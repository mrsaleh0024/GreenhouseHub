<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Livewire\Admin\CatalogManager;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Models\Zone;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_approve_a_pending_catalog_value(): void
    {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->role(Role::Admin)->create();
        $item = CatalogItem::create(['type' => 'crop', 'slug' => 'custom_abc', 'label_fa' => 'محصول نمونه', 'status' => 'pending', 'source' => 'user']);

        Livewire::actingAs($admin)->test(CatalogManager::class)->call('startReview', $item->id)
            ->set('slug', 'sample_crop')->call('approve')->assertHasNoErrors();
        $this->assertDatabaseHas('catalog_items', ['id' => $item->id, 'slug' => 'sample_crop', 'status' => 'approved', 'reviewed_by' => $admin->id]);
    }

    public function test_manager_must_choose_final_quantity_for_equipment_conflict(): void
    {
        $this->withoutVite();
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $admin = User::factory()->role(Role::Admin)->create();
        $source = CatalogItem::create(['type' => 'equipment', 'slug' => 'custom_fan', 'label_fa' => 'فن ویژه', 'status' => 'pending', 'source' => 'user']);
        $target = CatalogItem::where('type', 'equipment')->where('slug', 'exhaust_fan')->firstOrFail();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $zone = Zone::create(['greenhouse_id' => $greenhouse->id, 'name' => 'سالن']);
        DB::table('zone_equipment')->insert([
            ['zone_id' => $zone->id, 'equipment_id' => $source->id, 'quantity' => 2, 'notes' => 'اول'],
            ['zone_id' => $zone->id, 'equipment_id' => $target->id, 'quantity' => 3, 'notes' => 'دوم'],
        ]);

        $component = Livewire::actingAs($admin)->test(CatalogManager::class)->call('startMerge', $source->id)
            ->set('candidateId', (string) $target->id)->call('inspectMerge')->assertSee('سالن');
        $component->call('merge')->assertHasErrors('equipment');
        $component->set('quantities.'.$zone->id, '4')->call('merge')->assertHasNoErrors();
        $this->assertDatabaseHas('zone_equipment', ['zone_id' => $zone->id, 'equipment_id' => $target->id, 'quantity' => 4, 'notes' => "اول\nدوم"]);
    }

    public function test_non_manager_cannot_access_catalog_review(): void
    {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $expert = User::factory()->role(Role::Expert)->create();
        $this->actingAs($expert)->get(route('admin.catalog'))->assertForbidden();
    }
}
