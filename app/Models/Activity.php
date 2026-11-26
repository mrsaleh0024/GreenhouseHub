<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Activity extends Model
{
    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['occurred_at' => 'datetime', 'changes' => 'array'];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException('رویدادها تغییرناپذیرند.'));
        static::deleting(fn () => throw new \LogicException('رویدادها حذف‌پذیر نیستند.'));
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }
}
