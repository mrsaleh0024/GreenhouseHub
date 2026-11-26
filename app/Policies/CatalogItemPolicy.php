<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\User;

class CatalogItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasAnyRole([Role::Admin->value, Role::Expert->value, Role::GreenhouseOwner->value]);
    }

    public function view(User $user, CatalogItem $item): bool
    {
        return $this->viewAny($user) && ($item->status === 'approved' || ($item->status === 'pending' && ($user->id === $item->created_by || $user->hasAnyRole([Role::Admin->value, Role::Expert->value]))));
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, CatalogItem $item): bool
    {
        return $user->hasRole(Role::Admin->value);
    }

    public function approve(User $user, CatalogItem $item): bool
    {
        return $this->update($user, $item);
    }

    public function merge(User $user, CatalogItem $item): bool
    {
        return $this->update($user, $item);
    }

    public function reject(User $user, CatalogItem $item): bool
    {
        return $this->update($user, $item);
    }
}
