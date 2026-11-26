<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ZonePolicy extends DomainPolicy
{
    public function update(User $user, Model $model): bool
    {
        return $this->admin($user)
            || ($user->hasRole(Role::GreenhouseOwner->value) && $this->view($user, $model));
    }
}
