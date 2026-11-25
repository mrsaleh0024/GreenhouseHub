<?php

namespace App\Services\Sms;

use App\Support\Mobile;
use Illuminate\Support\Facades\Log;

/**
 * Development gateway: writes the code to the log instead of sending an SMS.
 * The code itself is only logged in local/testing environments.
 */
class LogSmsGateway implements SmsGateway
{
    public function sendOtp(string $mobile, string $code): void
    {
        if (app()->environment(['local', 'testing'])) {
            Log::info("[SMS:log] OTP for {$mobile}: {$code}");

            return;
        }

        Log::warning('[SMS:log] OTP was not delivered: no real SMS gateway is configured.', [
            'mobile' => Mobile::mask($mobile),
        ]);
    }
}
