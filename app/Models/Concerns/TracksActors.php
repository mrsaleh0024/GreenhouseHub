<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

trait TracksActors
{
    public static function bootTracksActors(): void
    {
        static::creating(function (Model $model): void {
            if ($actorId = auth()->id()) {
                $model->created_by = $actorId;
                $model->updated_by = $actorId;
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty() && ($actorId = auth()->id())) {
                $model->updated_by = $actorId;
            }
        });
    }
}
