<?php

namespace App\Services;

use App\Enums\OtpPurpose;
use App\Exceptions\OtpSendException;
use App\Services\Sms\SmsGateway;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class OtpService
{
    public const LENGTH = 6;
    public const EXPIRY_SECONDS = 120;
    public const MAX_VERIFY_ATTEMPTS = 5;
    public const RESEND_COOLDOWN_SECONDS = 60;
    public const MAX_SENDS_PER_MOBILE_PER_HOUR = 8;
    public const MAX_SENDS_PER_IP_PER_HOUR = 30;
    public const VERIFIED_TTL_SECONDS = 600;

    public function __construct(private readonly SmsGateway $sms)
    {
    }

    /**
     * Generate and send a code. Only a keyed hash of the code is stored.
     *
     * @throws OtpSendException when throttled or when delivery fails
     */
    public function send(string $mobile, OtpPurpose $purpose): void
    {
        $this->ensureCanSend($mobile, $purpose);

        $code = str_pad((string) random_int(0, (10 ** self::LENGTH) - 1), self::LENGTH, '0', STR_PAD_LEFT);
        $codeKey = $this->codeKey($mobile, $purpose);

        Cache::put($codeKey, [
            'hash' => $this->hash($mobile, $purpose, $code),
            'attempts' => 0,
            'expires_at' => now()->getTimestamp() + self::EXPIRY_SECONDS,
        ], self::EXPIRY_SECONDS);

        try {
            $this->sms->sendOtp($mobile, $code);
        } catch (Throwable $e) {
            Cache::forget($codeKey);
            report($e);

            throw new OtpSendException('ارسال پیامک با خطا مواجه شد. کمی بعد دوباره تلاش کنید.', 30);
        }

        RateLimiter::hit($this->cooldownKey($mobile, $purpose), self::RESEND_COOLDOWN_SECONDS);
        RateLimiter::hit($this->mobileKey($mobile), 3600);
        RateLimiter::hit($this->ipKey(), 3600);
    }

    /**
     * Check a code. Wrong guesses are counted without extending the expiry;
     * after MAX_VERIFY_ATTEMPTS the code is destroyed. A code works only once.
     */
    public function verify(string $mobile, OtpPurpose $purpose, string $code): bool
    {
        $key = $this->codeKey($mobile, $purpose);

        try {
            return Cache::lock($key.':lock', 5)->block(3, function () use ($key, $mobile, $purpose, $code): bool {
                $data = Cache::get($key);

                if (! is_array($data)) {
                    return false;
                }

                if (hash_equals($data['hash'], $this->hash($mobile, $purpose, $code))) {
                    Cache::forget($key);

                    return true;
                }

                $attempts = ($data['attempts'] ?? 0) + 1;

                if ($attempts >= self::MAX_VERIFY_ATTEMPTS) {
                    Cache::forget($key);

                    return false;
                }

                $data['attempts'] = $attempts;
                Cache::put($key, $data, max(1, $data['expires_at'] - now()->getTimestamp()));

                return false;
            });
        } catch (LockTimeoutException) {
            return false;
        }
    }

    public function secondsUntilResend(string $mobile, OtpPurpose $purpose): int
    {
        $key = $this->cooldownKey($mobile, $purpose);

        return RateLimiter::tooManyAttempts($key, 1) ? RateLimiter::availableIn($key) : 0;
    }

    /*
    |--------------------------------------------------------------------------
    | "OTP passed" marker, stored server-side in the session.
    |--------------------------------------------------------------------------
    | Steps that follow a successful OTP (set password, reset password) must
    | call consumeVerified(); a client-side flag alone is never trusted.
    */

    public function markVerified(string $mobile, OtpPurpose $purpose): void
    {
        session()->put($this->verifiedKey($purpose), [
            'mobile' => $mobile,
            'expires_at' => now()->getTimestamp() + self::VERIFIED_TTL_SECONDS,
        ]);
    }

    public function isVerified(string $mobile, OtpPurpose $purpose): bool
    {
        $marker = session()->get($this->verifiedKey($purpose));

        return is_array($marker)
            && ($marker['mobile'] ?? null) === $mobile
            && ($marker['expires_at'] ?? 0) >= now()->getTimestamp();
    }

    /** One-time check: returns true at most once per successful OTP. */
    public function consumeVerified(string $mobile, OtpPurpose $purpose): bool
    {
        $ok = $this->isVerified($mobile, $purpose);

        session()->forget($this->verifiedKey($purpose));

        return $ok;
    }

    private function ensureCanSend(string $mobile, OtpPurpose $purpose): void
    {
        $cooldown = $this->cooldownKey($mobile, $purpose);

        if (RateLimiter::tooManyAttempts($cooldown, 1)) {
            $seconds = RateLimiter::availableIn($cooldown);

            throw new OtpSendException("برای دریافت کد جدید {$seconds} ثانیه صبر کنید.", $seconds);
        }

        $limits = [
            $this->mobileKey($mobile) => self::MAX_SENDS_PER_MOBILE_PER_HOUR,
            $this->ipKey() => self::MAX_SENDS_PER_IP_PER_HOUR,
        ];

        foreach ($limits as $key => $max) {
            if (RateLimiter::tooManyAttempts($key, $max)) {
                throw new OtpSendException(
                    'تعداد درخواست‌های ارسال کد بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.',
                    RateLimiter::availableIn($key),
                );
            }
        }
    }

    private function hash(string $mobile, OtpPurpose $purpose, string $code): string
    {
        return hash_hmac('sha256', $purpose->value.'|'.$mobile.'|'.$code, (string) config('app.key'));
    }

    private function codeKey(string $mobile, OtpPurpose $purpose): string
    {
        return "otp:code:{$purpose->value}:{$mobile}";
    }

    private function cooldownKey(string $mobile, OtpPurpose $purpose): string
    {
        return "otp:cooldown:{$purpose->value}:{$mobile}";
    }

    private function mobileKey(string $mobile): string
    {
        return "otp:sends:mobile:{$mobile}";
    }

    private function ipKey(): string
    {
        return 'otp:sends:ip:'.request()->ip();
    }

    private function verifiedKey(OtpPurpose $purpose): string
    {
        return "otp_verified_{$purpose->value}";
    }
}
