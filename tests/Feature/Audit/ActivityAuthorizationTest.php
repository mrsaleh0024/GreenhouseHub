<?php

namespace Tests\Feature\Audit;

use App\Enums\Role;
use App\Models\Activity;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\User;
use App\Services\ActivityLogger;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_access_is_role_and_ownership_restricted(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->role(Role::Admin)->create();
        $superAdmin = User::factory()->role(Role::SuperAdmin)->create();
        $expert = User::factory()->role(Role::Expert)->create();
        $ownerA = User::factory()->role(Role::GreenhouseOwner)->create();
        $ownerB = User::factory()->role(Role::GreenhouseOwner)->create();
        $ownerWithoutLink = User::factory()->role(Role::GreenhouseOwner)->create();

        $customerA = Customer::create(['full_name' => 'مشتری الف', 'mobile' => '09120000001']);
        $customerA->users()->attach($ownerA);
        $customerB = Customer::create(['full_name' => 'مشتری ب', 'mobile' => '09120000002']);
        $customerB->users()->attach($ownerB);

        $activityA = app(ActivityLogger::class)->log($customerA, 'created', actor: $admin);
        $activityB = app(ActivityLogger::class)->log($customerB, 'created', actor: $admin);
        $orphan = app(ActivityLogger::class)->log(
            CatalogItem::create(['type' => 'province', 'slug' => 'test-province', 'label_fa' => 'Test Province']),
            'created',
            actor: $admin,
        );

        $this->assertTrue($admin->can('viewAny', Activity::class));
        $this->assertTrue($admin->can('view', $activityA));
        $this->assertTrue($admin->can('view', $activityB));
        $this->assertTrue($admin->can('view', $orphan));

        $this->assertTrue($ownerA->can('viewAny', Activity::class));
        $this->assertTrue($ownerA->can('view', $activityA));
        $this->assertFalse($ownerA->can('view', $activityB));
        $this->assertFalse($ownerA->can('view', $orphan));
        $this->assertFalse($ownerA->can('create', Activity::class));
        $this->assertFalse($ownerA->can('update', $activityA));
        $this->assertFalse($ownerA->can('delete', $activityA));

        $this->assertTrue($ownerB->can('view', $activityB));
        $this->assertFalse($ownerB->can('view', $activityA));

        $this->assertTrue($ownerWithoutLink->can('viewAny', Activity::class));
        $this->assertFalse($ownerWithoutLink->can('view', $activityA));
        $this->assertSame(0, Activity::visibleTo($ownerWithoutLink)->count());

        $this->assertFalse($expert->can('viewAny', Activity::class));
        $this->assertFalse($expert->can('view', $activityA));
        $this->assertSame(0, Activity::visibleTo($expert)->count());

        $this->assertTrue($superAdmin->can('viewAny', Activity::class));
        $this->assertTrue($superAdmin->can('view', $activityA));
        $this->assertTrue($superAdmin->can('view', $activityB));

        foreach ([$admin, $superAdmin, $expert, $ownerA, $ownerB, $ownerWithoutLink] as $user) {
            $this->assertFalse($user->can('create', Activity::class));

            foreach ([
                'update',
                'delete',
                'forceDelete',
                'restore',
                'changeStatus',
                'updateContact',
                'reopen',
                'viewInternalNotes',
            ] as $ability) {
                $this->assertFalse(
                    $user->can($ability, $activityA),
                    "Audit ability [{$ability}] must be denied.",
                );
            }
        }

        $ownerAVisible = Activity::visibleTo($ownerA)->pluck('id')->all();
        $this->assertContains($activityA->id, $ownerAVisible);
        $this->assertNotContains($activityB->id, $ownerAVisible);
        $this->assertNotContains($orphan->id, $ownerAVisible);

        $adminVisible = Activity::visibleTo($admin)->pluck('id')->all();
        $this->assertContains($activityA->id, $adminVisible);
        $this->assertContains($activityB->id, $adminVisible);
        $this->assertContains($orphan->id, $adminVisible);
    }
}
