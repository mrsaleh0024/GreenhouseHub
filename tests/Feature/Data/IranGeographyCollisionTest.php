<?php

namespace Tests\Feature\Data;

use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Support\PersianText;
use Database\Seeders\CatalogSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IranGeographyCollisionTest extends TestCase
{
    use RefreshDatabase;

    public function test_three_same_name_collision_groups_keep_both_source_identities(): void
    {
        $this->seed(CatalogSeeder::class);

        $groups = [
            ['mazandaran', 'گلوگاه', ['1020002002390', '10200022001157'], ['شهرستان بابل', 'شهرستان گلوگاه']],
            ['kerman', 'کشکوییه', ['1080001001008', '1080004002319'], ['شهرستان بافت', 'شهرستان رفسنجان']],
            ['kerman', 'علی آباد', ['1080003002953', '10800023001012'], ['شهرستان جیرفت', 'شهرستان ارزوییه']],
        ];

        foreach ($groups as [$provinceSlug, $label, $sourceRefs, $contexts]) {
            $province = CatalogItem::where('type', 'province')->where('slug', $provinceSlug)->firstOrFail();
            $items = CatalogItem::where('type', 'city')->where('parent_id', $province->id)
                ->where('normalized_label', PersianText::normalize($label))->get()
                ->keyBy(fn (CatalogItem $item) => (string) $item->source_ref);

            $this->assertCount(2, $items, $label);
            $this->assertEqualsCanonicalizing($sourceRefs, $items->keys()->map(fn ($id) => (string) $id)->all());
            $this->assertCount(2, $items->pluck('slug')->unique(), $label);
            $this->assertCount(2, $items->pluck('display_label')->unique(), $label);

            foreach ($sourceRefs as $index => $sourceRef) {
                $item = $items->get($sourceRef);
                $this->assertNotNull($item, $label.' '.$sourceRef);
                $this->assertSame($province->id, $item->parent_id);
                $this->assertSame($contexts[$index], $item->location_context);
                $this->assertStringContainsString($contexts[$index], $item->display_label);
                $this->assertFalse($item->location_ambiguous);
            }
        }
    }

    public function test_pre_split_references_keep_their_catalog_id_and_are_flagged(): void
    {
        $this->seed(CatalogSeeder::class);

        $province = CatalogItem::where('type', 'province')->where('slug', 'mazandaran')->firstOrFail();
        $original = CatalogItem::where('type', 'city')->where('source_ref', '1020002002390')->firstOrFail();
        CatalogItem::where('type', 'city')->where('source_ref', '10200022001157')->firstOrFail()->delete();

        // Reproduce a database seeded before source identities were stored.
        $original->forceFill(['source_ref' => null, 'location_context' => null])->save();
        $originalId = $original->id;

        $customer = Customer::create(['full_name' => 'مشتری آزمایشی', 'mobile' => '09123456789', 'province_id' => $province->id, 'city_id' => $originalId]);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه آزمایشی', 'province_id' => $province->id, 'city_id' => $originalId]);

        $this->seed(CatalogSeeder::class);

        $original->refresh();
        $customer->refresh();
        $greenhouse->refresh();

        $this->assertSame($originalId, $original->id);
        $this->assertSame('1020002002390', $original->source_ref);
        $this->assertTrue($original->location_ambiguous);
        $this->assertTrue($original->meta['ambiguous_reference']);
        $this->assertSame($originalId, $customer->city_id);
        $this->assertSame($originalId, $greenhouse->city_id);

        $newSibling = CatalogItem::where('type', 'city')->where('source_ref', '10200022001157')->firstOrFail();
        $this->assertNotSame($originalId, $newSibling->id);
        $this->assertSame('شهرستان گلوگاه', $newSibling->location_context);
        $this->assertStringContainsString('گلوگاه', $newSibling->display_label);
    }

    public function test_non_geographic_catalog_uniqueness_still_uses_normalized_labels(): void
    {
        $this->seed(CatalogSeeder::class);

        CatalogItem::create(['type' => 'crop', 'slug' => 'collision_test_crop', 'label_fa' => 'محصول آزمایشی یک', 'status' => 'approved']);

        $this->expectException(QueryException::class);
        CatalogItem::create(['type' => 'crop', 'slug' => 'collision_test_crop_variant', 'label_fa' => 'محصول‌آزمایشی یک', 'status' => 'approved']);
    }
}
