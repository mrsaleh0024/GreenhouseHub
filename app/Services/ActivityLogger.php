<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\CultivationCycle;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Eloquent\Model;

class ActivityLogger
{
    public function log(Model $subject, string $event, array $changes = [], ?string $description = null, ?User $actor = null): Activity
    {
        $greenhouse = match (true) {
            $subject instanceof Greenhouse => $subject,
            $subject instanceof Zone, $subject instanceof CultivationCycle => $subject->greenhouse()->withTrashed()->first(),
            default => null,
        };
        $actor ??= auth()->user();

        return Activity::create([
            'actor_id' => $actor?->id, 'actor_role' => $actor?->primaryRole()?->value,
            'event' => $event, 'subject_type' => $subject->getMorphClass(), 'subject_id' => $subject->getKey(),
            'customer_id' => $subject instanceof Customer ? $subject->id : $greenhouse?->customer_id,
            'greenhouse_id' => $greenhouse?->id, 'changes' => $changes ?: null,
            'description' => $description, 'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
