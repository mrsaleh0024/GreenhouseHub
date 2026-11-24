<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\OtpService;
use App\Services\SmsService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('فراموشی رمز عبور')]
class ForgotPassword extends Component
{
    public string $mobile = '';
    public string $otp = '';
    public bool $otpSent = false;

    public function sendOtp()
    {
        $this->validate([
            'mobile' => ['required', 'regex:/^09\d{9}$/'],
        ], [
            'mobile.required' => 'شماره موبایل الزامی است.',
            'mobile.regex' => 'شماره موبایل معتبر نیست.',
        ]);

        $user = User::where('mobile', $this->mobile)->first();

        if (!$user) {
            $this->addError('mobile', 'کاربری با این شماره موبایل یافت نشد.');
            return;
        }

        $otpService = app(OtpService::class);
        $smsService = app(SmsService::class);

        if (!$otpService->canSend($this->mobile)) {
            $this->addError('mobile', 'تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد تلاش کنید.');
            return;
        }

        $otp = $otpService->generate($this->mobile);
        $smsService->sendOtp($this->mobile, $otp);
        $otpService->incrementSendCount($this->mobile);

        $this->otpSent = true;
        session(['reset_mobile' => $this->mobile]);
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

        return redirect()->route('password.reset');
    }

    public function render()
    {
        return view('livewire.auth.forgot-password');
    }
}