<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CustomerLinker
{
    public function linkUserToCustomer(User $user): void
    {
        if (! $user->isActive() || ! $user->isMobileVerified() || ! $user->hasRole(Role::GreenhouseOwner->value)) {
            return;
        }

        DB::transaction(function () use ($user) {
            $customer = Customer::where('mobile', $user->mobile)->lockForUpdate()->first();
            if ($customer && ! $customer->users()->exists() && ! $user->customers()->exists()) {
                $customer->users()->attach($user->id, ['is_primary' => true]);
                Activity::create(['actor_id' => $user->id, 'actor_role' => $user->primaryRole()?->value, 'event' => 'user_linked', 'subject_type' => 'customer', 'subject_id' => $customer->id, 'customer_id' => $customer->id, 'description' => 'حساب کاربری به مشتری متصل شد']);
            }
        }, 3);
    }
}
