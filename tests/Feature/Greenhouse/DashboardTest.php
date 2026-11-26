<?php

namespace Tests\Feature\Greenhouse;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_only_sees_their_greenhouse_and_a_completion_link(): void
    {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه متعلق به من']);
        $otherCustomer = Customer::create(['full_name' => 'دیگری', 'mobile' => '09123456788']);
        Greenhouse::create(['customer_id' => $otherCustomer->id, 'name' => 'گلخانه خصوصی دیگر']);

        $this->actingAs($owner)->get(route('dashboard'))->assertOk()
            ->assertSee('گلخانه متعلق به من')->assertDontSee('گلخانه خصوصی دیگر')
            ->assertSee(route('greenhouses.complete', $greenhouse));
    }

    public function test_expert_dashboard_does_not_reveal_personal_details_or_edit_link(): void
    {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $expert = User::factory()->role(Role::Expert)->create();
        $customer = Customer::create(['full_name' => 'نام محرمانه', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه نمونه', 'address' => 'نشانی محرمانه']);

        $this->actingAs($expert)->get(route('dashboard'))->assertOk()->assertSee('گلخانه نمونه')
            ->assertDontSee('نام محرمانه')->assertDontSee('09123456789')->assertDontSee('نشانی محرمانه')
            ->assertDontSee(route('greenhouses.complete', $greenhouse));
    }
}
