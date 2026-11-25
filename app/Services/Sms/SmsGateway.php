<?php

namespace App\Services\Sms;

interface SmsGateway
{
    /**
     * Deliver a one-time code to a mobile number (09xxxxxxxxx).
     *
     * Implementations should throw on delivery failure so callers can react.
     */
    public function sendOtp(string $mobile, string $code): void;
}
