<?php

namespace App\Services;

use App\Models\CatalogItem;
use App\Models\Greenhouse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class GreenhouseService
{
    public function syncOptions(Greenhouse $greenhouse, array $idsByGroup, User $user): void
    {
        Gate::forUser($user)->authorize('update', $greenhouse);
        $groups = ['irrigation_type', 'heating_system', 'cooling_system', 'water_source', 'ventilation_system'];
        $ids = [];
        foreach ($idsByGroup as $group => $values) {
            if (! in_array($group, $groups, true) || ! is_array($values) || count($values) !== count(array_unique($values))) {
                throw ValidationException::withMessages(['options' => 'گزینه‌ها معتبر نیستند.']);
            }
            $items = CatalogItem::whereIn('id', $values)->where('type', $group)->whereIn('status', ['approved', 'pending'])->get();
            if ($items->count() !== count($values) || ($items->contains('slug', 'none') && $items->count() > 1)) {
                throw ValidationException::withMessages(['options' => 'گزینه‌های انتخاب‌شده معتبر نیستند.']);
            }
            $ids = array_merge($ids, $values);
        }
        DB::transaction(function () use ($greenhouse, $ids) {
            $old = DB::table('greenhouse_options')->join('catalog_items', 'catalog_items.id', '=', 'greenhouse_options.catalog_item_id')->where('greenhouse_id', $greenhouse->id)->pluck('slug')->all();
            DB::table('greenhouse_options')->where('greenhouse_id', $greenhouse->id)->delete();
            foreach ($ids as $id) {
                DB::table('greenhouse_options')->insert(['greenhouse_id' => $greenhouse->id, 'catalog_item_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
            }
            $new = CatalogItem::whereIn('id', $ids)->pluck('slug')->all();
            app(ActivityLogger::class)->log($greenhouse, 'options_changed', ['options' => ['old' => $old, 'new' => $new]]);
            app(GreenhouseCompleteness::class)->update($greenhouse);
        });
    }
}
