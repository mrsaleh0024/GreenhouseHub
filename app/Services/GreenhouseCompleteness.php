<?php

namespace App\Services;

use App\Models\Greenhouse;
use Illuminate\Support\Facades\DB;

class GreenhouseCompleteness
{
    public function percent(Greenhouse $greenhouse): int
    {
        $percent = ($greenhouse->province_id && $greenhouse->city_id ? 10 : 0)
            + (filled($greenhouse->address) ? 5 : 0)
            + ($greenhouse->latitude !== null && $greenhouse->longitude !== null ? 10 : 0)
            + ($greenhouse->area_m2 > 0 ? 10 : 0)
            + ($greenhouse->structure_type_id ? 10 : 0)
            + ($greenhouse->cultivation_type_id ? 10 : 0)
            + ($greenhouse->responsible_expert_id ? 5 : 0)
            + ($greenhouse->zones()->exists() ? 5 : 0)
            + ($greenhouse->cycles()->exists() ? 10 : 0);

        $types = DB::table('greenhouse_options')->join('catalog_items', 'catalog_items.id', '=', 'greenhouse_options.catalog_item_id')
            ->where('greenhouse_id', $greenhouse->id)->distinct()->pluck('catalog_items.type')->all();
        foreach (['irrigation_type', 'heating_system', 'cooling_system', 'water_source', 'ventilation_system'] as $type) {
            $percent += in_array($type, $types, true) ? 5 : 0;
        }

        return $percent;
    }

    public function update(Greenhouse $greenhouse): void
    {
        $greenhouse->newQuery()->whereKey($greenhouse->id)->update(['completeness_percent' => $this->percent($greenhouse)]);
    }

    /** @return array{label: string, description: string, route: string, step: ?int} */
    public function nextAction(Greenhouse $greenhouse, array $optionTypes): array
    {
        if (! $greenhouse->structure_type_id || ! $greenhouse->cultivation_type_id) {
            return ['label' => 'تکمیل ساختار و روش کشت', 'description' => 'نوع سازه و روش کشت را تکمیل کنید.', 'route' => 'greenhouses.complete', 'step' => 2];
        }
        if (array_diff(['irrigation_type', 'heating_system', 'cooling_system', 'water_source', 'ventilation_system'], $optionTypes) !== []) {
            return ['label' => 'تکمیل سامانه‌ها', 'description' => 'سامانه‌های گلخانه را مشخص کنید.', 'route' => 'greenhouses.complete', 'step' => 3];
        }
        if ((int) $greenhouse->zones_count === 0) {
            return ['label' => 'افزودن ناحیه', 'description' => 'سالن یا بخش‌های گلخانه را ثبت کنید.', 'route' => 'greenhouses.zones', 'step' => null];
        }
        if ((int) $greenhouse->cycles_count === 0) {
            return ['label' => 'ثبت نخستین کشت', 'description' => 'محصول و تاریخ کاشت را ثبت کنید.', 'route' => 'greenhouses.cultivation', 'step' => null];
        }
        if (! $greenhouse->area_m2 || blank($greenhouse->address) || $greenhouse->latitude === null || $greenhouse->longitude === null) {
            return ['label' => 'تکمیل اطلاعات پایه', 'description' => 'مساحت، نشانی و مختصات را در صورت دسترسی تکمیل کنید.', 'route' => 'greenhouses.complete', 'step' => 1];
        }
        if (! $greenhouse->responsible_expert_id || ! $greenhouse->province_id || ! $greenhouse->city_id) {
            return ['label' => 'مشاهده اطلاعات', 'description' => 'بخشی از اطلاعات پرونده نیازمند بررسی مدیریت است.', 'route' => 'greenhouses.complete', 'step' => 1];
        }

        return ['label' => 'ویرایش اطلاعات', 'description' => 'اطلاعات اصلی پرونده تکمیل شده است.', 'route' => 'greenhouses.complete', 'step' => 1];
    }
}
