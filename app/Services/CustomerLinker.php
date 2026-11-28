<?php

namespace App\Services;

use App\Enums\Role;
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
                app(ActivityLogger::class)->log(
                    $customer,
                    'user_linked',
                    description: 'حساب کاربری به مشتری متصل شد',
                    actor: $user,
                );
            }
        }, 3);
    }
}
