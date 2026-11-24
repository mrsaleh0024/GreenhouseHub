<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('تأیید شماره موبایل')]
class VerifyMobile extends Component
{
    public string $mobile = '';
    public string $otp = '';
    public string $password = '';
    public string $password_confirmation = '';

    public bool $otpSent = false;
    public bool $otpVerified = false;

    public function mount()
    {
        $this->mobile = session('pending_mobile', '');

        if (empty($this->mobile)) {
            return redirect()->route('login');
        }
    }

    public function sendOtp()
    {
        $this->validate([
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
        ]);

        $otpService = app(OtpService::class);
        $smsService = app(SmsService::class);

        if (!$otpService->canSend($this->mobile)) {
            $this->addError('mobile', 'تعداد درخواست‌های شما بیش از حد مجاز است. کمی بعد تلاش کنید.');
            return;
        }

        $otp = $otpService->generate($this->mobile);
        $smsService->sendOtp($this->mobile, $otp);
        $otpService->incrementSendCount($this->mobile);

        $this->otpSent = true;
        $this->dispatch('otp-sent');
    }

    public function verifyOtp()
    {
        $this->validate([
            'otp' => ['required', 'digits:6'],
        ]);

        $otpService = app(OtpService::class);

        if (!$otpService->verify($this->mobile, $this->otp)) {
            $this->addError('otp', 'کد وارد شده اشتباه یا منقضی شده است.');
            return;
        }

        $this->otpVerified = true;
    }

    public function setPassword()
    {
        $this->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.required' => 'رمز عبور الزامی است.',
            'password.min' => 'رمز عبور باید حداقل ۸ کاراکتر باشد.',
            'password.confirmed' => 'تکرار رمز عبور مطابقت ندارد.',
        ]);

        $user = User::where('mobile', $this->mobile)->first();

        if (!$user) {
            $this->addError('mobile', 'کاربر یافت نشد.');
            return;
        }

        $user->update([
            'password' => Hash::make($this->password),
            'mobile_verified_at' => now(),
            'status' => 'active',
        ]);

        Auth::login($user);
        session()->forget('pending_mobile');
        session()->regenerate();

        if ($user->hasRole(['super_admin', 'admin'])) {
            return redirect()->intended('/admin');
        }

        if ($user->hasRole('expert')) {
            return redirect()->intended('/expert');
        }

        if ($user->hasRole('greenhouse_owner')) {
            return redirect()->intended('/owner');
        }

        return redirect()->intended('/member');
    }

    public function render()
    {
        return view('livewire.auth.verify-mobile');
    }
}