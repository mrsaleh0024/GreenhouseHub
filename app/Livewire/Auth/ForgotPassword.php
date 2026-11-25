<?php

namespace App\Livewire\Auth;

use App\Enums\OtpPurpose;
use App\Exceptions\OtpSendException;
use App\Livewire\Concerns\HasFarsiValidation;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Mobile;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('فراموشی رمز عبور')]
class ForgotPassword extends Component
{
    use HasFarsiValidation;

    #[Locked]
    public string $step = 'mobile';

    #[Locked]
    public string $confirmedMobile = '';

    public string $mobile = '';
    public string $code = '';

    public function sendCode(OtpService $otp): void
    {
        $this->mobile = Mobile::normalize($this->mobile);
        $this->validate(['mobile' => ['required', 'regex:/^09\d{9}$/']]);

        $ipKey = 'forgot:'.request()->ip();

        if (RateLimiter::tooManyAttempts($ipKey, 15)) {
            $this->addError('mobile', 'تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.');

            return;
        }

        RateLimiter::hit($ipKey, 3600);

        // Always move to the code step: the response never reveals whether the number is registered.
        if ($this->eligibleUser($this->mobile)) {
            try {
                $otp->send($this->mobile, OtpPurpose::PasswordReset);
            } catch (OtpSendException $e) {
                $this->addError('mobile', $e->getMessage());

                return;
            }
        }

        $this->confirmedMobile = $this->mobile;
        $this->step = 'code';
    }

    public function verifyCode(OtpService $otp): void
    {
        $this->code = Mobile::latinDigits($this->code);
        $this->validate(['code' => ['required', 'digits:'.OtpService::LENGTH]]);

        if (! $otp->verify($this->confirmedMobile, OtpPurpose::PasswordReset, $this->code)) {
            $this->code = '';
            $this->addError('code', 'کد وارد شده اشتباه یا منقضی شده است.');

            return;
        }

        $otp->markVerified($this->confirmedMobile, OtpPurpose::PasswordReset);
        session()->put('reset_mobile', $this->confirmedMobile);

        $this->redirect(route('password.reset'), navigate: false);
    }

    public function resend(OtpService $otp): void
    {
        if (! $this->eligibleUser($this->confirmedMobile)) {
            return;
        }

        try {
            $otp->send($this->confirmedMobile, OtpPurpose::PasswordReset);
        } catch (OtpSendException $e) {
            $this->addError('code', $e->getMessage());

            return;
        }

        $this->resetErrorBag();
        $this->dispatch('otp-sent', seconds: OtpService::RESEND_COOLDOWN_SECONDS);
    }

    private function eligibleUser(string $mobile): ?User
    {
        $user = User::where('mobile', $mobile)->first();

        return $user && $user->isActive() && $user->hasPassword() ? $user : null;
    }

    public function render()
    {
        return view('livewire.auth.forgot-password', [
            'resendIn' => $this->confirmedMobile
                ? app(OtpService::class)->secondsUntilResend($this->confirmedMobile, OtpPurpose::PasswordReset)
                : 0,
        ]);
    }
}
