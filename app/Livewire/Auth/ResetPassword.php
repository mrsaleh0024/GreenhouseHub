<?php

namespace App\Livewire\Auth;

use App\Enums\OtpPurpose;
use App\Livewire\Concerns\HasFarsiValidation;
use App\Models\User;
use App\Services\OtpService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Reached only after a successful password-reset OTP. Never logs the user in:
 * admin-level accounts must still pass the 2FA step at login.
 */
#[Layout('layouts.guest')]
#[Title('تنظیم رمز عبور جدید')]
class ResetPassword extends Component
{
    use HasFarsiValidation;

    #[Locked]
    public string $mobile = '';

    public string $password = '';
    public string $password_confirmation = '';

    public function mount(OtpService $otp): void
    {
        $mobile = session('reset_mobile');

        if (! is_string($mobile) || ! $otp->isVerified($mobile, OtpPurpose::PasswordReset)) {
            $this->redirect(route('password.request'), navigate: false);

            return;
        }

        $this->mobile = $mobile;
    }

    public function resetPassword(OtpService $otp): void
    {
        $this->validate(['password' => $this->passwordRules()]);

        if (! $otp->consumeVerified($this->mobile, OtpPurpose::PasswordReset)) {
            session()->forget('reset_mobile');
            $this->redirect(route('password.request'), navigate: false);

            return;
        }

        $user = User::where('mobile', $this->mobile)->first();

        if (! $user || ! $user->isActive()) {
            session()->forget('reset_mobile');
            $this->redirect(route('login'), navigate: false);

            return;
        }

        $user->password = $this->password;
        $user->setRememberToken(Str::random(60));
        $user->save();
        $user->forgetSessions();

        session()->forget('reset_mobile');
        session()->flash('status', 'رمز عبور شما تغییر کرد. اکنون وارد شوید.');

        $this->redirect(route('login'), navigate: false);
    }

    public function render()
    {
        return view('livewire.auth.reset-password');
    }
}
