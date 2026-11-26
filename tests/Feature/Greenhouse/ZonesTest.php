<?php

namespace Tests\Feature\Greenhouse;

use App\Enums\Role;
use App\Livewire\Greenhouse\Zones;
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

class ZonesTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_add_a_zone_and_its_equipment(): void
    {
        $this->withoutVite();
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $equipment = CatalogItem::where('type', 'equipment')->firstOrFail();

        $component = Livewire::actingAs($owner)->test(Zones::class, ['greenhouse' => $greenhouse])
            ->call('startNew')->set('name', 'سالن یک')->set('capacity_plants', '400')
            ->call('save')->assertHasNoErrors()->assertSee('اطلاعات بخش ذخیره شد');
        $zone = Zone::where('greenhouse_id', $greenhouse->id)->firstOrFail();
        $component->call('startEquipment', $zone->id)->set('equipmentIds', [(string) $equipment->id])
            ->set('equipmentQuantities.'.$equipment->id, '2')->call('saveEquipment')->assertHasNoErrors();
        $this->assertDatabaseHas('zone_equipment', ['zone_id' => $zone->id, 'equipment_id' => $equipment->id, 'quantity' => 2]);
        DB::table('zone_equipment')->where('zone_id', $zone->id)->update(['notes' => 'یادداشت قبلی']);
        $component->call('startEquipment', $zone->id)->set('equipmentQuantities.'.$equipment->id, '3')
            ->call('saveEquipment')->assertHasNoErrors();
        $this->assertDatabaseHas('zone_equipment', ['zone_id' => $zone->id, 'equipment_id' => $equipment->id, 'quantity' => 3, 'notes' => 'یادداشت قبلی']);
    }

    public function test_expert_can_view_zones_but_cannot_start_an_edit(): void
    {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $expert = User::factory()->role(Role::Expert)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $zone = Zone::create(['greenhouse_id' => $greenhouse->id, 'name' => 'سالن']);

        $this->actingAs($expert)->get(route('greenhouses.zones', $greenhouse))->assertOk()->assertSee('سالن')->assertDontSee('افزودن بخش');
        Livewire::actingAs($expert)->test(Zones::class, ['greenhouse' => $greenhouse])->call('startEdit', $zone->id)->assertForbidden();
    }
}
