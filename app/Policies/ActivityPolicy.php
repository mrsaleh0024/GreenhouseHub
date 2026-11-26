<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityPolicy extends DomainPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::Admin->value, Role::GreenhouseOwner->value]);
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Model $model): bool
    {
        return false;
    }

    public function delete(User $user, Model $model): bool
    {
        return false;
    }

    public function restore(User $user, Model $model): bool
    {
        return false;
    }
}
