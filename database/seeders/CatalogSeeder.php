<?php

namespace Database\Seeders;

use App\Models\CatalogItem;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $data = require __DIR__.'/catalog_data.php';
        $provinces = [];

        foreach ($data as $type => $items) {
            foreach ($items as $index => [$slug, $label, $common, $parent, $category]) {
                $item = CatalogItem::updateOrCreate(
                    ['type' => $type, 'slug' => $slug],
                    ['label_fa' => $label, 'parent_id' => $parent === null ? null : $provinces[$parent],
                        'status' => 'approved', 'source' => 'system', 'is_common' => $common,
                        'sort_order' => ($index + 1) * 10, 'meta' => $category === null ? null : ['category' => $category]],
                );
                if ($type === 'province') {
                    $provinces[$slug] = $item->id;
                }
            }
        }
    }
}
