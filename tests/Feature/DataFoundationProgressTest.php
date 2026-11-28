<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Support\PersianText;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DataFoundationProgressTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_has_farsi_capitals_and_seeding_is_idempotent(): void
    {
        $this->seed(CatalogSeeder::class);

        $cities = json_decode(file_get_contents(database_path('seeders/iran-cities.json')), true, flags: JSON_THROW_ON_ERROR);
        $provinces = json_decode(file_get_contents(database_path('seeders/iran-provinces.json')), true, flags: JSON_THROW_ON_ERROR);
        $provinceSources = collect($provinces)->keyBy('id');
        $provinceItems = CatalogItem::where('type', 'province')->get()->keyBy(fn (CatalogItem $item) => PersianText::normalize($item->label_fa));
        $seeded = CatalogItem::where('type', 'city')->whereNotNull('source_ref')->get()->keyBy(fn (CatalogItem $item) => (string) $item->source_ref);
        $count = CatalogItem::count();

        $this->assertSame(31, CatalogItem::where('type', 'province')->count());
        $this->assertCount(count($cities), $seeded);
        $this->assertSame(count($cities), CatalogItem::where('type', 'city')->count());

        foreach ($cities as $city) {
            $item = $seeded->get((string) $city['id']);
            $this->assertNotNull($item, 'Missing source city '.$city['id']);
            $province = $provinceItems->get(PersianText::normalize($provinceSources->get($city['province_id'])['name']));
            $this->assertSame($province->id, $item->parent_id, 'Wrong province for source city '.$city['id']);
            $this->assertSame(PersianText::normalize($city['name']), $item->normalized_label);
            $this->assertSame('approved', $item->status);
        }

        $catalog = require database_path('seeders/catalog_data.php');
        foreach ($catalog['city'] as [$slug, $label]) {
            $this->assertDatabaseHas('catalog_items', ['type' => 'city', 'slug' => $slug, 'label_fa' => $label]);
        }

        foreach ([['mazandaran', 'گلوگاه'], ['kerman', 'کشکوییه'], ['kerman', 'علی آباد']] as [$provinceSlug, $label]) {
            $provinceId = CatalogItem::where('type', 'province')->where('slug', $provinceSlug)->value('id');
            $this->assertSame(2, CatalogItem::where('type', 'city')->where('parent_id', $provinceId)
                ->where('normalized_label', PersianText::normalize($label))->count(), $label);
        }

        $sourceIds = $seeded->pluck('id', 'source_ref')->all();

        $this->seed(CatalogSeeder::class);

        $this->assertSame($count, CatalogItem::count());
        $this->assertSame(count($cities), CatalogItem::where('type', 'city')->count());
        $this->assertSame($sourceIds, CatalogItem::where('type', 'city')->whereNotNull('source_ref')->pluck('id', 'source_ref')->all());
    }

    public function test_persian_spelling_variants_normalize_equally(): void
    {
        $this->assertSame(PersianText::normalize('میکرو اسپرینکلر'), PersianText::normalize('ميكرو‌اسپرينكلر'));
        $this->assertSame('123', PersianText::normalize('۱۲٣'));
    }

    public function test_all_new_columns_are_classified(): void
    {
        foreach (config('data_classification.tables') as $table => $columns) {
            $actual = Schema::getColumnListing($table);
            $classified = array_keys($columns);
            sort($actual);
            sort($classified);
            $this->assertSame($actual, $classified, $table);
            foreach ($columns as $classification) {
                $this->assertContains($classification, ['safe', 'internal', 'pii', 'credential']);
            }
        }
    }

    public function test_owner_visibility_requires_a_customer_link(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $this->assertFalse(Greenhouse::visibleTo($owner)->whereKey($greenhouse)->exists());
        $customer->users()->attach($owner);
        $this->assertTrue(Greenhouse::visibleTo($owner)->whereKey($greenhouse)->exists());
        $this->assertTrue($owner->can('view', $greenhouse));
        $this->assertTrue($owner->can('update', $greenhouse));
    }
}
