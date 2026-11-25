<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuth();
    }

    public function test_password_can_be_reset_with_an_sms_code(): void
    {
        $user = $this->userWithRole(Role::Expert);

        Livewire::test(ForgotPassword::class)
            ->set('mobile', $user->mobile)
            ->call('sendCode')
            ->assertSet('step', 'code')
            ->set('code', $this->sms->lastCode($user->mobile))
            ->call('verifyCode')
            ->assertRedirect(route('password.reset'));

        Livewire::test(ResetPassword::class)
            ->set('password', 'NewPassword2')
            ->set('password_confirmation', 'NewPassword2')
            ->call('resetPassword')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('NewPassword2', $user->fresh()->password));
        $this->assertGuest();
    }

    public function test_resetting_an_admin_password_does_not_log_them_in(): void
    {
        $admin = $this->userWithRole(Role::Admin);

        Livewire::test(ForgotPassword::class)
            ->set('mobile', $admin->mobile)
            ->call('sendCode')
            ->set('code', $this->sms->lastCode($admin->mobile))
            ->call('verifyCode');

        Livewire::test(ResetPassword::class)
            ->set('password', 'NewPassword2')
            ->set('password_confirmation', 'NewPassword2')
            ->call('resetPassword');

        $this->assertGuest();
    }

    public function test_the_reset_page_requires_a_verified_code(): void
    {
        $this->get(route('password.reset'))->assertRedirect(route('password.request'));
    }

    public function test_a_wrong_code_does_not_unlock_the_reset_page(): void
    {
        $user = $this->userWithRole(Role::Expert);

        $component = Livewire::test(ForgotPassword::class)
            ->set('mobile', $user->mobile)
            ->call('sendCode');

        $real = $this->sms->lastCode($user->mobile);

        $component
            ->set('code', $real === '000000' ? '111111' : '000000')
            ->call('verifyCode')
            ->assertHasErrors('code')
            ->assertNoRedirect();

        $this->get(route('password.reset'))->assertRedirect(route('password.request'));
    }

    public function test_unknown_mobile_gets_the_same_response_but_no_sms(): void
    {
        Livewire::test(ForgotPassword::class)
            ->set('mobile', '09129999999')
            ->call('sendCode')
            ->assertHasNoErrors()
            ->assertSet('step', 'code');

        $this->assertSame([], $this->sms->codes);
    }

    public function test_the_new_password_must_be_strong_and_confirmed(): void
    {
        $user = $this->userWithRole(Role::Expert);

        Livewire::test(ForgotPassword::class)
            ->set('mobile', $user->mobile)
            ->call('sendCode')
            ->set('code', $this->sms->lastCode($user->mobile))
            ->call('verifyCode');

        Livewire::test(ResetPassword::class)
            ->set('password', 'weak')
            ->set('password_confirmation', 'weak')
            ->call('resetPassword')
            ->assertHasErrors('password');

        $this->assertTrue(Hash::check('Password1', $user->fresh()->password));
    }
}
