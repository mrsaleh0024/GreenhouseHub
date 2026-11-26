<?php

namespace App\Policies;

use App\Enums\Role;
use App\Models\CultivationCycle;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class CultivationCyclePolicy extends DomainPolicy
{
    public function update(User $user, Model $model): bool
    {
        if ($this->admin($user)) {
            return true;
        }

        return $model instanceof CultivationCycle
            && $user->hasRole(Role::GreenhouseOwner->value)
            && $model->harvest_ended_on === null
            && ! in_array($model->status, ['completed', 'aborted'], true)
            && $this->view($user, $model);
    }
}
