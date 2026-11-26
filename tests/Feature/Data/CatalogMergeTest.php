<?php

namespace Tests\Feature\Data;

use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\CultivationCycle;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Models\Zone;
use App\Services\CatalogService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CatalogMergeTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_equipment_needs_manager_decision_and_preserves_notes(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $admin = User::factory()->role(Role::Admin)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $zone = Zone::create(['greenhouse_id' => $greenhouse->id, 'name' => 'سالن']);
        $target = CatalogItem::where('type', 'equipment')->where('slug', 'exhaust_fan')->firstOrFail();
        $source = CatalogItem::create(['type' => 'equipment', 'slug' => 'fan_custom', 'label_fa' => 'فن ویژه', 'status' => 'pending', 'source' => 'user']);
        DB::table('zone_equipment')->insert([
            ['zone_id' => $zone->id, 'equipment_id' => $source->id, 'quantity' => 2, 'notes' => 'یادداشت اول'],
            ['zone_id' => $zone->id, 'equipment_id' => $target->id, 'quantity' => 3, 'notes' => 'یادداشت دوم'],
        ]);
        $catalog = app(CatalogService::class);

        try {
            $catalog->merge($source, $target, $admin);
            $this->fail('The conflicting quantities were not reviewed.');
        } catch (ValidationException) {
            $this->assertSame('pending', $source->fresh()->status);
            $this->assertDatabaseCount('zone_equipment', 2);
        }

        $catalog->merge($source, $target, $admin, [$zone->id => 4]);
        $this->assertSame('merged', $source->fresh()->status);
        $this->assertDatabaseHas('zone_equipment', ['zone_id' => $zone->id, 'equipment_id' => $target->id, 'quantity' => 4, 'notes' => "یادداشت اول\nیادداشت دوم"]);
        $this->assertDatabaseMissing('zone_equipment', ['zone_id' => $zone->id, 'equipment_id' => $source->id]);
        $this->assertDatabaseHas('activities', ['event' => 'catalog_merged', 'subject_type' => 'catalog_item', 'subject_id' => $source->id, 'actor_id' => $admin->id]);
    }

    public function test_merging_crops_repoints_variety_when_labels_collide(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $admin = User::factory()->role(Role::Admin)->create();
        $target = CatalogItem::where('type', 'crop')->where('slug', 'tomato')->firstOrFail();
        $source = CatalogItem::create(['type' => 'crop', 'slug' => 'custom_crop', 'label_fa' => 'گوجه نمونه', 'status' => 'pending', 'source' => 'user']);
        $first = CatalogItem::create(['type' => 'variety', 'parent_id' => $source->id, 'slug' => 'variety_a', 'label_fa' => 'رقم یک', 'status' => 'pending', 'source' => 'user']);
        $second = CatalogItem::create(['type' => 'variety', 'parent_id' => $target->id, 'slug' => 'variety_b', 'label_fa' => 'رقم یک', 'status' => 'approved', 'source' => 'system']);
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $cycle = CultivationCycle::create(['greenhouse_id' => $greenhouse->id, 'crop_id' => $source->id, 'variety_id' => $first->id, 'planting_date' => '2026-01-01']);

        app(CatalogService::class)->merge($source, $target, $admin);

        $this->assertSame($target->id, $cycle->fresh()->crop_id);
        $this->assertSame($second->id, $cycle->fresh()->variety_id);
        $this->assertSame($second->id, $first->fresh()->merged_into_id);
    }
}
