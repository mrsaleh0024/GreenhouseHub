<?php

namespace App\Support;

final class Mobile
{
    private const DIGITS = [
        '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
        '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
        '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
    ];

    /**
     * Normalise user input to the canonical 09xxxxxxxxx form.
     * Accepts Persian/Arabic digits, spaces, dashes, +98 / 0098 / 98 prefixes.
     */
    public static function normalize(?string $value): string
    {
        $value = strtr(trim((string) $value), self::DIGITS);
        $value = preg_replace('/[\s\-()]+/u', '', $value) ?? '';

        return match (true) {
            str_starts_with($value, '+98') => '0'.substr($value, 3),
            str_starts_with($value, '0098') => '0'.substr($value, 4),
            str_starts_with($value, '98') && strlen($value) === 12 => '0'.substr($value, 2),
            strlen($value) === 10 && str_starts_with($value, '9') => '0'.$value,
            default => $value,
        };
    }

    /** Convert Persian/Arabic digits to Latin and drop whitespace (for OTP input). */
    public static function latinDigits(?string $value): string
    {
        return preg_replace('/\s+/u', '', strtr((string) $value, self::DIGITS)) ?? '';
    }

    public static function isValid(string $mobile): bool
    {
        return (bool) preg_match('/^09\d{9}$/', $mobile);
    }

    /** 09123456789 → 0912***6789 (safe to log or display). */
    public static function mask(string $mobile): string
    {
        return strlen($mobile) === 11
            ? substr($mobile, 0, 4).'***'.substr($mobile, -4)
            : '***';
    }
}
