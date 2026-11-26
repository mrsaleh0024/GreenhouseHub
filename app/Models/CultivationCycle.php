<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Models\Concerns\TracksActors;
use App\Models\Concerns\VisibleToUser;
use App\Services\GreenhouseCompleteness;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CultivationCycle extends Model
{
    use LogsActivity, SoftDeletes, TracksActors, VisibleToUser;

    protected $fillable = ['greenhouse_id', 'zone_id', 'crop_id', 'variety_id', 'substrate_id', 'planting_date', 'expected_harvest_date', 'harvest_started_on', 'harvest_ended_on', 'plant_count', 'planted_area_m2', 'status', 'total_yield_kg', 'notes'];

    protected function casts(): array
    {
        return ['planting_date' => 'date', 'expected_harvest_date' => 'date', 'harvest_started_on' => 'date', 'harvest_ended_on' => 'date'];
    }

    protected static function booted(): void
    {
        $refresh = function (self $cycle) {
            if ($greenhouse = $cycle->greenhouse()->first()) {
                app(GreenhouseCompleteness::class)->update($greenhouse);
            }
        };
        static::saved($refresh);
        static::deleted($refresh);
        static::restored($refresh);
        static::creating(fn (self $m) => $m->cycle_code ??= app(CodeGenerator::class)->next('cultivation_cycle', 'CC'));
        static::saving(fn (self $m) => $m->exists && $m->isDirty('cycle_code') ? throw new \LogicException('کد دوره قابل تغییر نیست.') : null);
    }

    public function greenhouse(): BelongsTo
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(Zone::class);
    }

    public function crop(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'crop_id');
    }

    public function substrate(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'substrate_id');
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(CatalogItem::class, 'variety_id');
    }

    public function displayStatus(): string
    {
        if ($this->status === 'aborted') {
            return 'متوقف‌شده';
        }
        if ($this->harvest_ended_on !== null || $this->status === 'completed') {
            return 'پایان‌یافته';
        }
        if ($this->planting_date?->isFuture()) {
            return 'در انتظار کاشت';
        }

        return $this->harvest_started_on ? 'در حال برداشت' : 'در حال رشد';
    }
}
