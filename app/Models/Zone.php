<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use SoftDeletes;

    protected $fillable = ['greenhouse_id', 'code', 'name', 'zone_type_id', 'area_m2', 'capacity_plants', 'status', 'notes'];

    protected static function booted(): void
    {
        static::creating(function (self $m) {
            if (! $m->code) {
                $n = static::withTrashed()->where('greenhouse_id', $m->greenhouse_id)->count() + 1;
                $m->code = sprintf('Z%02d', $n);
            }
        });
        static::saving(fn (self $m) => $m->exists && $m->isDirty('code') ? throw new \LogicException('کد ناحیه قابل تغییر نیست.') : null);
    }

    public function greenhouse(): BelongsTo
    {
        return $this->belongsTo(Greenhouse::class);
    }
}
