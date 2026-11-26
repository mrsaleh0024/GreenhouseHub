<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CultivationCycle extends Model
{
    use SoftDeletes;

    protected $fillable = ['greenhouse_id', 'zone_id', 'crop_id', 'variety_id', 'substrate_id', 'planting_date', 'expected_harvest_date', 'harvest_started_on', 'harvest_ended_on', 'plant_count', 'planted_area_m2', 'status', 'total_yield_kg', 'notes'];

    protected function casts(): array
    {
        return ['planting_date' => 'date', 'expected_harvest_date' => 'date', 'harvest_started_on' => 'date', 'harvest_ended_on' => 'date'];
    }

    protected static function booted(): void
    {
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
}
