<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class DomainPolicy
{
    protected function staff(User $user): bool
    {
        return $user->hasAnyRole([Role::Admin->value, Role::Expert->value]);
    }

    protected function admin(User $user): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function viewAny(User $user): bool
    {
        return $this->staff($user) || $user->hasRole(Role::GreenhouseOwner->value);
    }

    public function view(User $user, Model $model): bool
    {
        return $model->newQuery()->visibleTo($user)->whereKey($model->getKey())->exists();
    }

    public function create(User $user): bool
    {
        return $this->admin($user);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->admin($user);
    }

    public function delete(User $user, Model $model): bool
    {
        return $this->admin($user);
    }

    public function restore(User $user, Model $model): bool
    {
        return $this->admin($user);
    }

    public function viewInternalNotes(User $user, Model $model): bool
    {
        return $this->admin($user);
    }

    public function changeStatus(User $user, Model $model): bool
    {
        return $this->admin($user);
    }

    public function updateContact(User $user, Model $model): bool
    {
        return $this->admin($user)
            || ($user->hasRole(Role::GreenhouseOwner->value) && $this->view($user, $model));
    }

    public function reopen(User $user, Model $model): bool
    {
        return $this->admin($user);
    }
}
