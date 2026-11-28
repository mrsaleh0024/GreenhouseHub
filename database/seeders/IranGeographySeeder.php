<?php

namespace Database\Seeders;

use App\Models\CatalogItem;
use App\Support\PersianText;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class IranGeographySeeder extends Seeder
{
    public function run(): void
    {
        // Source: sajaddp/list-of-cities-in-Iran, cities-filtered, divisions through 1404 (GPL-3.0).
        $provinces = json_decode(file_get_contents(__DIR__.'/iran-provinces.json'), true, flags: JSON_THROW_ON_ERROR);
        $cities = json_decode(file_get_contents(__DIR__.'/iran-cities.json'), true, flags: JSON_THROW_ON_ERROR);
        $contexts = require __DIR__.'/iran-city-contexts.php';
        $provinceItems = CatalogItem::where('type', 'province')->get();
        $parents = [];
        $sourceNames = [];

        foreach ($provinces as $province) {
            $parent = $provinceItems->first(fn ($item) => PersianText::normalize($item->label_fa) === PersianText::normalize($province['name']));
            if (! $parent) {
                throw new \RuntimeException('Unmatched province: '.$province['name']);
            }
            $parents[$province['id']] = $parent->id;
        }

        foreach ($cities as $city) {
            $sourceNames[$city['province_id']][PersianText::normalize($city['name'])][] = $city;
        }

        $existingCities = CatalogItem::where('type', 'city')->get()->groupBy('parent_id');

        foreach ($cities as $index => $city) {
            $parentId = $parents[$city['province_id']];
            $sourceRef = (string) $city['id'];
            $sameNameSources = $sourceNames[$city['province_id']][PersianText::normalize($city['name'])];
            $parentCities = $existingCities->get($parentId, collect());
            $existing = $parentCities->first(fn (CatalogItem $item) => (string) $item->source_ref === $sourceRef || $item->slug === 'iran-'.$city['id']);

            if (! $existing && count($sameNameSources) === 1) {
                $existing = $parentCities->first(fn (CatalogItem $item) => blank($item->source_ref)
                    && PersianText::normalize($item->label_fa) === PersianText::normalize($city['name']));
            }

            $meta = array_merge($existing?->meta ?? [], [
                'source_id' => $city['id'],
                'province_source_id' => $city['province_id'],
                'county_id' => $city['county_id'],
                'district_id' => $city['district_id'],
            ]);
            $attributes = [
                'parent_id' => $parentId,
                'status' => 'approved',
                'source' => 'system',
                'source_ref' => $sourceRef,
                'location_context' => $contexts[$city['id']] ?? null,
                'meta' => $meta,
            ];

            if ($existing) {
                // Keep legacy capital labels, slugs and ordering untouched.
                if (! str_starts_with($existing->slug, 'iran-')) {
                    $existing->fill($attributes)->save();

                    continue;
                }

                $existing->fill($attributes + [
                    'label_fa' => $city['name'],
                    'is_common' => false,
                    'sort_order' => $index + 1,
                ])->save();

                continue;
            }

            // A same-name sibling only appears when splitting a previously collapsed
            // identity. References made before that split are ambiguous, so flag the
            // older row without moving any customer or greenhouse foreign keys.
            foreach ($parentCities as $sibling) {
                if (PersianText::normalize($sibling->label_fa) === PersianText::normalize($city['name']) && $this->hasReferences($sibling)) {
                    $sibling->forceFill([
                        'location_ambiguous' => true,
                        'meta' => array_merge($sibling->meta ?? [], [
                            'ambiguous_reference' => true,
                            'ambiguous_source_ids' => array_map(fn ($source) => (int) $source['id'], $sameNameSources),
                        ]),
                    ])->save();
                }
            }

            $created = CatalogItem::create($attributes + [
                'type' => 'city',
                'slug' => 'iran-'.$city['id'],
                'label_fa' => $city['name'],
                'is_common' => false,
                'sort_order' => $index + 1,
            ]);
            $existingCities->put($parentId, $parentCities->push($created));
        }
    }

    private function hasReferences(CatalogItem $item): bool
    {
        return DB::table('customers')->where('city_id', $item->id)->exists()
            || DB::table('greenhouses')->where('city_id', $item->id)->exists()
            || DB::table('catalog_items')->where('parent_id', $item->id)->exists()
            || DB::table('catalog_items')->where('merged_into_id', $item->id)->exists();
    }
}
