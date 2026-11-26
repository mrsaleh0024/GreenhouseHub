<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CorrectionRequest extends Model
{
    protected $fillable = ['greenhouse_id', 'cultivation_cycle_id', 'details'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function greenhouse(): BelongsTo
    {
        return $this->belongsTo(Greenhouse::class);
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(CultivationCycle::class, 'cultivation_cycle_id');
    }
}
