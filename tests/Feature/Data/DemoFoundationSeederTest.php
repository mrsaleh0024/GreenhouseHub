<?php

namespace Tests\Feature\Data;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\DemoFoundationSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoFoundationSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_owner_receives_a_linked_greenhouse_and_repeat_seeding_is_safe(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create(['mobile' => '09000000004', 'name' => 'گلخانه‌دار آزمایشی']);

        $this->seed(DemoFoundationSeeder::class);
        $this->seed(DemoFoundationSeeder::class);

        $customer = $owner->customer();
        $this->assertNotNull($customer);
        $this->assertSame(1, $customer->greenhouses()->count());
        $greenhouse = $customer->greenhouses()->firstOrFail();
        $this->assertSame(2, $greenhouse->zones()->count());
        $this->assertSame(1, $greenhouse->cycles()->where('status', 'growing')->count());
    }

    public function test_existing_customer_is_not_taken_over(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create(['mobile' => '09000000004', 'name' => 'گلخانه‌دار آزمایشی']);
        $customer = Customer::create(['mobile' => $owner->mobile, 'full_name' => 'مشتری موجود']);

        $this->seed(DemoFoundationSeeder::class);

        $this->assertFalse($owner->customers()->exists());
        $this->assertSame('مشتری موجود', $customer->fresh()->full_name);
        $this->assertSame(0, $customer->greenhouses()->count());
    }
}
