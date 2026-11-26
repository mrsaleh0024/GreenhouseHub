<?php

namespace Tests\Feature\Authorization;

use App\Enums\Role;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Services\GreenhouseService;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpertAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_expert_can_view_but_cannot_change_a_greenhouse_or_customer(): void
    {
        $this->seed(RoleSeeder::class);
        $expert = User::factory()->role(Role::Expert)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);

        $this->assertTrue($expert->can('view', $greenhouse));
        $this->assertFalse($expert->can('create', Greenhouse::class));
        $this->assertFalse($expert->can('update', $greenhouse));
        $this->assertFalse($expert->can('delete', $greenhouse));
        $this->assertFalse($expert->can('update', $customer));
        $this->assertFalse($expert->can('updateContact', $customer));
        $this->assertFalse($expert->can('view', $customer));
        $this->assertFalse($expert->can('viewAny', Customer::class));
        $this->assertFalse(Customer::visibleTo($expert)->whereKey($customer)->exists());
        $this->assertFalse($expert->can('viewAny', Activity::class));
        $this->assertFalse($expert->can('viewInternalNotes', $greenhouse));
    }

    public function test_expert_cannot_modify_greenhouse_options_through_the_service(): void
    {
        $this->seed(RoleSeeder::class);
        $expert = User::factory()->role(Role::Expert)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);

        $this->expectException(AuthorizationException::class);
        app(GreenhouseService::class)->syncOptions($greenhouse, [], $expert);
    }

    public function test_admin_and_super_admin_can_still_update_greenhouses(): void
    {
        $this->seed(RoleSeeder::class);
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);

        foreach ([Role::Admin, Role::SuperAdmin] as $role) {
            $user = User::factory()->role($role)->create();
            $this->assertTrue($user->can('update', $greenhouse));
            $this->assertTrue($user->can('updateContact', $customer));
        }
    }
}
