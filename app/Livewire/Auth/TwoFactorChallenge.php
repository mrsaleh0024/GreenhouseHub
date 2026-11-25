<?php

namespace App\Livewire\Auth;

use App\Enums\OtpPurpose;
use App\Exceptions\OtpSendException;
use App\Livewire\Concerns\HasFarsiValidation;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Mobile;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Second login step for admin / super_admin. The user is NOT authenticated
 * until the code is verified; only `two_factor` lives in the session.
 */
#[Layout('layouts.guest')]
#[Title('تأیید دومرحله‌ای')]
class TwoFactorChallenge extends Component
{
    use HasFarsiValidation;

    #[Locked]
    public string $maskedMobile = '';

    public string $code = '';

    public function mount(): void
    {
        $state = $this->state();

        if (! $state) {
            $this->redirect(route('login'), navigate: false);

            return;
        }

        $this->maskedMobile = Mobile::mask($state['mobile']);
    }

    public function verify(OtpService $otp): void
    {
        $state = $this->state();

        if (! $state) {
            $this->redirect(route('login'), navigate: false);

            return;
        }

        $this->code = Mobile::latinDigits($this->code);
        $this->validate(['code' => ['required', 'digits:'.OtpService::LENGTH]]);

        if (! $otp->verify($state['mobile'], OtpPurpose::TwoFactor, $this->code)) {
            $this->code = '';
            $this->addError('code', 'کد وارد شده اشتباه یا منقضی شده است.');

            return;
        }

        $user = User::find($state['user_id']);

        if (! $user || ! $user->isActive() || $user->mobile !== $state['mobile']) {
            session()->forget('two_factor');
            $this->redirect(route('login'), navigate: false);

            return;
        }

        session()->forget('two_factor');
        Auth::login($user, (bool) $state['remember']);
        session()->regenerate();

        $this->redirectIntended(default: route('dashboard'), navigate: false);
    }

    public function resend(OtpService $otp): void
    {
        $state = $this->state();

        if (! $state) {
            $this->redirect(route('login'), navigate: false);

            return;
        }

        try {
            $otp->send($state['mobile'], OtpPurpose::TwoFactor);
        } catch (OtpSendException $e) {
            $this->addError('code', $e->getMessage());

            return;
        }

        $this->resetErrorBag();
        $this->dispatch('otp-sent', seconds: OtpService::RESEND_COOLDOWN_SECONDS);
    }

    public function cancel(): void
    {
        session()->forget('two_factor');
        $this->redirect(route('login'), navigate: false);
    }

    /** @return array{user_id: int, mobile: string, remember: bool, expires_at: int}|null */
    private function state(): ?array
    {
        $state = session('two_factor');

        return is_array($state) && ($state['expires_at'] ?? 0) >= now()->getTimestamp() ? $state : null;
    }

    public function render()
    {
        $state = $this->state();

        return view('livewire.auth.two-factor-challenge', [
            'resendIn' => $state
                ? app(OtpService::class)->secondsUntilResend($state['mobile'], OtpPurpose::TwoFactor)
                : 0,
        ]);
    }
}
