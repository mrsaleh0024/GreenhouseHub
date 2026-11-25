<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExpertProfile extends Model
{
    protected $fillable = ['specialty', 'license_number', 'experience_years'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
