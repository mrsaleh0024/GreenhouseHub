<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\TwoFactorChallenge;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuth();
    }

    public function test_login_page_renders(): void
    {
        $this->get(route('login'))->assertOk()->assertSeeLivewire(Login::class);
    }

    public function test_greenhouse_owner_and_expert_log_in_with_mobile_and_password(): void
    {
        foreach ([Role::GreenhouseOwner, Role::Expert] as $role) {
            $user = $this->userWithRole($role);

            Livewire::test(Login::class)
                ->set('mobile', $user->mobile)
                ->set('password', 'Password1')
                ->call('login')
                ->assertHasNoErrors()
                ->assertRedirect(route('dashboard'));

            $this->assertAuthenticatedAs($user);
            auth()->logout();
        }
    }

    public function test_persian_digits_in_the_mobile_are_accepted(): void
    {
        $user = $this->userWithRole(Role::Expert, ['mobile' => '09123456789']);

        Livewire::test(Login::class)
            ->set('mobile', '۰۹۱۲۳۴۵۶۷۸۹')
            ->set('password', 'Password1')
            ->call('login')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_and_unknown_mobile_give_the_same_error(): void
    {
        $user = $this->userWithRole(Role::Expert);

        $wrong = Livewire::test(Login::class)
            ->set('mobile', $user->mobile)
            ->set('password', 'WrongPass1')
            ->call('login');

        $unknown = Livewire::test(Login::class)
            ->set('mobile', '09129999999')
            ->set('password', 'WrongPass1')
            ->call('login');

        $this->assertGuest();
        $this->assertSame($wrong->errors()->first('mobile'), $unknown->errors()->first('mobile'));
    }

    public function test_inactive_account_cannot_log_in(): void
    {
        $user = $this->userWithRole(Role::Expert, ['status' => 'inactive']);

        Livewire::test(Login::class)
            ->set('mobile', $user->mobile)
            ->set('password', 'Password1')
            ->call('login')
            ->assertHasErrors('mobile');

        $this->assertGuest();
    }

    public function test_account_without_a_password_cannot_log_in(): void
    {
        $user = User::factory()->withoutPassword()->role(Role::Admin)->create();

        Livewire::test(Login::class)
            ->set('mobile', $user->mobile)
            ->set('password', '')
            ->call('login')
            ->assertHasErrors('password');

        $this->assertGuest();
    }

    public function test_unconfirmed_mobile_is_sent_to_verification_instead_of_logging_in(): void
    {
        $user = User::factory()->unverified()->role(Role::GreenhouseOwner)->create();

        Livewire::test(Login::class)
            ->set('mobile', $user->mobile)
            ->set('password', 'Password1')
            ->call('login')
            ->assertRedirect(route('verify-mobile'));

        $this->assertGuest();
        $this->assertSame($user->mobile, session('pending_mobile'));
        $this->assertNotNull($this->sms->lastCode($user->mobile));
    }

    public function test_login_is_rate_limited(): void
    {
        $user = $this->userWithRole(Role::Expert);

        $component = Livewire::test(Login::class)->set('mobile', $user->mobile);

        for ($i = 0; $i < 5; $i++) {
            $component->set('password', 'WrongPass1')->call('login');
        }

        // Even the right password is refused while locked out.
        $component->set('password', 'Password1')->call('login')->assertHasErrors('mobile');

        $this->assertGuest();
    }

    /** @return array<string, array{Role}> */
    public static function adminRoles(): array
    {
        return ['admin' => [Role::Admin], 'super admin' => [Role::SuperAdmin]];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('adminRoles')]
    public function test_admin_level_accounts_are_not_logged_in_by_the_password_alone(Role $role): void
    {
        $user = $this->userWithRole($role);

        Livewire::test(Login::class)
            ->set('mobile', $user->mobile)
            ->set('password', 'Password1')
            ->call('login')
            ->assertRedirect(route('two-factor'));

        $this->assertGuest();
        $this->assertNotNull($this->sms->lastCode($user->mobile));
    }

    public function test_two_factor_code_completes_the_admin_login(): void
    {
        $user = $this->userWithRole(Role::Admin);

        Livewire::test(Login::class)
            ->set('mobile', $user->mobile)
            ->set('password', 'Password1')
            ->call('login');

        Livewire::test(TwoFactorChallenge::class)
            ->set('code', $this->sms->lastCode($user->mobile))
            ->call('verify')
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_two_factor_code_does_not_log_in(): void
    {
        $user = $this->userWithRole(Role::SuperAdmin);

        Livewire::test(Login::class)
            ->set('mobile', $user->mobile)
            ->set('password', 'Password1')
            ->call('login');

        $real = $this->sms->lastCode($user->mobile);

        Livewire::test(TwoFactorChallenge::class)
            ->set('code', $real === '000000' ? '111111' : '000000')
            ->call('verify')
            ->assertHasErrors('code');

        $this->assertGuest();
    }

    public function test_two_factor_page_requires_a_started_login(): void
    {
        $this->get(route('two-factor'))->assertRedirect(route('login'));
    }

    public function test_two_factor_state_expires(): void
    {
        $user = $this->userWithRole(Role::Admin);

        Livewire::test(Login::class)
            ->set('mobile', $user->mobile)
            ->set('password', 'Password1')
            ->call('login');

        $this->travel(11)->minutes();

        Livewire::test(TwoFactorChallenge::class)->assertRedirect(route('login'));
    }

    public function test_logout_clears_the_session(): void
    {
        $user = $this->userWithRole(Role::Expert);

        $this->actingAs($user)->post(route('logout'))->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_authenticated_users_are_redirected_away_from_login(): void
    {
        $this->actingAs($this->userWithRole(Role::Expert))
            ->get(route('login'))
            ->assertRedirect(route('dashboard'));
    }
}
