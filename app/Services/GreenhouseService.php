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
            if (! in_array($group, $groups, true) || ! is_array($values)) {
                throw ValidationException::withMessages(['options' => 'گزینه‌ها معتبر نیستند.']);
            }
            foreach ($values as $value) {
                if (! is_int($value) && (! is_string($value) || ! ctype_digit($value))) {
                    throw ValidationException::withMessages(['options' => 'گزینه‌ها معتبر نیستند.']);
                }
            }
            if (count($values) !== count(array_unique($values))) {
                throw ValidationException::withMessages(['options' => 'گزینه‌ها معتبر نیستند.']);
            }
            $attachedIds = DB::table('greenhouse_options')->where('greenhouse_id', $greenhouse->id)->pluck('catalog_item_id')->all();
            $query = CatalogItem::whereIn('id', $values)->where('type', $group);
            CatalogVisibility::constrain($query, $user, $attachedIds);
            $items = $query->get();
            if ($items->count() !== count($values) || ($items->contains('slug', 'none') && $items->count() > 1)) {
                throw ValidationException::withMessages(['options' => 'گزینه‌های انتخاب‌شده معتبر نیستند.']);
            }
            $ids[$group] = $items->modelKeys();
        }
        DB::transaction(function () use ($greenhouse, $ids, $user) {
            $locked = Greenhouse::whereKey($greenhouse->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('update', $locked);
            $old = [];
            $new = [];
            foreach ($ids as $group => $groupIds) {
                $current = DB::table('greenhouse_options')->join('catalog_items', 'catalog_items.id', '=', 'greenhouse_options.catalog_item_id')
                    ->where('greenhouse_id', $locked->id)->where('catalog_items.type', $group)
                    ->orderBy('catalog_items.id')->pluck('catalog_items.id')->map(fn ($id) => (int) $id)->all();
                $selected = CatalogItem::whereIn('id', $groupIds)->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
                if ($current === $selected) {
                    continue;
                }
                $old[$group] = $current;
                $new[$group] = $selected;
                DB::table('greenhouse_options')->where('greenhouse_id', $locked->id)
                    ->whereIn('catalog_item_id', CatalogItem::where('type', $group)->select('id'))->delete();
                foreach ($groupIds as $id) {
                    DB::table('greenhouse_options')->insert(['greenhouse_id' => $locked->id, 'catalog_item_id' => $id, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
            if ($old !== []) {
                app(ActivityLogger::class)->log($locked, 'options_changed', ['options' => ['old' => $old, 'new' => $new]], actor: $user);
                app(GreenhouseCompleteness::class)->update($locked);
            }
        });
    }
}
