<?php

namespace Tests\Feature\Data;

use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Models\Zone;
use App\Services\CultivationCycleService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CultivationCycleServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_record_and_finish_a_cycle_but_not_edit_it_afterwards(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $this->travelTo(now()->setDate(2026, 11, 26));
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $crop = CatalogItem::where('type', 'crop')->firstOrFail();
        $service = app(CultivationCycleService::class);
        $data = ['crop_id' => $crop->id, 'planting_date' => '2026-01-01'];

        $cycle = $service->save($greenhouse, $owner, $data);
        $this->assertSame('growing', $cycle->status);
        $service->finish($greenhouse, $cycle, $owner, '2026-11-20', '100.50');
        $this->assertDatabaseHas('cultivation_cycles', ['id' => $cycle->id, 'status' => 'completed']);
        $this->assertSame('2026-11-20', $cycle->fresh()->harvest_ended_on->format('Y-m-d'));

        $this->expectException(AuthorizationException::class);
        $service->save($greenhouse, $owner, $data, $cycle);
    }

    public function test_zone_from_another_greenhouse_is_rejected(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $first = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'اول']);
        $second = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'دوم']);
        $zone = Zone::create(['greenhouse_id' => $second->id, 'name' => 'ناحیه']);
        $crop = CatalogItem::where('type', 'crop')->firstOrFail();

        $this->expectException(ValidationException::class);
        app(CultivationCycleService::class)->save($first, $owner, ['crop_id' => $crop->id, 'zone_id' => $zone->id, 'planting_date' => '2026-01-01']);
    }
}
