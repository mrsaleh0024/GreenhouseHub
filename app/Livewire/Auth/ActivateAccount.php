<?php

namespace App\Livewire\Auth;

use App\Enums\OtpPurpose;
use App\Enums\UserStatus;
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

/**
 * Activation of accounts created by the super admin (no password yet):
 * mobile → OTP → choose a password. The password step requires a server-side
 * "OTP passed" marker, so skipping the OTP step by hand does nothing.
 */
#[Layout('layouts.guest')]
#[Title('فعال‌سازی حساب')]
class ActivateAccount extends Component
{
    use HasFarsiValidation;

    #[Locked]
    public string $step = 'mobile';

    #[Locked]
    public string $confirmedMobile = '';

    public string $mobile = '';
    public string $code = '';
    public string $password = '';
    public string $password_confirmation = '';

    public function sendCode(OtpService $otp): void
    {
        $this->mobile = Mobile::normalize($this->mobile);
        $this->validate(['mobile' => ['required', 'regex:/^09\d{9}$/']]);

        $ipKey = 'activate:'.request()->ip();

        if (RateLimiter::tooManyAttempts($ipKey, 15)) {
            $this->addError('mobile', 'تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.');

            return;
        }

        RateLimiter::hit($ipKey, 3600);

        // Same response whether or not the number is eligible (no enumeration).
        if ($this->findPendingAccount($this->mobile)) {
            try {
                $otp->send($this->mobile, OtpPurpose::Activation);
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

        if (! $otp->verify($this->confirmedMobile, OtpPurpose::Activation, $this->code)) {
            $this->code = '';
            $this->addError('code', 'کد وارد شده اشتباه یا منقضی شده است.');

            return;
        }

        $otp->markVerified($this->confirmedMobile, OtpPurpose::Activation);
        $this->step = 'password';
    }

    public function resend(OtpService $otp): void
    {
        if (! $this->findPendingAccount($this->confirmedMobile)) {
            return;
        }

        try {
            $otp->send($this->confirmedMobile, OtpPurpose::Activation);
        } catch (OtpSendException $e) {
            $this->addError('code', $e->getMessage());

            return;
        }

        $this->resetErrorBag();
        $this->dispatch('otp-sent', seconds: OtpService::RESEND_COOLDOWN_SECONDS);
    }

    public function setPassword(OtpService $otp): void
    {
        $this->validate(['password' => $this->passwordRules()]);

        // The step flag is not trusted: the OTP must have passed in this session.
        if (! $otp->consumeVerified($this->confirmedMobile, OtpPurpose::Activation)) {
            $this->step = 'mobile';
            $this->addError('mobile', 'ابتدا باید شماره موبایل خود را با کد تأیید کنید.');

            return;
        }

        $user = $this->findPendingAccount($this->confirmedMobile);

        if (! $user) {
            $this->step = 'mobile';
            $this->addError('mobile', 'حسابی برای فعال‌سازی با این شماره یافت نشد.');

            return;
        }

        $user->password = $this->password;
        $user->save();
        $user->activate();

        session()->flash('status', 'حساب شما فعال شد. اکنون می‌توانید وارد شوید.');
        $this->redirect(route('login'), navigate: false);
    }

    /** Admin-created account that has no password and was never confirmed. */
    private function findPendingAccount(string $mobile): ?User
    {
        $user = User::where('mobile', $mobile)->first();

        return $user
            && ! $user->hasPassword()
            && ! $user->isMobileVerified()
            && $user->status === UserStatus::Pending
            ? $user
            : null;
    }

    public function render()
    {
        return view('livewire.auth.activate-account', [
            'resendIn' => $this->confirmedMobile
                ? app(OtpService::class)->secondsUntilResend($this->confirmedMobile, OtpPurpose::Activation)
                : 0,
        ]);
    }
}
