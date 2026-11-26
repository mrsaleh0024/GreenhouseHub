<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Support\PersianText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CatalogItem extends Model
{
    use LogsActivity;

    protected $fillable = ['type', 'parent_id', 'slug', 'label_fa', 'label_en', 'status', 'source', 'is_common', 'sort_order', 'meta', 'created_by'];

    protected function casts(): array
    {
        return ['meta' => 'array', 'is_common' => 'boolean', 'reviewed_at' => 'datetime'];
    }

    protected static function booted(): void
    {
        $invalidate = function (): void {
            Cache::forget('catalog.approved.v2');
            if (DB::transactionLevel() > 0) {
                DB::afterCommit(fn () => Cache::forget('catalog.approved.v2'));
            }
        };
        static::saved($invalidate);
        static::deleted($invalidate);
        static::saving(function (self $m) {
            if ($m->exists && $m->getOriginal('status') === 'approved' && $m->isDirty('slug')) {
                throw new \LogicException('شناسه گزینه تأییدشده قابل تغییر نیست.');
            }
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
