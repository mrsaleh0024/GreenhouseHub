<?php

namespace Tests\Feature\Data;

use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\CultivationCycle;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Services\CorrectionRequestService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CorrectionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_requests_a_closed_cycle_correction_and_admin_resolves_it(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $admin = User::factory()->role(Role::Admin)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $crop = CatalogItem::where('type', 'crop')->firstOrFail();
        $cycle = CultivationCycle::create(['greenhouse_id' => $greenhouse->id, 'crop_id' => $crop->id, 'planting_date' => '2025-01-01', 'harvest_ended_on' => '2025-07-01']);

        $service = app(CorrectionRequestService::class);
        $this->actingAs($owner);
        $request = $service->request($owner, $greenhouse, 'تاریخ پایان نیاز به اصلاح دارد', $cycle);
        $this->assertDatabaseHas('correction_requests', ['id' => $request->id, 'requested_by' => $owner->id, 'status' => 'pending', 'cultivation_cycle_id' => $cycle->id]);

        $this->actingAs($admin);
        $service->resolve($admin, $request, 'درخواست بررسی شد');
        $this->assertDatabaseHas('correction_requests', ['id' => $request->id, 'status' => 'resolved', 'resolved_by' => $admin->id]);
        $this->assertSame('2025-07-01', $cycle->fresh()->harvest_ended_on->format('Y-m-d'));
    }

    public function test_owner_cannot_request_a_correction_for_another_owners_greenhouse(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری دیگر', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه دیگر']);

        $this->expectException(AuthorizationException::class);
        app(CorrectionRequestService::class)->request($owner, $greenhouse, 'درخواست اصلاح اطلاعات');
    }

    public function test_owner_cannot_submit_an_open_cycle_as_locked_history(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $cycle = CultivationCycle::create(['greenhouse_id' => $greenhouse->id, 'crop_id' => CatalogItem::where('type', 'crop')->firstOrFail()->id, 'planting_date' => '2025-01-01']);

        $this->expectException(ValidationException::class);
        app(CorrectionRequestService::class)->request($owner, $greenhouse, 'درخواست اصلاح اطلاعات', $cycle);
    }
}
