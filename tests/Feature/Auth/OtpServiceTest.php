<?php

namespace Tests\Feature\Auth;

use App\Enums\OtpPurpose;
use App\Exceptions\OtpSendException;
use App\Services\OtpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class OtpServiceTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    private const MOBILE = '09123456789';

    private OtpService $otp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuth();
        $this->otp = app(OtpService::class);
    }

    public function test_a_sent_code_verifies_once(): void
    {
        $this->otp->send(self::MOBILE, OtpPurpose::Registration);
        $code = $this->sms->lastCode(self::MOBILE);

        $this->assertMatchesRegularExpression('/^\d{6}$/', $code);
        $this->assertTrue($this->otp->verify(self::MOBILE, OtpPurpose::Registration, $code));
        $this->assertFalse($this->otp->verify(self::MOBILE, OtpPurpose::Registration, $code), 'A code must be single-use.');
    }

    public function test_the_plain_code_is_never_stored(): void
    {
        $this->otp->send(self::MOBILE, OtpPurpose::Registration);
        $code = $this->sms->lastCode(self::MOBILE);

        $stored = Cache::get('otp:code:registration:'.self::MOBILE);

        $this->assertArrayNotHasKey('code', $stored);
        $this->assertNotSame($code, $stored['hash']);
    }

    public function test_a_code_is_bound_to_its_purpose(): void
    {
        $this->otp->send(self::MOBILE, OtpPurpose::PasswordReset);
        $code = $this->sms->lastCode(self::MOBILE);

        $this->assertFalse($this->otp->verify(self::MOBILE, OtpPurpose::TwoFactor, $code));
        $this->assertFalse($this->otp->verify(self::MOBILE, OtpPurpose::Registration, $code));
        $this->assertTrue($this->otp->verify(self::MOBILE, OtpPurpose::PasswordReset, $code));
    }

    public function test_a_code_is_destroyed_after_too_many_wrong_guesses(): void
    {
        $this->otp->send(self::MOBILE, OtpPurpose::Registration);
        $code = $this->sms->lastCode(self::MOBILE);
        $wrong = $code === '000000' ? '111111' : '000000';

        for ($i = 0; $i < OtpService::MAX_VERIFY_ATTEMPTS; $i++) {
            $this->assertFalse($this->otp->verify(self::MOBILE, OtpPurpose::Registration, $wrong));
        }

        $this->assertFalse($this->otp->verify(self::MOBILE, OtpPurpose::Registration, $code));
    }

    public function test_a_code_expires(): void
    {
        $this->otp->send(self::MOBILE, OtpPurpose::Registration);
        $code = $this->sms->lastCode(self::MOBILE);

        $this->travel(OtpService::EXPIRY_SECONDS + 1)->seconds();

        $this->assertFalse($this->otp->verify(self::MOBILE, OtpPurpose::Registration, $code));
    }

    public function test_resend_is_throttled_until_the_cooldown_passes(): void
    {
        $this->otp->send(self::MOBILE, OtpPurpose::Registration);

        try {
            $this->otp->send(self::MOBILE, OtpPurpose::Registration);
            $this->fail('Second send inside the cooldown should be refused.');
        } catch (OtpSendException $e) {
            $this->assertGreaterThan(0, $e->retryAfter);
            $this->assertGreaterThan(0, $this->otp->secondsUntilResend(self::MOBILE, OtpPurpose::Registration));
        }

        $this->travel(OtpService::RESEND_COOLDOWN_SECONDS + 1)->seconds();

        $this->otp->send(self::MOBILE, OtpPurpose::Registration);
        $this->assertNotNull($this->sms->lastCode(self::MOBILE));
    }

    public function test_gateway_failure_leaves_no_usable_code(): void
    {
        $this->sms->fail = true;

        try {
            $this->otp->send(self::MOBILE, OtpPurpose::Registration);
            $this->fail('A gateway failure should surface as OtpSendException.');
        } catch (OtpSendException) {
            // expected
        }

        $this->assertNull(Cache::get('otp:code:registration:'.self::MOBILE));
        $this->assertSame(0, $this->otp->secondsUntilResend(self::MOBILE, OtpPurpose::Registration));
    }

    public function test_verified_marker_is_one_time_and_bound_to_the_mobile(): void
    {
        $this->assertFalse($this->otp->consumeVerified(self::MOBILE, OtpPurpose::Activation));

        $this->otp->markVerified(self::MOBILE, OtpPurpose::Activation);

        $this->assertFalse($this->otp->isVerified('09120000000', OtpPurpose::Activation));
        $this->assertFalse($this->otp->isVerified(self::MOBILE, OtpPurpose::PasswordReset));
        $this->assertTrue($this->otp->consumeVerified(self::MOBILE, OtpPurpose::Activation));
        $this->assertFalse($this->otp->consumeVerified(self::MOBILE, OtpPurpose::Activation));
    }

    public function test_verified_marker_expires(): void
    {
        $this->otp->markVerified(self::MOBILE, OtpPurpose::Activation);

        $this->travel(OtpService::VERIFIED_TTL_SECONDS + 1)->seconds();

        $this->assertFalse($this->otp->consumeVerified(self::MOBILE, OtpPurpose::Activation));
    }
}
