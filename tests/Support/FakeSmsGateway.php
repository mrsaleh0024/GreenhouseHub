<?php

namespace Tests\Support;

use App\Services\Sms\SmsGateway;
use RuntimeException;

class FakeSmsGateway implements SmsGateway
{
    /** @var array<string, string> mobile => last code */
    public array $codes = [];

    public bool $fail = false;

    public function sendOtp(string $mobile, string $code): void
    {
        if ($this->fail) {
            throw new RuntimeException('Gateway down.');
        }

        $this->codes[$mobile] = $code;
    }

    public function lastCode(string $mobile): ?string
    {
        return $this->codes[$mobile] ?? null;
    }
}
