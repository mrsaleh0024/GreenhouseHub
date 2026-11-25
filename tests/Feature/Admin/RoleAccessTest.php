<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Livewire\Admin\AdminManager;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuth();
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('admin.admins'))->assertRedirect(route('login'));
    }

    public function test_every_role_reaches_the_shared_dashboard(): void
    {
        foreach (Role::cases() as $role) {
            $this->actingAs($this->userWithRole($role))->get(route('dashboard'))->assertOk();
        }
    }

    public function test_the_menu_depends_on_the_role(): void
    {
        $this->actingAs($this->userWithRole(Role::SuperAdmin))
            ->get(route('dashboard'))
            ->assertSee('مدیریت مدیران');

        foreach ([Role::Admin, Role::Expert, Role::GreenhouseOwner] as $role) {
            $this->actingAs($this->userWithRole($role))
                ->get(route('dashboard'))
                ->assertDontSee('مدیریت مدیران');
        }
    }

    public function test_only_the_super_admin_can_open_the_admin_manager(): void
    {
        foreach ([Role::Admin, Role::Expert, Role::GreenhouseOwner] as $role) {
            $this->actingAs($this->userWithRole($role))->get(route('admin.admins'))->assertForbidden();
        }

        $this->actingAs($this->userWithRole(Role::SuperAdmin))
            ->get(route('admin.admins'))
            ->assertOk()
            ->assertSeeLivewire(AdminManager::class);
    }

    public function test_the_super_admin_creates_an_admin_who_must_activate_the_account(): void
    {
        $super = $this->userWithRole(Role::SuperAdmin);

        Livewire::actingAs($super)
            ->test(AdminManager::class)
            ->set('name', 'مدیر جدید')
            ->set('mobile', '۰۹۱۲۲۲۲۲۲۲۲')
            ->call('create')
            ->assertHasNoErrors();

        $admin = User::where('mobile', '09122222222')->firstOrFail();

        $this->assertTrue($admin->hasRole(Role::Admin->value));
        $this->assertSame(UserStatus::Pending, $admin->status);
        $this->assertFalse($admin->hasPassword());
        $this->assertNull($admin->mobile_verified_at);
    }

    public function test_the_admin_manager_rejects_a_duplicate_mobile(): void
    {
        $super = $this->userWithRole(Role::SuperAdmin);
        $existing = $this->userWithRole(Role::Expert);

        Livewire::actingAs($super)
            ->test(AdminManager::class)
            ->set('name', 'مدیر جدید')
            ->set('mobile', $existing->mobile)
            ->call('create')
            ->assertHasErrors('mobile');
    }

    public function test_the_super_admin_can_deactivate_and_reactivate_an_admin(): void
    {
        $super = $this->userWithRole(Role::SuperAdmin);
        $admin = $this->userWithRole(Role::Admin);

        $component = Livewire::actingAs($super)->test(AdminManager::class);

        $component->call('toggleStatus', $admin->id);
        $this->assertSame(UserStatus::Inactive, $admin->fresh()->status);

        $component->call('toggleStatus', $admin->id);
        $this->assertSame(UserStatus::Active, $admin->fresh()->status);
    }

    public function test_super_admins_and_other_roles_cannot_be_toggled_from_the_admin_manager(): void
    {
        $super = $this->userWithRole(Role::SuperAdmin);
        $expert = $this->userWithRole(Role::Expert);

        $component = Livewire::actingAs($super)->test(AdminManager::class);

        // Whether Livewire surfaces the 404 or swallows it, the account must be untouched.
        try {
            $component->call('toggleStatus', $expert->id);
        } catch (\Throwable) {
            // expected: the lookup is scoped to the admin role
        }

        $this->assertSame(UserStatus::Active, $expert->fresh()->status);
        $this->assertTrue($super->fresh()->isActive());
    }

    public function test_a_deactivated_user_is_logged_out_on_the_next_request(): void
    {
        $user = $this->userWithRole(Role::Expert, ['status' => UserStatus::Inactive]);

        $this->actingAs($user)->get(route('dashboard'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_the_super_admin_passes_every_gate(): void
    {
        $this->assertTrue($this->userWithRole(Role::SuperAdmin)->can('anything'));
        $this->assertFalse($this->userWithRole(Role::Expert)->can('anything'));
    }
}
