<?php

namespace App\Support;

class PersianText
{
    public static function normalize(string $value): string
    {
        $value = self::latinDigits($value);
        $value = str_replace(['ي', 'ى', 'ك', 'ة', 'ۀ', 'ـ'], ['ی', 'ی', 'ک', 'ه', 'ه', ''], $value);
        $value = mb_strtolower($value, 'UTF-8');

        return preg_replace('/[\s\p{P}\p{S}\x{064B}-\x{065F}\x{0670}\x{200C}\x{200D}]+/u', '', $value) ?? '';
    }

    private static function latinDigits(string $v): string
    {
        return strtr($v, ['۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4', '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9', '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4', '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9']);
    }
}
