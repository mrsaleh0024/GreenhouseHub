<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\CorrectionRequest;
use App\Models\CultivationCycle;
use App\Models\Greenhouse;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CorrectionRequestService
{
    public function request(User $owner, Greenhouse $greenhouse, string $details, ?CultivationCycle $cycle = null): CorrectionRequest
    {
        abort_unless($owner->hasRole(Role::GreenhouseOwner->value), 403);
        Gate::forUser($owner)->authorize('update', $greenhouse);
        Validator::make(['details' => trim($details)], ['details' => ['required', 'string', 'min:5', 'max:2000']], [
            'details.required' => 'توضیح درخواست را وارد کنید.', 'details.min' => 'توضیح درخواست باید دست‌کم ۵ نویسه باشد.',
            'details.max' => 'توضیح درخواست بیش از حد طولانی است.',
        ])->validate();

        if ($cycle) {
            $cycle = CultivationCycle::whereKey($cycle->id)->where('greenhouse_id', $greenhouse->id)->first();
            if (! $cycle || ($cycle->harvest_ended_on === null && ! in_array($cycle->status, ['completed', 'aborted'], true))) {
                throw ValidationException::withMessages(['cycle' => 'درخواست اصلاح فقط برای سوابق بسته‌شده این گلخانه مجاز است.']);
            }
        }

        return DB::transaction(function () use ($owner, $greenhouse, $details, $cycle) {
            $request = new CorrectionRequest(['greenhouse_id' => $greenhouse->id, 'cultivation_cycle_id' => $cycle?->id, 'details' => trim($details)]);
            $request->requested_by = $owner->id;
            $request->save();
            app(ActivityLogger::class)->log($greenhouse, 'correction_requested');

            return $request;
        });
    }

    public function resolve(User $admin, CorrectionRequest $request, string $resolution): void
    {
        abort_unless($admin->hasAnyRole([Role::Admin->value, Role::SuperAdmin->value]), 403);
        Validator::make(['resolution' => trim($resolution)], ['resolution' => ['required', 'string', 'min:5', 'max:2000']], [
            'resolution.required' => 'توضیح نتیجه را وارد کنید.', 'resolution.min' => 'توضیح نتیجه باید دست‌کم ۵ نویسه باشد.',
            'resolution.max' => 'توضیح نتیجه بیش از حد طولانی است.',
        ])->validate();

        DB::transaction(function () use ($admin, $request, $resolution) {
            $locked = CorrectionRequest::whereKey($request->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                throw ValidationException::withMessages(['resolution' => 'این درخواست پیش‌تر بررسی شده است.']);
            }
            $locked->forceFill(['status' => 'resolved', 'resolution' => trim($resolution), 'resolved_by' => $admin->id, 'resolved_at' => now()])->save();
            app(ActivityLogger::class)->log($locked->greenhouse, 'correction_resolved');
        });
    }
}
