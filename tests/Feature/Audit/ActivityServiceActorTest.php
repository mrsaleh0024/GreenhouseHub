<?php

namespace Tests\Feature\Audit;

use App\Enums\Role;
use App\Models\Activity;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Services\CorrectionRequestService;
use App\Services\CustomerLinker;
use App\Services\GreenhouseService;
use App\Services\ZoneService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityServiceActorTest extends TestCase
{
    use RefreshDatabase;

    public function test_equipment_and_options_record_the_explicit_actor(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $admin = User::factory()->role(Role::Admin)->create();

        $customer = Customer::create([
            'full_name' => 'Audit test customer',
            'mobile' => '09120000001',
        ]);

        $customer->users()->attach($owner);

        $greenhouse = Greenhouse::create([
            'customer_id' => $customer->id,
            'name' => 'Audit test greenhouse',
        ]);

        $zone = app(ZoneService::class)->save(
            $greenhouse,
            $owner,
            ['name' => 'Audit test zone'],
        );

        $equipment = CatalogItem::where('type', 'equipment')
            ->where('status', 'approved')
            ->firstOrFail();

        $option = CatalogItem::where('type', 'irrigation_type')
            ->where('status', 'approved')
            ->firstOrFail();

        $this->assertGuest();

        app(ZoneService::class)->syncEquipment(
            $zone,
            $owner,
            [
                [
                    'equipment_id' => $equipment->id,
                    'quantity' => 2,
                    'notes' => 'PRIVATE-NOTE',
                ],
            ],
        );

        $equipmentLog = Activity::query()
            ->where('subject_type', 'zone')
            ->where('subject_id', $zone->id)
            ->where('event', 'equipment_changed')
            ->sole();

        $this->assertSame($owner->id, $equipmentLog->actor_id);
        $this->assertSame(Role::GreenhouseOwner->value, $equipmentLog->actor_role);

        // The authenticated user differs from the service actor.
        $this->actingAs($admin);

        app(GreenhouseService::class)->syncOptions(
            $greenhouse,
            ['irrigation_type' => [$option->id]],
            $owner,
        );

        $optionsLog = Activity::query()
            ->where('subject_type', 'greenhouse')
            ->where('subject_id', $greenhouse->id)
            ->where('event', 'options_changed')
            ->sole();

        $this->assertSame($owner->id, $optionsLog->actor_id);
        $this->assertSame(Role::GreenhouseOwner->value, $optionsLog->actor_role);

        $this->assertSame(
            [$option->id],
            $optionsLog->getAttribute('changes')['options']['new']['irrigation_type'],
        );

        $this->assertAuthenticatedAs($admin);
    }

    public function test_correction_events_record_the_correct_explicit_actors(): void
    {
        $this->seed(RoleSeeder::class);

        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $admin = User::factory()->role(Role::Admin)->create();

        $customer = Customer::create([
            'full_name' => 'Audit test customer',
            'mobile' => '09120000002',
        ]);

        $customer->users()->attach($owner);

        $greenhouse = Greenhouse::create([
            'customer_id' => $customer->id,
            'name' => 'Audit test greenhouse',
        ]);

        $this->actingAs($admin);

        $request = app(CorrectionRequestService::class)->request(
            $owner,
            $greenhouse,
            'Private correction details',
        );

        $requested = Activity::query()
            ->where('subject_type', 'greenhouse')
            ->where('subject_id', $greenhouse->id)
            ->where('event', 'correction_requested')
            ->sole();

        $this->assertSame($owner->id, $requested->actor_id);
        $this->assertSame(Role::GreenhouseOwner->value, $requested->actor_role);

        $this->actingAs($owner);

        app(CorrectionRequestService::class)->resolve(
            $admin,
            $request,
            'Private resolution details',
        );

        $resolved = Activity::query()
            ->where('subject_type', 'greenhouse')
            ->where('subject_id', $greenhouse->id)
            ->where('event', 'correction_resolved')
            ->sole();

        $this->assertSame($admin->id, $resolved->actor_id);
        $this->assertSame(Role::Admin->value, $resolved->actor_role);

        $this->assertAuthenticatedAs($owner);
    }

    public function test_customer_link_is_logged_with_the_verified_owner_as_actor(): void
    {
        $this->seed(RoleSeeder::class);

        $owner = User::factory()->role(Role::GreenhouseOwner)->create([
            'mobile_verified_at' => now(),
            'status' => 'active',
        ]);

        $customer = Customer::create([
            'full_name' => 'Audit test customer',
            'mobile' => $owner->mobile,
        ]);

        $this->assertGuest();

        app(CustomerLinker::class)->linkUserToCustomer($owner);
        app(CustomerLinker::class)->linkUserToCustomer($owner);

        $activity = Activity::query()
            ->where('subject_type', 'customer')
            ->where('subject_id', $customer->id)
            ->where('event', 'user_linked')
            ->sole();

        $this->assertSame($owner->id, $activity->actor_id);
        $this->assertSame(Role::GreenhouseOwner->value, $activity->actor_role);
        $this->assertSame($customer->id, $activity->customer_id);

        $this->assertDatabaseHas('customer_user', [
            'customer_id' => $customer->id,
            'user_id' => $owner->id,
        ]);

        $this->assertNull($activity->getAttribute('changes'));
    }
}
