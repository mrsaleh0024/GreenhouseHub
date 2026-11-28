<?php

namespace Tests\Feature\Audit;

use App\Enums\Role;
use App\Models\Activity;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CatalogService;
use App\Services\CultivationCycleService;
use App\Services\GreenhouseRecordService;
use App\Services\OwnerRegistrationService;
use App\Services\ZoneService;
use App\Support\AuditActorContext;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class AutomaticActorTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_context_restores_previous_actor_even_after_exception(): void
    {
        $admin = User::factory()->create();
        $owner = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($admin);

        $context = app(AuditActorContext::class);

        $context->run($owner, function () use ($context, $owner, $other, $admin) {
            $this->assertSame($owner->id, $context->current()->id);

            try {
                $context->run($other, function () use ($context, $other) {
                    $this->assertSame($other->id, $context->current()->id);

                    throw new RuntimeException('test-context');
                });

                $this->fail('Expected context exception.');
            } catch (RuntimeException $exception) {
                $this->assertSame('test-context', $exception->getMessage());
            }

            $this->assertSame($owner->id, $context->current()->id);
            $this->assertAuthenticatedAs($admin);
        });

        $this->assertSame($admin->id, $context->current()->id);
    }

    public function test_automatic_events_use_the_service_actor(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $admin = User::factory()->role(Role::Admin)->create();

        $customer = Customer::create([
            'full_name' => 'Audit customer',
            'mobile' => '09120000001',
        ]);

        $customer->users()->attach($owner);

        $greenhouse = Greenhouse::create([
            'customer_id' => $customer->id,
            'name' => 'Audit greenhouse',
        ]);

        $this->actingAs($admin);

        $zone = app(ZoneService::class)->save(
            $greenhouse,
            $owner,
            ['name' => 'Audit zone'],
        );

        $this->assertSame($owner->id, $zone->fresh()->created_by);
        $this->assertSame($owner->id, $zone->fresh()->updated_by);

        $this->assertDatabaseHas('activities', [
            'subject_type' => 'zone',
            'subject_id' => $zone->id,
            'event' => 'created',
            'actor_id' => $owner->id,
        ]);

        app(GreenhouseRecordService::class)->saveBasics(
            $greenhouse,
            $owner,
            ['name' => 'Updated greenhouse', 'area_m2' => 500],
        );

        $this->assertSame($owner->id, $greenhouse->fresh()->updated_by);

        $this->assertDatabaseHas('activities', [
            'subject_type' => 'greenhouse',
            'subject_id' => $greenhouse->id,
            'event' => 'updated',
            'actor_id' => $owner->id,
        ]);

        $crop = CatalogItem::where('type', 'crop')
            ->where('status', 'approved')
            ->firstOrFail();

        $cycle = app(CultivationCycleService::class)->save(
            $greenhouse,
            $owner,
            [
                'zone_id' => $zone->id,
                'crop_id' => $crop->id,
                'planting_date' => now()->toDateString(),
            ],
        );

        $this->assertSame($owner->id, $cycle->fresh()->created_by);

        $this->assertDatabaseHas('activities', [
            'subject_type' => 'cultivation_cycle',
            'subject_id' => $cycle->id,
            'event' => 'created',
            'actor_id' => $owner->id,
        ]);

        app(CultivationCycleService::class)->finish(
            $greenhouse,
            $cycle,
            $owner,
            now()->toDateString(),
            '10',
        );

        $this->assertDatabaseHas('activities', [
            'subject_type' => 'cultivation_cycle',
            'subject_id' => $cycle->id,
            'event' => 'status_changed',
            'actor_id' => $owner->id,
        ]);

        $this->assertAuthenticatedAs($admin);
    }

    public function test_catalog_events_use_the_explicit_admin(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->role(Role::Admin)->create();
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();

        $item = CatalogItem::create([
            'type' => 'equipment',
            'slug' => 'audit_test_equipment',
            'label_fa' => 'Audit equipment',
            'status' => 'pending',
            'source' => 'user',
        ]);

        $this->actingAs($owner);

        app(CatalogService::class)->approve($item, $admin);

        foreach (['status_changed', 'catalog_approved'] as $event) {
            $this->assertDatabaseHas('activities', [
                'subject_type' => 'catalog_item',
                'subject_id' => $item->id,
                'event' => $event,
                'actor_id' => $admin->id,
            ]);
        }

        $this->assertAuthenticatedAs($owner);
    }

    public function test_registration_records_the_initiating_user(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);

        $owner = User::factory()->role(Role::GreenhouseOwner)->create();

        $province = CatalogItem::where('type', 'province')->firstOrFail();

        $city = CatalogItem::where('type', 'city')
            ->where('parent_id', $province->id)
            ->firstOrFail();

        $this->assertGuest();

        $customer = app(OwnerRegistrationService::class)->register(
            $owner,
            [
                'province_id' => $province->id,
                'city_id' => $city->id,
                'greenhouse_name' => 'Audit registration',
            ],
        );

        $greenhouse = $customer->greenhouses()->sole();

        $this->assertSame($owner->id, $customer->fresh()->created_by);
        $this->assertSame($owner->id, $greenhouse->fresh()->created_by);

        $this->assertDatabaseHas('activities', [
            'subject_type' => 'customer',
            'subject_id' => $customer->id,
            'event' => 'created',
            'actor_id' => $owner->id,
        ]);

        $this->assertDatabaseHas('activities', [
            'subject_type' => 'greenhouse',
            'subject_id' => $greenhouse->id,
            'event' => 'created',
            'actor_id' => $owner->id,
        ]);

        $this->assertGuest();
    }

    public function test_greenhouse_update_rolls_back_when_audit_write_fails(): void
    {
        $this->seed(RoleSeeder::class);

        $owner = User::factory()->role(Role::GreenhouseOwner)->create();

        $customer = Customer::create([
            'full_name' => 'Audit rollback customer',
            'mobile' => '09120000002',
        ]);

        $customer->users()->attach($owner);

        $greenhouse = Greenhouse::create([
            'customer_id' => $customer->id,
            'name' => 'Original greenhouse',
            'area_m2' => 100,
        ]);

        $before = Activity::count();

        $this->mock(ActivityLogger::class)
            ->shouldReceive('log')
            ->once()
            ->andThrow(new RuntimeException('audit-write-failed'));

        try {
            app(GreenhouseRecordService::class)->saveBasics(
                $greenhouse,
                $owner,
                [
                    'name' => 'Must not persist',
                    'area_m2' => 200,
                ],
            );

            $this->fail('Expected audit write exception.');
        } catch (RuntimeException $exception) {
            $this->assertSame('audit-write-failed', $exception->getMessage());
        }

        $this->assertSame(
            'Original greenhouse',
            $greenhouse->fresh()->name,
        );

        $this->assertEquals(100, $greenhouse->fresh()->area_m2);
        $this->assertSame($before, Activity::count());
        $this->assertNull(app(AuditActorContext::class)->current());
    }
}
