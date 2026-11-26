<?php

namespace App\Models\Concerns;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait VisibleToUser
{
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasAnyRole([Role::SuperAdmin->value, Role::Admin->value, Role::Expert->value])) {
            return $query;
        }

        if (! $user->hasRole(Role::GreenhouseOwner->value)) {
            return $query->whereRaw('1 = 0');
        }

        $customerIds = DB::table('customer_user')->where('user_id', $user->id)->select('customer_id');

        return match ($this->getTable()) {
            'customers' => $query->whereIn('customers.id', $customerIds),
            'greenhouses', 'activities' => $query->whereIn($this->getTable().'.customer_id', $customerIds),
            'zones', 'cultivation_cycles' => $query->whereIn($this->getTable().'.greenhouse_id', DB::table('greenhouses')->whereIn('customer_id', $customerIds)->whereNull('deleted_at')->select('id')),
            default => $query->whereRaw('1 = 0'),
        };
    }
}
