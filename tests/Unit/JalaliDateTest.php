<?php

namespace Tests\Unit;

use App\Support\JalaliDate;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class JalaliDateTest extends TestCase
{
    public function test_new_year_and_leap_days_round_trip(): void
    {
        foreach ([
            ['۱۳۹۵/۰۱/۲۳', '2016-04-11', '1395/01/23'],
            ['۱۳۹۹/۱۲/۳۰', '2021-03-20', '1399/12/30'],
            ['۱۴۰۳/۱۲/۳۰', '2025-03-20', '1403/12/30'],
            ['۱۴۰۴/۰۱/۰۱', '2025-03-21', '1404/01/01'],
            ['۱۴۰۵/۰۹/۰۵', '2026-11-26', '1405/09/05'],
        ] as [$input, $gregorian, $jalali]) {
            $this->assertSame($gregorian, JalaliDate::toGregorian($input));
            $this->assertSame($jalali, JalaliDate::fromGregorian($gregorian));
        }
    }

    public function test_nonexistent_leap_day_is_rejected(): void
    {
        $this->expectException(ValidationException::class);
        JalaliDate::toGregorian('1404/12/30');
    }
}
