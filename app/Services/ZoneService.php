<?php

namespace App\Services;

use App\Support\AuditActorContext;
use App\Models\Greenhouse;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ZoneService
{
    public function save(Greenhouse $greenhouse, User $user, array $input, ?Zone $zone = null): Zone
    {
        return app(AuditActorContext::class)->run($user, function () use ($greenhouse, $user, $input, $zone) {
            if (array_diff(array_keys($input), ['name', 'zone_type_id', 'area_m2', 'capacity_plants']) !== []) {
                throw ValidationException::withMessages(['zone' => 'تغییر اطلاعات کلیدی از این بخش مجاز نیست.']);
            }

            return DB::transaction(function () use ($greenhouse, $user, $input, $zone) {
                $lockedGreenhouse = Greenhouse::whereKey($greenhouse->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($user)->authorize('manageZones', $lockedGreenhouse);
                if ($zone) {
                    $zone = Zone::whereKey($zone->id)->where('greenhouse_id', $lockedGreenhouse->id)->lockForUpdate()->firstOrFail();
                    Gate::forUser($user)->authorize('update', $zone);
                }
                $data = Validator::make($input, [
                    'name' => ['required', 'string', 'max:120'],
                    'zone_type_id' => ['nullable', 'integer', Rule::exists('catalog_items', 'id')->where(function ($query) use ($user, $zone) {
                        $query->where('type', 'zone_type');
                        CatalogVisibility::constrain($query, $user, array_filter([$zone?->zone_type_id]));
                    })],
                    'area_m2' => ['nullable', 'numeric', 'gt:0', 'max:99999999.99'],
                    'capacity_plants' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
                ], [
                    'name.required' => 'نام ناحیه را وارد کنید.', 'name.max' => 'نام ناحیه بیش از حد طولانی است.',
                    'zone_type_id.exists' => 'نوع ناحیه معتبر نیست.', 'area_m2.numeric' => 'مساحت باید عدد باشد.',
                    'area_m2.gt' => 'مساحت باید بزرگ‌تر از صفر باشد.', 'capacity_plants.integer' => 'ظرفیت باید عدد صحیح باشد.',
                    'capacity_plants.min' => 'ظرفیت باید دست‌کم یک بوته باشد.',
                ])->validate();
                $zone ??= new Zone(['greenhouse_id' => $lockedGreenhouse->id]);
                $zone->fill($data)->save();

                return $zone;
            }, 3);
        });
    }

    public function syncEquipment(Zone $zone, User $user, array $rows): void
    {
        Gate::forUser($user)->authorize('update', $zone);
        $attached = DB::table('zone_equipment')->where('zone_id', $zone->id)->pluck('equipment_id')->all();
        $equipmentRule = Rule::exists('catalog_items', 'id')->where(function ($query) use ($user, $attached) {
            $query->where('type', 'equipment');
            CatalogVisibility::constrain($query, $user, $attached);
        });
        $data = Validator::make(['rows' => $rows], [
            'rows' => ['array'], 'rows.*' => ['required', 'array:equipment_id,quantity,notes'],
            'rows.*.equipment_id' => ['required', 'integer', 'distinct', $equipmentRule],
            'rows.*.quantity' => ['required', 'integer', 'between:1,65535'],
            'rows.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'rows.*.equipment_id.exists' => 'تجهیز انتخاب‌شده معتبر نیست.', 'rows.*.equipment_id.distinct' => 'هر تجهیز فقط یک بار قابل ثبت است.',
            'rows.*.quantity.between' => 'تعداد تجهیزات باید دست‌کم یک عدد باشد.',
        ])->validate()['rows'];

        DB::transaction(function () use ($zone, $user, $data) {
            $locked = Zone::whereKey($zone->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('update', $locked);
            $old = DB::table('zone_equipment')->where('zone_id', $locked->id)->orderBy('equipment_id')->get(['equipment_id', 'quantity', 'notes'])
                ->map(fn ($row) => ['equipment_id' => (int) $row->equipment_id, 'quantity' => (int) $row->quantity, 'notes' => $row->notes])->all();
            $new = [];
            foreach ($data as $item) {
                $new[] = ['equipment_id' => (int) $item['equipment_id'], 'quantity' => (int) $item['quantity'], 'notes' => $item['notes'] ?? null];
            }
            usort($new, fn ($a, $b) => $a['equipment_id'] <=> $b['equipment_id']);
            if ($old === $new) {
                return;
            }
            DB::table('zone_equipment')->where('zone_id', $locked->id)->delete();
            foreach ($new as $item) {
                DB::table('zone_equipment')->insert($item + ['zone_id' => $locked->id, 'created_at' => now(), 'updated_at' => now()]);
            }
            app(ActivityLogger::class)->log($locked, 'equipment_changed', ['equipment' => ['old' => $old, 'new' => $new]], actor: $user);
        }, 3);
    }
}
