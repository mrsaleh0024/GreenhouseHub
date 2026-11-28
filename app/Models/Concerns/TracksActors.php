<?php

namespace App\Models\Concerns;

use App\Support\AuditActorContext;
use Illuminate\Database\Eloquent\Model;

trait TracksActors
{
    public static function bootTracksActors(): void
    {
        static::creating(function (Model $model): void {
            if ($actorId = app(AuditActorContext::class)->current()?->getKey()) {
                $model->created_by = $actorId;
                $model->updated_by = $actorId;
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty() && ($actorId = app(AuditActorContext::class)->current()?->getKey())) {
                $model->updated_by = $actorId;
            }
        });
    }
}
