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
        $count = CatalogItem::count();
        $this->assertSame(31, CatalogItem::where('type', 'province')->count());
        $this->assertSame(31, CatalogItem::where('type', 'city')->count());
        $this->assertDatabaseHas('catalog_items', ['type' => 'city', 'slug' => 'tabriz', 'label_fa' => 'تبریز']);
        $this->seed(CatalogSeeder::class);
        $this->assertSame($count, CatalogItem::count());
    }

    public function test_persian_spelling_variants_normalize_equally(): void
    {
        $this->assertSame(PersianText::normalize('میکرو اسپرینکلر'), PersianText::normalize('ميكرو‌اسپرينكلر'));
        $this->assertSame('123', PersianText::normalize('۱۲٣'));
    }

    public function test_all_new_columns_are_classified(): void
    {
        foreach (config('data_classification') as $table => $columns) {
            if ($table === 'users') {
                continue;
            }
            $actual = Schema::getColumnListing($table);
            $classified = array_keys($columns);
            sort($actual);
            sort($classified);
            $this->assertSame($actual, $classified, $table);
            foreach ($columns as $classification) {
                $this->assertContains($classification, ['safe', 'internal', 'pii']);
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
        $this->assertFalse($owner->can('update', $greenhouse));
    }
}
