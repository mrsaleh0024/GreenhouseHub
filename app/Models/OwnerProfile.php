<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerProfile extends Model
{
    protected $fillable = ['greenhouse_name', 'province', 'city'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
