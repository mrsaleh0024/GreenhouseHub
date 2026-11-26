<?php

namespace Tests\Feature\Data;

use App\Enums\Role;
use App\Models\Activity;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Services\ZoneService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ZoneServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_a_zone_and_update_its_equipment(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $equipment = CatalogItem::where('type', 'equipment')->firstOrFail();
        $service = app(ZoneService::class);
        $this->actingAs($owner);

        $zone = $service->save($greenhouse, $owner, ['name' => 'سالن یک', 'capacity_plants' => 1200]);
        $this->assertSame('Z01', $zone->code);
        $service->syncEquipment($zone, $owner, [['equipment_id' => $equipment->id, 'quantity' => 2]]);
        $service->syncEquipment($zone, $owner, [['equipment_id' => $equipment->id, 'quantity' => 2]]);
        $this->assertDatabaseHas('zone_equipment', ['zone_id' => $zone->id, 'equipment_id' => $equipment->id, 'quantity' => 2]);
        $this->assertSame(1, Activity::where('subject_type', 'zone')->where('subject_id', $zone->id)->where('event', 'equipment_changed')->count());
    }

    public function test_invalid_equipment_cannot_be_saved(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $admin = User::factory()->role(Role::Admin)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $service = app(ZoneService::class);
        $zone = $service->save($greenhouse, $admin, ['name' => 'سالن یک']);
        $crop = CatalogItem::where('type', 'crop')->firstOrFail();

        $this->expectException(ValidationException::class);
        $service->syncEquipment($zone, $admin, [['equipment_id' => $crop->id, 'quantity' => 0]]);
    }
}
