<?php

namespace App\Livewire\Auth;

use App\Enums\OtpPurpose;
use App\Enums\Role;
use App\Enums\UserStatus;
use App\Exceptions\OtpSendException;
use App\Livewire\Concerns\HasFarsiValidation;
use App\Models\User;
use App\Services\CustomerLinker;
use App\Services\OtpService;
use App\Support\Mobile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Confirms the mobile of a self-registered account (password already set).
 * Reached only through Register or Login, which put `pending_mobile` in the session.
 */
#[Layout('layouts.guest')]
#[Title('تأیید شماره موبایل')]
class VerifyMobile extends Component
{
    use HasFarsiValidation;

    #[Locked]
    public string $mobile = '';

    public string $code = '';

    public function mount(): void
    {
        $mobile = session('pending_mobile');

        if (! is_string($mobile) || ! Mobile::isValid($mobile)) {
            $this->redirect(route('login'), navigate: false);

            return;
        }

        $this->mobile = $mobile;
    }

    public function verify(OtpService $otp): void
    {
        $this->code = Mobile::latinDigits($this->code);
        $this->validate(['code' => ['required', 'digits:'.OtpService::LENGTH]]);

        if (session('pending_mobile') !== $this->mobile) {
            $this->redirect(route('login'), navigate: false);

            return;
        }

        if (! $otp->verify($this->mobile, OtpPurpose::Registration, $this->code)) {
            $this->code = '';
            $this->addError('code', 'کد وارد شده اشتباه یا منقضی شده است.');

            return;
        }

        $user = User::where('mobile', $this->mobile)->first();

        if (! $user || $user->status === UserStatus::Inactive) {
            session()->forget('pending_mobile');
            $this->redirect(route('login'), navigate: false);

            return;
        }

        $user->activate();
        if ($user->primaryRole() === Role::GreenhouseOwner) {
            app(CustomerLinker::class)->linkUserToCustomer($user);
        }
        session()->forget('pending_mobile');

        // Admin-level accounts still have to pass the 2FA step at login.
        if ($user->requiresTwoFactor()) {
            session()->flash('status', 'حساب شما فعال شد. لطفاً وارد شوید.');
            $this->redirect(route('login'), navigate: false);

            return;
        }

        Auth::login($user);
        session()->regenerate();

        $this->redirectIntended(default: route('dashboard'), navigate: false);
    }

    public function resend(OtpService $otp): void
    {
        try {
            $otp->send($this->mobile, OtpPurpose::Registration);
        } catch (OtpSendException $e) {
            $this->addError('code', $e->getMessage());

            return;
        }

        $this->resetErrorBag();
        $this->dispatch('otp-sent', seconds: OtpService::RESEND_COOLDOWN_SECONDS);
    }

    public function render()
    {
        return view('livewire.auth.verify-mobile', [
            'maskedMobile' => Mobile::mask($this->mobile),
            'resendIn' => $this->mobile
                ? app(OtpService::class)->secondsUntilResend($this->mobile, OtpPurpose::Registration)
                : 0,
        ]);
    }
}
