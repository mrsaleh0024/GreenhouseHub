<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OtpService
{
    const OTP_LENGTH = 6;
    const OTP_EXPIRY_SECONDS = 120;
    const MAX_ATTEMPTS = 5;
    const MAX_SEND_PER_HOUR = 8;

    public function generate(string $mobile): string
    {
        $otp = str_pad(random_int(0, 999999), self::OTP_LENGTH, '0', STR_PAD_LEFT);

        $key = $this->getCacheKey($mobile);

        Cache::put($key, [
            'code' => $otp,
            'attempts' => 0,
            'created_at' => now(),
        ], self::OTP_EXPIRY_SECONDS);

        return $otp;
    }

    public function verify(string $mobile, string $inputCode): bool
    {
        $key = $this->getCacheKey($mobile);
        $data = Cache::get($key);

        if (!$data) {
            return false;
        }

        $data['attempts']++;
        Cache::put($key, $data, self::OTP_EXPIRY_SECONDS);

        if ($data['attempts'] > self::MAX_ATTEMPTS) {
            Cache::forget($key);
            return false;
        }

        if ($data['code'] === $inputCode) {
            Cache::forget($key);
            return true;
        }

        return false;
    }

    public function canSend(string $mobile): bool
    {
        $sendKey = "otp_send_count:{$mobile}";
        $count = Cache::get($sendKey, 0);

        return $count < self::MAX_SEND_PER_HOUR;
    }

    public function incrementSendCount(string $mobile): void
    {
        $sendKey = "otp_send_count:{$mobile}";
        $count = Cache::get($sendKey, 0);
        Cache::put($sendKey, $count + 1, now()->addHour());
    }

    private function getCacheKey(string $mobile): string
    {
        return "otp:{$mobile}";
    }
}