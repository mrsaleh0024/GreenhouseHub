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
}
