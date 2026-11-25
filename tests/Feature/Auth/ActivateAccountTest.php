<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Livewire\Auth\ActivateAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class ActivateAccountTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuth();
    }

    public function test_an_admin_created_account_is_activated_through_otp_and_a_new_password(): void
    {
        $admin = User::factory()->withoutPassword()->role(Role::Admin)->create();

        $component = Livewire::test(ActivateAccount::class)
            ->set('mobile', $admin->mobile)
            ->call('sendCode')
            ->assertSet('step', 'code');

        $component
            ->set('code', $this->sms->lastCode($admin->mobile))
            ->call('verifyCode')
            ->assertSet('step', 'password');

        $component
            ->set('password', 'Password1')
            ->set('password_confirmation', 'Password1')
            ->call('setPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $admin->refresh();

        $this->assertSame(UserStatus::Active, $admin->status);
        $this->assertNotNull($admin->mobile_verified_at);
        $this->assertTrue(Hash::check('Password1', $admin->password));
        $this->assertGuest();
    }

    /** Regression: the old flow let anyone set a password without passing the OTP. */
    public function test_the_password_cannot_be_set_without_passing_the_otp(): void
    {
        $admin = User::factory()->withoutPassword()->role(Role::Admin)->create();

        Livewire::test(ActivateAccount::class)
            ->set('mobile', $admin->mobile)
            ->set('password', 'Password1')
            ->set('password_confirmation', 'Password1')
            ->call('setPassword')
            ->assertHasErrors('mobile');

        $this->assertFalse($admin->fresh()->hasPassword());
        $this->assertSame(UserStatus::Pending, $admin->fresh()->status);
    }

    public function test_a_wrong_otp_does_not_unlock_the_password_step(): void
    {
        $admin = User::factory()->withoutPassword()->role(Role::Admin)->create();

        $component = Livewire::test(ActivateAccount::class)
            ->set('mobile', $admin->mobile)
            ->call('sendCode');

        $real = $this->sms->lastCode($admin->mobile);

        $component
            ->set('code', $real === '000000' ? '111111' : '000000')
            ->call('verifyCode')
            ->assertHasErrors('code')
            ->assertSet('step', 'code')
            ->set('password', 'Password1')
            ->set('password_confirmation', 'Password1')
            ->call('setPassword')
            ->assertHasErrors('mobile');

        $this->assertFalse($admin->fresh()->hasPassword());
    }

    public function test_an_unknown_mobile_gets_the_same_response_but_no_sms(): void
    {
        Livewire::test(ActivateAccount::class)
            ->set('mobile', '09129999999')
            ->call('sendCode')
            ->assertHasNoErrors()
            ->assertSet('step', 'code');

        $this->assertSame([], $this->sms->codes);
    }

    public function test_an_active_account_cannot_be_hijacked_through_activation(): void
    {
        $user = User::factory()->role(Role::Expert)->create();
        $originalHash = $user->password;

        Livewire::test(ActivateAccount::class)
            ->set('mobile', $user->mobile)
            ->call('sendCode')
            ->assertSet('step', 'code');

        $this->assertSame([], $this->sms->codes);
        $this->assertSame($originalHash, $user->fresh()->password);
    }

    public function test_a_self_registered_pending_account_cannot_use_the_activation_flow(): void
    {
        $user = User::factory()->unverified()->role(Role::GreenhouseOwner)->create();

        Livewire::test(ActivateAccount::class)
            ->set('mobile', $user->mobile)
            ->call('sendCode');

        $this->assertSame([], $this->sms->codes);
    }
}
