<?php

namespace App\Models;

use App\Support\PersianText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CatalogItem extends Model
{
    protected $fillable = ['type', 'parent_id', 'slug', 'label_fa', 'label_en', 'status', 'source', 'is_common', 'sort_order', 'meta', 'created_by'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'is_common' => 'boolean', 'reviewed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        static::saving(function (self $m) {
            $m->parent_key = $m->parent_id ?? 0;
            $m->normalized_label = PersianText::normalize($m->label_fa);
        });
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function mergedInto(): BelongsTo
    {
        return $this->belongsTo(self::class, 'merged_into_id');
    }
}
