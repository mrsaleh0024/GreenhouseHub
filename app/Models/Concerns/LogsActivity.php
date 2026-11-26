<?php

namespace App\Models\Concerns;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(fn (Model $model) => app(ActivityLogger::class)->log($model, 'created'));
        static::updated(function (Model $model) {
            $changes = [];
            foreach ($model->getChanges() as $field => $value) {
                if (! in_array($field, ['updated_at', 'updated_by', 'search_text', 'completeness_percent', 'normalized_label', 'parent_key', 'deleted_at'], true)) {
                    $changes[$field] = ['old' => $model->getRawOriginal($field), 'new' => $value];
                }
            }
            if ($changes !== []) {
                app(ActivityLogger::class)->log($model, array_key_exists('status', $changes) ? 'status_changed' : 'updated', $changes);
            }
        });
        static::deleted(fn (Model $model) => app(ActivityLogger::class)->log($model, 'deleted'));
        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $model) => app(ActivityLogger::class)->log($model, 'restored'));
        }
    }
}
