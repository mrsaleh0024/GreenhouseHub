<?php

namespace App\Enums;

enum UserStatus: string
{
    case Pending = 'pending';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'در انتظار فعال‌سازی',
            self::Active => 'فعال',
            self::Inactive => 'غیرفعال',
        };
    }
}
