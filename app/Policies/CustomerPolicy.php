<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;

class CustomerPolicy extends DomainPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::Admin->value, Role::GreenhouseOwner->value]);
    }
}
