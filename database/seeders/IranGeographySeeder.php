<?php

namespace Database\Seeders;

use App\Models\CatalogItem;
use App\Support\PersianText;
use Illuminate\Database\Seeder;

class IranGeographySeeder extends Seeder
{
    public function run(): void
    {
        // Source: sajaddp/list-of-cities-in-Iran, cities-filtered, divisions through 1404 (GPL-3.0).
        $provinces = json_decode(file_get_contents(__DIR__.'/iran-provinces.json'), true, flags: JSON_THROW_ON_ERROR);
        $cities = json_decode(file_get_contents(__DIR__.'/iran-cities.json'), true, flags: JSON_THROW_ON_ERROR);
        $parents = [];
        $existingCities = CatalogItem::where('type', 'city')->get()->groupBy('parent_id');
        foreach ($provinces as $province) {
            $parent = CatalogItem::where('type', 'province')->get()->first(fn ($item) => PersianText::normalize($item->label_fa) === PersianText::normalize($province['name']));
            if (! $parent) {
                throw new \RuntimeException('Unmatched province: '.$province['name']);
            }
            $parents[$province['id']] = $parent->id;
        }
        foreach ($cities as $index => $city) {
            $parent = $parents[$city['province_id']];
            $existing = $existingCities->get($parent, collect())->first(fn ($item) => PersianText::normalize($item->label_fa) === PersianText::normalize($city['name']));
            if ($existing) {
                $existing->update(['status' => 'approved']);

                continue;
            }
            $created = CatalogItem::firstOrCreate(['type' => 'city', 'slug' => 'iran-'.$city['id']], ['label_fa' => $city['name'], 'parent_id' => $parent, 'status' => 'approved', 'source' => 'system', 'is_common' => false, 'sort_order' => $index + 1]);
            $existingCities->put($parent, $existingCities->get($parent, collect())->push($created));
        }
    }
}
