<?php

namespace App\Services;

use App\Enums\CatalogType;
use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\User;
use App\Models\Zone;
use App\Support\PersianText;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CatalogService
{
    public function forPicker(CatalogType $type, User $viewer, ?string $q = null, ?int $parentId = null, int $limit = 50): Collection
    {
        Gate::forUser($viewer)->authorize('viewAny', CatalogItem::class);
        $staff = $viewer->hasAnyRole([Role::SuperAdmin->value, Role::Admin->value, Role::Expert->value]);
        $query = CatalogItem::where('type', $type->value)->where(function ($query) use ($viewer, $staff) {
            $query->where('status', 'approved')->orWhere(function ($query) use ($viewer, $staff) {
                $query->where('status', 'pending');
                if (! $staff) {
                    $query->where('created_by', $viewer->id);
                }
            });
        });
        if ($parentId !== null) {
            $query->where('parent_id', $parentId);
        }
        if (filled($q)) {
            $query->where('normalized_label', 'like', '%'.PersianText::normalize($q).'%');
        }

        return $query->orderByDesc('is_common')->orderBy('sort_order')->orderBy('label_fa')->limit(max(1, min(100, $limit)))->get();
    }

    public function findOrCreateCustom(CatalogType|string $type, string $label, User $user, ?int $parent = null): CatalogItem
    {
        Gate::forUser($user)->authorize('create', CatalogItem::class);
        $type = $type instanceof CatalogType ? $type : CatalogType::tryFrom($type);
        if (! $type || ! $type->isUserCreatable()) {
            throw ValidationException::withMessages(['type' => 'این فهرست قابل افزودن نیست.']);
        }
        $expectedParent = $type->parentType();
        $parentItem = $parent === null ? null : CatalogItem::whereKey($parent)->where('type', $expectedParent?->value)->first();
        if (($expectedParent === null && $parent !== null) || ($expectedParent !== null && (! $parentItem || ! Gate::forUser($user)->allows('view', $parentItem)))) {
            throw ValidationException::withMessages(['parent' => 'مقدار والد معتبر نیست.']);
        }
        $label = trim($label);
        $norm = PersianText::normalize($label);
        if ($norm === '' || mb_strlen($label) > 150) {
            throw ValidationException::withMessages(['label' => 'عنوان معتبر نیست.']);
        }
        $item = CatalogItem::where(['type' => $type->value, 'parent_key' => $parent ?? 0, 'normalized_label' => $norm])->first();
        if ($item) {
            $visited = [];
            while ($item->status === 'merged') {
                if (isset($visited[$item->id])) {
                    throw new \LogicException('زنجیره ادغام کاتالوگ نامعتبر است.');
                }
                $visited[$item->id] = true;
                $item = $item->mergedInto ?? throw new \LogicException('مقصد ادغام کاتالوگ پیدا نشد.');
            }

            return $item;
        }
        $key = 'catalog-create:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 50)) {
            throw ValidationException::withMessages(['label' => 'تعداد درخواست‌ها بیش از حد مجاز است.']);
        }
        $created = CatalogItem::create(['type' => $type->value, 'parent_id' => $parent, 'slug' => 'custom_'.strtolower(bin2hex(random_bytes(6))), 'label_fa' => $label, 'status' => $type->autoApproves() ? 'approved' : 'pending', 'source' => 'user', 'created_by' => $user->id]);
        RateLimiter::hit($key, 3600);

        return $created;
    }

    public function usageCount(CatalogItem $item): int
    {
        $count = 0;
        foreach (CatalogReferences::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                $count += DB::table($table)->where($column, $item->id)->count();
            }
        }

        return $count;
    }

    public function approve(CatalogItem $item, User $admin, ?string $slug = null, ?string $labelEn = null): void
    {
        Gate::forUser($admin)->authorize('approve', $item);
        DB::transaction(function () use ($item, $admin, $slug, $labelEn) {
            $locked = CatalogItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['item' => 'این گزینه در انتظار بررسی نیست.']);
            }
            $values = Validator::make(['slug' => $slug, 'label_en' => $labelEn], [
                'slug' => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:_[a-z0-9]+)*$/', Rule::unique('catalog_items', 'slug')->where('type', $locked->type)->ignore($locked->id)],
                'label_en' => ['nullable', 'string', 'max:150'],
            ], [
                'slug.regex' => 'شناسه انگلیسی باید با حروف کوچک، عدد و زیرخط نوشته شود.',
                'slug.unique' => 'این شناسه قبلاً در همین فهرست استفاده شده است.',
                'slug.max' => 'شناسه انگلیسی بیش از حد طولانی است.',
            ])->validate();
            $locked->forceFill(['slug' => $values['slug'] ?? $locked->slug, 'label_en' => $values['label_en'] ?? null, 'status' => 'approved', 'reviewed_by' => $admin->id, 'reviewed_at' => now()])->save();
            app(ActivityLogger::class)->log($locked, 'catalog_approved', actor: $admin);
        }, 3);
    }

    public function reject(CatalogItem $item, User $admin): void
    {
        Gate::forUser($admin)->authorize('reject', $item);
        DB::transaction(function () use ($item) {
            $locked = CatalogItem::whereKey($item->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending' || $this->usageCount($locked)) {
                throw ValidationException::withMessages(['item' => 'گزینه مورد استفاده را نمی‌توان حذف کرد؛ در صورت نیاز آن را ادغام کنید.']);
            }
            $locked->delete();
        }, 3);
    }

    public function equipmentConflicts(CatalogItem $from, CatalogItem $into, User $admin): Collection
    {
        Gate::forUser($admin)->authorize('merge', $from);
        if ($from->type !== 'equipment' || $into->type !== 'equipment' || $from->is($into)) {
            return collect();
        }

        return DB::table('zone_equipment as source')->join('zone_equipment as target', function ($join) use ($into) {
            $join->on('source.zone_id', '=', 'target.zone_id')->where('target.equipment_id', '=', $into->id);
        })->join('zones', 'zones.id', '=', 'source.zone_id')->join('greenhouses', 'greenhouses.id', '=', 'zones.greenhouse_id')
            ->where('source.equipment_id', $from->id)->orderBy('zones.id')->get([
                'zones.id as zone_id', 'zones.name as zone_name', 'greenhouses.name as greenhouse_name',
                'source.quantity as source_quantity', 'source.notes as source_notes',
                'target.quantity as target_quantity', 'target.notes as target_notes',
            ]);
    }

    public function merge(CatalogItem $from, CatalogItem $into, User $admin, array $equipmentResolutions = []): void
    {
        Gate::forUser($admin)->authorize('merge', $from);
        DB::transaction(function () use ($from, $into, $admin, $equipmentResolutions) {
            $locked = CatalogItem::whereIn('id', [$from->id, $into->id])->orderBy('id')->lockForUpdate()->get()->keyBy('id');
            $source = $locked->get($from->id);
            $target = $locked->get($into->id);
            if (! $source || ! $target || $source->is($target) || $source->type !== $target->type || $source->parent_id !== $target->parent_id || $source->status === 'merged' || $target->status === 'merged') {
                throw ValidationException::withMessages(['item' => 'ادغام این گزینه‌ها مجاز نیست.']);
            }

            $this->mergePair($source, $target, $admin, $equipmentResolutions);
        }, 3);
    }

    private function mergePair(CatalogItem $from, CatalogItem $into, User $admin, array $equipmentResolutions): void
    {
        foreach (CatalogItem::where('parent_id', $from->id)->orderBy('id')->lockForUpdate()->get() as $child) {
            $collision = CatalogItem::where('type', $child->type)->where('parent_key', $into->id)
                ->where('normalized_label', $child->normalized_label)->lockForUpdate()->first();
            if ($collision) {
                while ($collision->status === 'merged' && $collision->mergedInto) {
                    $collision = $collision->mergedInto;
                }
                if ($child->status !== 'merged') {
                    $this->mergePair($child, $collision, $admin, $equipmentResolutions);
                }
            } else {
                $child->parent_id = $into->id;
                $child->save();
            }
        }

        $duplicates = DB::table('greenhouse_options')->where('catalog_item_id', $into->id)->pluck('greenhouse_id');
        DB::table('greenhouse_options')->where('catalog_item_id', $from->id)->whereIn('greenhouse_id', $duplicates)->delete();

        foreach ($this->equipmentConflicts($from, $into, $admin) as $conflict) {
            if (! array_key_exists($conflict->zone_id, $equipmentResolutions)) {
                throw ValidationException::withMessages(['equipment' => 'تعداد نهایی هر ناحیه دارای تجهیزات تکراری را مشخص کنید.']);
            }
            $quantity = Validator::make(['quantity' => $equipmentResolutions[$conflict->zone_id]], [
                'quantity' => ['required', 'integer', 'between:1,65535'],
            ], ['quantity.between' => 'تعداد نهایی تجهیزات معتبر نیست.', 'quantity.integer' => 'تعداد نهایی باید عدد صحیح باشد.'])->validate()['quantity'];
            $notes = array_values(array_unique(array_filter([$conflict->source_notes, $conflict->target_notes], fn ($value) => filled($value))));
            $combinedNotes = $notes === [] ? null : implode("\n", $notes);
            if ($combinedNotes !== null && mb_strlen($combinedNotes) > 255) {
                throw ValidationException::withMessages(['equipment' => 'یادداشت‌های این تجهیزات برای نگهداری در یک ردیف طولانی هستند؛ پیش از ادغام آن‌ها را بررسی کنید.']);
            }
            DB::table('zone_equipment')->where('zone_id', $conflict->zone_id)->where('equipment_id', $into->id)
                ->update(['quantity' => $quantity, 'notes' => $combinedNotes, 'updated_at' => now()]);
            DB::table('zone_equipment')->where('zone_id', $conflict->zone_id)->where('equipment_id', $from->id)->delete();
            $zone = Zone::withTrashed()->findOrFail($conflict->zone_id);
            app(ActivityLogger::class)->log($zone, 'equipment_changed', [
                'merge' => ['old' => [['id' => $from->id, 'quantity' => $conflict->source_quantity, 'notes' => $conflict->source_notes], ['id' => $into->id, 'quantity' => $conflict->target_quantity, 'notes' => $conflict->target_notes]], 'new' => ['id' => $into->id, 'quantity' => (int) $quantity, 'notes' => $combinedNotes]],
            ], actor: $admin);
        }

        foreach (CatalogReferences::COLUMNS as $table => $columns) {
            foreach ($columns as $column) {
                if ($table !== 'catalog_items' || $column !== 'parent_id') {
                    DB::table($table)->where($column, $from->id)->update([$column => $into->id]);
                }
            }
        }
        $from->forceFill(['status' => 'merged', 'merged_into_id' => $into->id, 'reviewed_by' => $admin->id, 'reviewed_at' => now()])->save();
        app(ActivityLogger::class)->log($from, 'catalog_merged', ['merged_into_id' => ['old' => null, 'new' => $into->id]], actor: $admin);
    }
}
