<?php

namespace App\Support;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class JalaliDate
{
    /** The break years define the Persian calendar's leap-year intervals. */
    private const BREAKS = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];

    public static function toGregorian(string $input, string $field = 'date'): string
    {
        $input = Mobile::latinDigits(trim($input));
        if (! preg_match('/^(\d{4})[\/-](\d{1,2})[\/-](\d{1,2})$/', $input, $parts)) {
            throw self::invalid($field);
        }

        [$year, $month, $day] = [(int) $parts[1], (int) $parts[2], (int) $parts[3]];
        if ($year < 400 || $year > 3000 || $month < 1 || $month > 12) {
            throw self::invalid($field);
        }

        $start = self::yearStart($year);
        $yearLength = (int) $start->diffInDays(self::yearStart($year + 1));
        $monthLength = $month <= 6 ? 31 : ($month <= 11 ? 30 : ($yearLength === 366 ? 30 : 29));
        if ($day < 1 || $day > $monthLength) {
            throw self::invalid($field);
        }

        $offset = $month <= 6 ? ($month - 1) * 31 : 186 + ($month - 7) * 30;

        return $start->addDays($offset + $day - 1)->format('Y-m-d');
    }

    public static function fromGregorian(CarbonInterface|string $input): string
    {
        $date = $input instanceof CarbonInterface ? CarbonImmutable::instance($input)->startOfDay() : CarbonImmutable::parse($input, 'UTC')->startOfDay();
        $year = $date->year - 621;
        if ($date->lessThan(self::yearStart($year))) {
            $year--;
        }
        $days = (int) self::yearStart($year)->diffInDays($date);
        $month = $days < 186 ? intdiv($days, 31) + 1 : intdiv($days - 186, 30) + 7;
        $day = $days < 186 ? $days % 31 + 1 : ($days - 186) % 30 + 1;

        return sprintf('%04d/%02d/%02d', $year, $month, $day);
    }

    /** Based on the Borkowski leap-year break algorithm used by jalaali-js. */
    private static function yearStart(int $year): CarbonImmutable
    {
        $previous = self::BREAKS[0];
        $leaps = -14;
        $jump = 0;
        foreach (array_slice(self::BREAKS, 1) as $next) {
            $jump = $next - $previous;
            if ($year < $next) {
                break;
            }
            $leaps += intdiv($jump, 33) * 8 + intdiv($jump % 33, 4);
            $previous = $next;
        }
        $yearsSinceBreak = $year - $previous;
        $leaps += intdiv($yearsSinceBreak, 33) * 8 + intdiv(($yearsSinceBreak % 33) + 3, 4);
        if ($jump % 33 === 4 && $jump - $yearsSinceBreak === 4) {
            $leaps++;
        }
        $gregorianYear = $year + 621;
        $gregorianLeaps = intdiv($gregorianYear, 4) - intdiv((intdiv($gregorianYear, 100) + 1) * 3, 4) - 150;

        return CarbonImmutable::create($gregorianYear, 3, 20 + $leaps - $gregorianLeaps, 0, 0, 0, 'UTC');
    }

    private static function invalid(string $field): ValidationException
    {
        return ValidationException::withMessages([$field => 'تاریخ شمسی معتبر نیست؛ مانند ۱۴۰۴/۰۱/۰۱ وارد کنید.']);
    }
}
