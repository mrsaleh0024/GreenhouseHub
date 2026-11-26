<?php

namespace Tests\Feature\Authorization;

use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\CultivationCycle;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerRecordPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_edit_only_their_own_open_cycle_and_greenhouse(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $other = User::factory()->role(Role::GreenhouseOwner)->create();
        $admin = User::factory()->role(Role::Admin)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $crop = CatalogItem::where('type', 'crop')->firstOrFail();
        $cycle = CultivationCycle::create(['greenhouse_id' => $greenhouse->id, 'crop_id' => $crop->id, 'planting_date' => '2025-01-01']);

        $this->assertTrue($owner->can('update', $greenhouse));
        $this->assertFalse($other->can('update', $greenhouse));
        $this->assertTrue($owner->can('update', $cycle));
        $this->assertFalse($other->can('update', $cycle));

        $cycle->update(['harvest_ended_on' => '2025-10-01']);
        $this->assertFalse($owner->can('update', $cycle->fresh()));
        $this->assertTrue($admin->can('update', $cycle->fresh()));
    }
}
