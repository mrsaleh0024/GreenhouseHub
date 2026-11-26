<?php

namespace App\Services;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;

class CatalogVisibility
{
    public static function constrain(EloquentBuilder|QueryBuilder $query, User $user, array $attachedIds = []): void
    {
        $staff = $user->hasAnyRole([Role::SuperAdmin->value, Role::Admin->value, Role::Expert->value]);
        $claims = array_keys(session()->get('catalog_claims.'.$user->id, []));
        $knownIds = array_values(array_unique(array_map('intval', array_merge($attachedIds, $claims))));

        $query->where(function ($query) use ($user, $staff, $knownIds) {
            $query->where('status', 'approved')->orWhere(function ($query) use ($user, $staff, $knownIds) {
                $query->where('status', 'pending');
                if (! $staff) {
                    $query->where(function ($query) use ($user, $knownIds) {
                        $query->where('created_by', $user->id);
                        if ($knownIds !== []) {
                            $query->orWhereIn('id', $knownIds);
                        }
                    });
                }
            });
        });
    }

    public static function rememberClaim(User $user, int $itemId): void
    {
        session()->put('catalog_claims.'.$user->id.'.'.$itemId, true);
    }
}
