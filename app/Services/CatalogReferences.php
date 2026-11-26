<?php

namespace App\Services;

class CatalogReferences
{
    public const COLUMNS = [
        'customers' => ['province_id', 'city_id'],
        'greenhouses' => ['province_id', 'city_id', 'structure_type_id', 'cultivation_type_id'],
        'greenhouse_options' => ['catalog_item_id'],
        'zones' => ['zone_type_id'],
        'zone_equipment' => ['equipment_id'],
        'cultivation_cycles' => ['crop_id', 'variety_id', 'substrate_id'],
        'catalog_items' => ['parent_id', 'merged_into_id'],
    ];
}
