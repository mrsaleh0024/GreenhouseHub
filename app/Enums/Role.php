<?php

namespace App\Enums;

enum Role: string
{
    case SuperAdmin = 'super_admin';
    case Admin = 'admin';
    case Expert = 'expert';
    case GreenhouseOwner = 'greenhouse_owner';

    public function label(): string
    {
        return match ($this) {
            self::SuperAdmin => 'مدیر ارشد',
            self::Admin => 'مدیر',
            self::Expert => 'کارشناس',
            self::GreenhouseOwner => 'گلخانه‌دار',
        };
    }

    /** Admin-level accounts must pass an OTP challenge after the password. */
    public function requiresTwoFactor(): bool
    {
        return match ($this) {
            self::SuperAdmin, self::Admin => true,
            default => false,
        };
    }

    /** Only these roles may be chosen on the public registration page. */
    public function isSelfRegistrable(): bool
    {
        return match ($this) {
            self::Expert, self::GreenhouseOwner => true,
            default => false,
        };
    }

    /** @return list<self> */
    public static function selfRegistrable(): array
    {
        return array_values(array_filter(self::cases(), fn (self $role) => $role->isSelfRegistrable()));
    }

    /** @return list<string> */
    public static function values(): array
    {
        return array_map(fn (self $role) => $role->value, self::cases());
    }

    /** @return list<string> */
    public static function twoFactorValues(): array
    {
        return array_values(array_map(
            fn (self $role) => $role->value,
            array_filter(self::cases(), fn (self $role) => $role->requiresTwoFactor()),
        ));
    }
}
