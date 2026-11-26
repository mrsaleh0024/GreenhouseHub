<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\CultivationCycle;
use App\Models\Greenhouse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CultivationCycleService
{
    public function save(Greenhouse $greenhouse, User $user, array $input, ?CultivationCycle $cycle = null): CultivationCycle
    {
        $allowed = ['zone_id', 'crop_id', 'variety_id', 'substrate_id', 'planting_date', 'expected_harvest_date', 'harvest_started_on', 'plant_count', 'planted_area_m2'];
        if (array_diff(array_keys($input), $allowed) !== []) {
            throw ValidationException::withMessages(['cycle' => 'تغییر اطلاعات کلیدی از این بخش مجاز نیست.']);
        }

        return DB::transaction(function () use ($greenhouse, $user, $input, $cycle) {
            $lockedGreenhouse = Greenhouse::whereKey($greenhouse->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('manageCycles', $lockedGreenhouse);
            if ($cycle) {
                $cycle = CultivationCycle::whereKey($cycle->id)->where('greenhouse_id', $lockedGreenhouse->id)->lockForUpdate()->firstOrFail();
                Gate::forUser($user)->authorize('update', $cycle);
            } elseif ($lockedGreenhouse->status !== 'active') {
                throw ValidationException::withMessages(['cycle' => 'ثبت کشت جدید فقط برای گلخانه فعال امکان‌پذیر است.']);
            }

            $catalog = function (string $type, string $field) use ($user, $cycle) {
                return Rule::exists('catalog_items', 'id')->where(function ($query) use ($type, $field, $user, $cycle) {
                    $query->where('type', $type);
                    CatalogVisibility::constrain($query, $user, array_filter([$cycle?->{$field}]));
                });
            };
            $validator = Validator::make($input, [
                'zone_id' => ['nullable', 'integer', Rule::exists('zones', 'id')->where('greenhouse_id', $lockedGreenhouse->id)->whereNull('deleted_at')],
                'crop_id' => ['required', 'integer', $catalog('crop', 'crop_id')],
                'variety_id' => ['nullable', 'integer', $catalog('variety', 'variety_id'), Rule::exists('catalog_items', 'id')->where('parent_id', $input['crop_id'] ?? null)],
                'substrate_id' => ['nullable', 'integer', $catalog('substrate', 'substrate_id')],
                'planting_date' => ['required', 'date_format:Y-m-d'],
                'expected_harvest_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:planting_date'],
                'harvest_started_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:planting_date', 'before_or_equal:today'],
                'plant_count' => ['nullable', 'integer', 'min:1', 'max:4294967295'],
                'planted_area_m2' => ['nullable', 'numeric', 'gt:0', 'max:99999999.99'],
            ], [
                'required' => 'تکمیل این بخش الزامی است.', 'integer' => 'مقدار واردشده باید عدد صحیح باشد.',
                'numeric' => 'مقدار واردشده باید عدد باشد.', 'exists' => 'گزینه انتخاب‌شده معتبر نیست.',
                'date_format' => 'تاریخ واردشده معتبر نیست.', 'after_or_equal' => 'تاریخ نمی‌تواند پیش از تاریخ کاشت باشد.',
                'before_or_equal' => 'این تاریخ نمی‌تواند در آینده باشد.', 'gt' => 'مقدار باید بزرگ‌تر از صفر باشد.',
            ]);
            $values = $validator->validate();

            if ($cycle && $cycle->harvest_ended_on && (($values['planting_date'] ?? '') > $cycle->harvest_ended_on->format('Y-m-d') || (($values['harvest_started_on'] ?? null) && $values['harvest_started_on'] > $cycle->harvest_ended_on->format('Y-m-d')))) {
                throw ValidationException::withMessages(['planting_date' => 'تاریخ کاشت یا شروع برداشت نمی‌تواند پس از پایان برداشت باشد.']);
            }

            $cycle ??= new CultivationCycle(['greenhouse_id' => $lockedGreenhouse->id]);
            $cycle->fill($values);
            if (! $cycle->harvest_ended_on && $cycle->status !== 'aborted') {
                $cycle->status = ($values['harvest_started_on'] ?? null) ? 'harvesting' : (($values['planting_date'] > now()->toDateString()) ? 'planned' : 'growing');
            }
            $cycle->save();

            return $cycle;
        }, 3);
    }

    public function finish(Greenhouse $greenhouse, CultivationCycle $cycle, User $user, string $endDate, ?string $yieldKg = null): void
    {
        DB::transaction(function () use ($greenhouse, $cycle, $user, $endDate, $yieldKg) {
            $locked = CultivationCycle::whereKey($cycle->id)->where('greenhouse_id', $greenhouse->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($user)->authorize('update', $locked);
            if ($locked->harvest_ended_on !== null || in_array($locked->status, ['completed', 'aborted'], true)) {
                throw ValidationException::withMessages(['end' => 'این دوره پیش‌تر بسته شده است.']);
            }
            $referenceDate = $locked->harvest_started_on?->format('Y-m-d') ?? $locked->planting_date->format('Y-m-d');
            $data = Validator::make(['end' => $endDate, 'yield' => $yieldKg], [
                'end' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$referenceDate, 'before_or_equal:today'],
                'yield' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            ], [
                'end.required' => 'تاریخ پایان برداشت را وارد کنید.',
                'end.date_format' => 'تاریخ پایان معتبر نیست.',
                'end.after_or_equal' => 'تاریخ پایان نمی‌تواند پیش از کاشت یا شروع برداشت باشد.',
                'end.before_or_equal' => 'تاریخ پایان نمی‌تواند در آینده باشد.',
                'yield.numeric' => 'مقدار برداشت باید عدد باشد.',
                'yield.min' => 'مقدار برداشت نمی‌تواند منفی باشد.',
            ])->validate();
            $locked->forceFill(['harvest_ended_on' => $data['end'], 'total_yield_kg' => $data['yield'] ?? null, 'status' => 'completed'])->save();
        }, 3);
    }

    public function correctClosedHarvest(Greenhouse $greenhouse, CultivationCycle $cycle, User $admin, string $endDate, ?string $yieldKg): void
    {
        abort_unless($admin->hasAnyRole([Role::Admin->value, Role::SuperAdmin->value]), 403);
        DB::transaction(function () use ($greenhouse, $cycle, $admin, $endDate, $yieldKg) {
            $locked = CultivationCycle::whereKey($cycle->id)->where('greenhouse_id', $greenhouse->id)->lockForUpdate()->firstOrFail();
            Gate::forUser($admin)->authorize('update', $locked);
            if ($locked->harvest_ended_on === null) {
                throw ValidationException::withMessages(['end_date' => 'این کشت هنوز پایان نیافته است.']);
            }
            $referenceDate = $locked->harvest_started_on?->format('Y-m-d') ?? $locked->planting_date->format('Y-m-d');
            $data = Validator::make(['end_date' => $endDate, 'yield_kg' => $yieldKg], [
                'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.$referenceDate, 'before_or_equal:today'],
                'yield_kg' => ['nullable', 'numeric', 'min:0', 'max:9999999999.99'],
            ], [
                'end_date.required' => 'تاریخ پایان برداشت را وارد کنید.',
                'end_date.date_format' => 'تاریخ پایان معتبر نیست.',
                'end_date.after_or_equal' => 'تاریخ پایان نمی‌تواند پیش از کاشت یا شروع برداشت باشد.',
                'end_date.before_or_equal' => 'تاریخ پایان نمی‌تواند در آینده باشد.',
                'yield_kg.numeric' => 'مقدار برداشت باید عدد باشد.',
                'yield_kg.min' => 'مقدار برداشت نمی‌تواند منفی باشد.',
            ])->validate();
            $locked->forceFill(['harvest_ended_on' => $data['end_date'], 'total_yield_kg' => $data['yield_kg'] ?? null])->save();
        }, 3);
    }
}
