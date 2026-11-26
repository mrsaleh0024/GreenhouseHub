<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Greenhouse\Cultivation;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RoleWorkspacesTest extends TestCase
{
    use RefreshDatabase;

    public function test_each_role_has_its_own_workspace_and_managers_cannot_start_cultivation(): void
    {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        foreach ([Role::Admin, Role::SuperAdmin, Role::Expert, Role::GreenhouseOwner] as $role) {
            $user = User::factory()->role($role)->create();
            if ($role === Role::GreenhouseOwner) {
                $customer->users()->attach($user);
            }
            $view = match ($role) {
                Role::Admin, Role::SuperAdmin => 'dashboards.manager',
                Role::Expert => 'dashboards.expert',
                Role::GreenhouseOwner => 'dashboard',
            };
            $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertViewIs($view);
            if ($role !== Role::GreenhouseOwner) {
                Livewire::actingAs($user)->test(Cultivation::class, ['greenhouse' => $greenhouse])->call('startNew')->assertForbidden();
                Livewire::actingAs($user)->test(Cultivation::class, ['greenhouse' => $greenhouse])->call('startFinish', 0)->assertForbidden();
            }
        }
    }
}
