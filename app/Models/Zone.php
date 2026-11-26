<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\VisibleToUser;
use App\Services\GreenhouseCompleteness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Zone extends Model
{
    use LogsActivity, SoftDeletes, VisibleToUser;

    protected $fillable = ['greenhouse_id', 'name', 'zone_type_id', 'area_m2', 'capacity_plants', 'status', 'notes'];

    protected static function booted(): void
    {
        $refresh = function (self $zone) {
            if ($greenhouse = $zone->greenhouse()->first()) {
                app(GreenhouseCompleteness::class)->update($greenhouse);
            }
        };
        static::saved($refresh);
        static::deleted($refresh);
        static::restored($refresh);
        static::creating(function (self $m) {
            $m->code = app(CodeGenerator::class)->nextZone((int) $m->greenhouse_id);
        });
        static::saving(fn (self $m) => $m->exists && $m->isDirty('code') ? throw new \LogicException('کد ناحیه قابل تغییر نیست.') : null);
    }

    public function greenhouse(): BelongsTo
    {
        return $this->belongsTo(Greenhouse::class);
    }
}
