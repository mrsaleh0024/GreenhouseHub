<?php

namespace App\Models;

use App\Models\Concerns\LogsActivity;
use App\Support\Mobile;
use App\Support\PersianText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use \App\Models\Concerns\VisibleToUser, LogsActivity, SoftDeletes;

    protected $fillable = ['full_name', 'customer_type', 'company_name', 'national_id', 'mobile', 'phone', 'email', 'province_id', 'city_id', 'address', 'status', 'registered_via', 'notes'];

    protected static function booted(): void
    {
        static::deleting(function (self $customer) {
            if (! $customer->isForceDeleting() && $customer->greenhouses()->exists()) {
                throw new \LogicException('مشتری دارای گلخانه را نمی‌توان حذف کرد.');
            }
        });
        static::creating(function (self $m) {
            $m->customer_code ??= app(CodeGenerator::class)->next('customer', 'CU');
        });
        static::saving(function (self $m) {
            if ($m->isDirty('customer_code') && $m->exists) {
                throw new \LogicException('کد مشتری قابل تغییر نیست.');
            } $m->mobile = Mobile::normalize($m->mobile);
            $m->search_text = PersianText::normalize($m->full_name.' '.$m->customer_code);
        });
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)->withPivot('is_primary')->withTimestamps();
    }

    public function greenhouses(): HasMany
    {
        return $this->hasMany(Greenhouse::class);
    }
}
