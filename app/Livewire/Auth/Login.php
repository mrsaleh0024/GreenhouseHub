<?php

namespace App\Livewire\Auth;

use App\Enums\OtpPurpose;
use App\Enums\UserStatus;
use App\Exceptions\OtpSendException;
use App\Livewire\Concerns\HasFarsiValidation;
use App\Models\User;
use App\Services\OtpService;
use App\Support\Mobile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('ورود به سامانه')]
class Login extends Component
{
    use HasFarsiValidation;

    /** Verified against when the mobile is unknown, so timing does not reveal it. */
    private const DUMMY_HASH = '$2y$12$Wr.71r9JhVZtpxFM9lFwWOij6zqGWJ90Ye9aARDuAGwABBrqmnHOO';

    public string $mobile = '';
    public string $password = '';
    public bool $remember = false;

    public function login(OtpService $otp): void
    {
        $this->mobile = Mobile::normalize($this->mobile);

        $this->validate([
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
            'password' => ['required', 'string'],
        ]);

        $this->ensureIsNotRateLimited();

        $user = User::where('mobile', $this->mobile)->first();
        $passwordOk = Hash::check($this->password, $user?->password ?: self::DUMMY_HASH);

        if (! $user || ! $user->hasPassword() || ! $passwordOk) {
            RateLimiter::hit($this->throttleKey(), 60);
            RateLimiter::hit($this->mobileKey(), 3600);

            throw ValidationException::withMessages(['mobile' => 'شماره موبایل یا رمز عبور اشتباه است.']);
        }

        RateLimiter::clear($this->throttleKey());

        if ($user->status === UserStatus::Inactive) {
            throw ValidationException::withMessages([
                'mobile' => 'حساب کاربری شما غیرفعال است. با پشتیبانی تماس بگیرید.',
            ]);
        }

        // Password is correct but the mobile was never confirmed.
        if (! $user->isActive() || ! $user->isMobileVerified()) {
            $this->sendCode($otp, $user->mobile, OtpPurpose::Registration);
            session()->put('pending_mobile', $user->mobile);

            $this->redirect(route('verify-mobile'), navigate: false);

            return;
        }

        // Admin-level accounts: the password alone never logs them in.
        if ($user->requiresTwoFactor()) {
            $this->sendCode($otp, $user->mobile, OtpPurpose::TwoFactor);
            session()->put('two_factor', [
                'user_id' => $user->getKey(),
                'mobile' => $user->mobile,
                'remember' => $this->remember,
                'expires_at' => now()->getTimestamp() + 600,
            ]);

            $this->redirect(route('two-factor'), navigate: false);

            return;
        }

        Auth::login($user, $this->remember);
        session()->regenerate();

        $this->redirectIntended(default: route('dashboard'), navigate: false);
    }

    /** A throttled/failed send must not block the next page: it offers "resend". */
    private function sendCode(OtpService $otp, string $mobile, OtpPurpose $purpose): void
    {
        try {
            $otp->send($mobile, $purpose);
        } catch (OtpSendException $e) {
            session()->flash('otp_error', $e->getMessage());
        }
    }

    private function ensureIsNotRateLimited(): void
    {
        $limits = [$this->throttleKey() => 5, $this->mobileKey() => 20];

        foreach ($limits as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                $seconds = RateLimiter::availableIn($key);

                throw ValidationException::withMessages([
                    'mobile' => "تعداد تلاش‌های ناموفق زیاد است. {$seconds} ثانیه دیگر دوباره تلاش کنید.",
                ]);
            }
        }
    }

    private function throttleKey(): string
    {
        return 'login:'.$this->mobile.'|'.request()->ip();
    }

    private function mobileKey(): string
    {
        return 'login:mobile:'.$this->mobile;
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
