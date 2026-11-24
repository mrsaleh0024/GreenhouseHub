<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    public function sendOtp(string $mobile, string $otp): bool
    {
        Log::info("OTP for {$mobile}: {$otp}");
        return true;
    }

    // private function sendWithKavenegar(string $mobile, string $otp): bool { ... }
}