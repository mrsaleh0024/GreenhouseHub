<?php

namespace Tests\Feature\Data;

use App\Enums\Role;
use App\Models\Activity;
use App\Services\ActivityLogger;
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
        $this->assertSame($admin->id, $cycle->fresh()->updated_by);

        $logs = Activity::where('subject_type', 'cultivation_cycle')
            ->where('subject_id', $cycle->id)
            ->where('event', 'updated')
            ->where('actor_id', $admin->id)
            ->get();

        foreach ([
            'crop_id' => [$source->id, $target->id],
            'variety_id' => [$first->id, $second->id],
        ] as $column => [$old, $new]) {
            $this->assertTrue(
                $logs->contains(fn (Activity $log): bool =>
                    ($log->changes[$column]['old'] ?? null) === $old
                    && ($log->changes[$column]['new'] ?? null) === $new
                ),
                "Missing catalog audit for {$column}.",
            );
        }
    }

    public function test_option_merge_records_each_affected_greenhouse(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->role(Role::Admin)->create();
        $customer = Customer::create([
            'full_name' => 'Audit customer',
            'mobile' => '09120000001',
        ]);

        $first = Greenhouse::create([
            'customer_id' => $customer->id,
            'name' => 'First greenhouse',
        ]);

        $second = Greenhouse::create([
            'customer_id' => $customer->id,
            'name' => 'Second greenhouse',
        ]);

        $target = CatalogItem::create([
            'type' => 'irrigation_type',
            'slug' => 'audit_target',
            'label_fa' => 'Target irrigation',
            'status' => 'approved',
            'source' => 'system',
        ]);

        $source = CatalogItem::create([
            'type' => 'irrigation_type',
            'slug' => 'audit_source',
            'label_fa' => 'Source irrigation',
            'status' => 'pending',
            'source' => 'user',
        ]);

        DB::table('greenhouse_options')->insert([
            [
                'greenhouse_id' => $first->id,
                'catalog_item_id' => $source->id,
            ],
            [
                'greenhouse_id' => $second->id,
                'catalog_item_id' => $source->id,
            ],
            [
                'greenhouse_id' => $second->id,
                'catalog_item_id' => $target->id,
            ],
        ]);

        app(CatalogService::class)->merge($source, $target, $admin);

        $this->assertDatabaseCount('greenhouse_options', 2);

        foreach ([$first, $second] as $greenhouse) {
            $this->assertDatabaseHas('greenhouse_options', [
                'greenhouse_id' => $greenhouse->id,
                'catalog_item_id' => $target->id,
            ]);

            $this->assertDatabaseMissing('greenhouse_options', [
                'greenhouse_id' => $greenhouse->id,
                'catalog_item_id' => $source->id,
            ]);

            $activity = Activity::where('subject_type', 'greenhouse')
                ->where('subject_id', $greenhouse->id)
                ->where('event', 'options_changed')
                ->where('actor_id', $admin->id)
                ->sole();

            $before = $activity->changes['options']['old']['irrigation_type'];
            $after = $activity->changes['options']['new']['irrigation_type'];

            $this->assertContains($source->id, $before);
            $this->assertSame([$target->id], $after);
            $this->assertCount(
                $greenhouse->is($first) ? 1 : 2,
                $before,
            );
        }
    }

    public function test_nonconflicting_equipment_merge_has_a_safe_audit(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->role(Role::Admin)->create();

        $customer = Customer::create([
            'full_name' => 'Audit customer',
            'mobile' => '09120000002',
        ]);

        $greenhouse = Greenhouse::create([
            'customer_id' => $customer->id,
            'name' => 'Audit greenhouse',
        ]);

        $zone = Zone::create([
            'greenhouse_id' => $greenhouse->id,
            'name' => 'Audit zone',
        ]);

        $target = CatalogItem::create([
            'type' => 'equipment',
            'slug' => 'audit_target_equipment',
            'label_fa' => 'Target equipment',
            'status' => 'approved',
            'source' => 'system',
        ]);

        $source = CatalogItem::create([
            'type' => 'equipment',
            'slug' => 'audit_source_equipment',
            'label_fa' => 'Source equipment',
            'status' => 'pending',
            'source' => 'user',
        ]);

        DB::table('zone_equipment')->insert([
            'zone_id' => $zone->id,
            'equipment_id' => $source->id,
            'quantity' => 4,
            'notes' => 'PRIVATE-EQUIPMENT-NOTE',
        ]);

        app(CatalogService::class)->merge($source, $target, $admin);

        $this->assertDatabaseHas('zone_equipment', [
            'zone_id' => $zone->id,
            'equipment_id' => $target->id,
            'quantity' => 4,
            'notes' => 'PRIVATE-EQUIPMENT-NOTE',
        ]);

        $activity = Activity::where('subject_type', 'zone')
            ->where('subject_id', $zone->id)
            ->where('event', 'equipment_changed')
            ->where('actor_id', $admin->id)
            ->sole();

        $old = $activity->changes['equipment']['old'][0];
        $new = $activity->changes['equipment']['new'][0];

        $this->assertSame($source->id, $old['equipment_id']);
        $this->assertSame($target->id, $new['equipment_id']);
        $this->assertSame(4, $new['quantity']);
        $this->assertTrue($new['notes']['redacted']);

        $this->assertStringNotContainsString(
            'PRIVATE-EQUIPMENT-NOTE',
            json_encode($activity->changes),
        );
    }

    public function test_catalog_merge_rolls_back_when_audit_fails(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->role(Role::Admin)->create();

        $customer = Customer::create([
            'full_name' => 'Rollback customer',
            'mobile' => '09120000003',
        ]);

        $greenhouse = Greenhouse::create([
            'customer_id' => $customer->id,
            'name' => 'Rollback greenhouse',
        ]);

        $target = CatalogItem::create([
            'type' => 'irrigation_type',
            'slug' => 'rollback_target',
            'label_fa' => 'Rollback target',
            'status' => 'approved',
            'source' => 'system',
        ]);

        $source = CatalogItem::create([
            'type' => 'irrigation_type',
            'slug' => 'rollback_source',
            'label_fa' => 'Rollback source',
            'status' => 'pending',
            'source' => 'user',
        ]);

        DB::table('greenhouse_options')->insert([
            'greenhouse_id' => $greenhouse->id,
            'catalog_item_id' => $source->id,
        ]);

        $before = Activity::count();

        $this->mock(ActivityLogger::class)
            ->shouldReceive('log')
            ->once()
            ->andThrow(new \RuntimeException('merge-audit-failed'));

        try {
            app(CatalogService::class)->merge(
                $source,
                $target,
                $admin,
            );

            $this->fail('Expected audit failure.');
        } catch (\RuntimeException $exception) {
            $this->assertSame(
                'merge-audit-failed',
                $exception->getMessage(),
            );
        }

        $this->assertDatabaseHas('greenhouse_options', [
            'greenhouse_id' => $greenhouse->id,
            'catalog_item_id' => $source->id,
        ]);

        $this->assertDatabaseMissing('greenhouse_options', [
            'greenhouse_id' => $greenhouse->id,
            'catalog_item_id' => $target->id,
        ]);

        $this->assertSame('pending', $source->fresh()->status);
        $this->assertSame($before, Activity::count());
    }
}
