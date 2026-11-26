<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\User;
use App\Support\PersianText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class CatalogService
{
    public function findOrCreateCustom(string $type, string $label, User $user, ?int $parent = null): CatalogItem
    {
        if (! in_array($type, ['city', 'variety', 'crop', 'substrate', 'equipment', 'zone_type', 'structure_type', 'cultivation_type', 'irrigation_type', 'heating_system', 'cooling_system', 'water_source', 'ventilation_system'], true)) {
            throw ValidationException::withMessages(['type' => 'این فهرست قابل افزودن نیست.']);
        }
        $expectedParent = match ($type) {
            'city' => 'province', 'variety' => 'crop', default => null
        };
        if (($expectedParent === null && $parent !== null) || ($expectedParent !== null && ! CatalogItem::whereKey($parent)->where('type', $expectedParent)->where('status', 'approved')->exists())) {
            throw ValidationException::withMessages(['parent' => 'مقدار والد معتبر نیست.']);
        }
        $norm = PersianText::normalize($label);
        if ($norm === '') {
            throw ValidationException::withMessages(['label' => 'عنوان معتبر نیست.']);
        }
        $item = CatalogItem::where(['type' => $type, 'parent_key' => $parent ?? 0, 'normalized_label' => $norm])->first();
        if ($item) {
            while ($item->status === 'merged' && $item->mergedInto) {
                $item = $item->mergedInto;
            }

            return $item;
        }
        $key = 'catalog-create:'.$user->id;
        if (RateLimiter::tooManyAttempts($key, 50)) {
            throw ValidationException::withMessages(['label' => 'تعداد درخواست‌ها بیش از حد مجاز است.']);
        }
        $created = CatalogItem::create(['type' => $type, 'parent_id' => $parent, 'slug' => 'custom_'.strtolower(bin2hex(random_bytes(6))), 'label_fa' => $label, 'status' => $type === 'city' ? 'approved' : 'pending', 'source' => 'user', 'created_by' => $user->id]);
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
        if ($item->status !== 'pending' || ($slug !== null && ! preg_match('/^[a-z0-9]+(?:_[a-z0-9]+)*$/', $slug))) {
            throw ValidationException::withMessages(['status' => 'آیتم یا شناسه معتبر نیست.']);
        }
        $item->forceFill(['slug' => $slug ?? $item->slug, 'label_en' => $labelEn, 'status' => 'approved', 'reviewed_by' => $admin->id, 'reviewed_at' => now()])->save();
        app(ActivityLogger::class)->log($item, 'catalog_approved');
    }

    public function reject(CatalogItem $item, User $admin): void
    {
        Gate::forUser($admin)->authorize('reject', $item);
        if ($item->status !== 'pending' || $this->usageCount($item)) {
            throw ValidationException::withMessages(['item' => 'آیتم مورد استفاده را نمی‌توان حذف کرد.']);
        }
        $item->delete();
    }

    public function merge(CatalogItem $from, CatalogItem $into, User $admin): void
    {
        Gate::forUser($admin)->authorize('merge', $from);
        if ($from->is($into) || $from->type !== $into->type || $from->parent_id !== $into->parent_id || $from->status === 'merged' || $into->status === 'merged') {
            throw ValidationException::withMessages(['item' => 'ادغام این گزینه‌ها مجاز نیست.']);
        }
        DB::transaction(function () use ($from, $into) {
            foreach (['greenhouse_options' => ['greenhouse_id', 'catalog_item_id'], 'zone_equipment' => ['zone_id', 'equipment_id']] as $table => [$owner, $column]) {
                $duplicates = DB::table($table)->where($column, $into->id)->pluck($owner);
                DB::table($table)->where($column, $from->id)->whereIn($owner, $duplicates)->delete();
            }
            foreach (CatalogReferences::COLUMNS as $table => $columns) {
                foreach ($columns as $column) {
                    if ($table === 'catalog_items' && $column === 'parent_id') {
                        foreach (CatalogItem::where('parent_id', $from->id)->get() as $child) {
                            $child->parent_id = $into->id;
                            $child->save();
                        }
                    } else {
                        DB::table($table)->where($column, $from->id)->update([$column => $into->id]);
                    }
                }
            }
            $from->forceFill(['status' => 'merged', 'merged_into_id' => $into->id])->save();
            app(ActivityLogger::class)->log($from, 'catalog_merged', ['merged_into_id' => ['old' => null, 'new' => $into->id]]);
        });
    }
}
