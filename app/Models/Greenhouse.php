<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Services\GreenhouseCompleteness;
use App\Support\PersianText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Greenhouse extends Model
{
    use \App\Models\Concerns\VisibleToUser, LogsActivity, SoftDeletes;

    protected $fillable = ['customer_id', 'responsible_expert_id', 'name', 'province_id', 'city_id', 'address', 'latitude', 'longitude', 'altitude_m', 'area_m2', 'structure_type_id', 'cultivation_type_id', 'commissioned_on', 'status', 'notes'];

    protected function casts(): array
    {
        return ['commissioned_on' => 'date'];
    }

    protected static function booted(): void
    {
        static::saved(fn (self $greenhouse) => app(GreenhouseCompleteness::class)->update($greenhouse));
        static::deleting(function (self $greenhouse) {
            if (! $greenhouse->isForceDeleting() && ($greenhouse->zones()->exists() || $greenhouse->cycles()->whereNotIn('status', ['completed', 'aborted'])->exists())) {
                throw new \LogicException('گلخانه دارای ناحیه یا دوره کشت فعال را نمی‌توان حذف کرد.');
            }
        });
        static::creating(fn (self $m) => $m->greenhouse_code ??= app(CodeGenerator::class)->next('greenhouse', 'GH'));
        static::saving(function (self $m) {
            if ($m->isDirty('greenhouse_code') && $m->exists) {
                throw new \LogicException('کد گلخانه قابل تغییر نیست.');
            }$m->search_text = PersianText::normalize($m->name.' '.$m->greenhouse_code);
        });
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function cycles(): HasMany
    {
        return $this->hasMany(CultivationCycle::class);
    }
}
