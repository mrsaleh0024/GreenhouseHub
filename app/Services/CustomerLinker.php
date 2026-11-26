<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\User;

class CustomerLinker
{
    public function linkUserToCustomer(User $user): void
    {
        $customer = Customer::where('mobile', $user->mobile)->first();
        if ($customer && ! $customer->users()->exists()) {
            $customer->users()->attach($user->id, ['is_primary' => true]);
            Activity::create(['actor_id' => $user->id, 'actor_role' => $user->primaryRole()?->value, 'event' => 'user_linked', 'subject_type' => 'customer', 'subject_id' => $customer->id, 'customer_id' => $customer->id, 'description' => 'حساب کاربری به مشتری متصل شد']);
        }
    }
}
