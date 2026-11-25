<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SMS driver
    |--------------------------------------------------------------------------
    |
    | "log" writes OTP codes to the Laravel log (development only).
    | To add a real provider (e.g. sms.ir): implement App\Services\Sms\SmsGateway,
    | then add a matching arm in AppServiceProvider::register().
    |
    */

    'driver' => env('SMS_DRIVER', 'log'),

    // Reserved for the sms.ir driver (not wired up yet).
    'smsir' => [
        'api_key' => env('SMSIR_API_KEY'),
        'template_id' => env('SMSIR_TEMPLATE_ID'),
    ],

];
