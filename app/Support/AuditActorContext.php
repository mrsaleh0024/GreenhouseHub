<?php

namespace App\Support;

use App\Models\User;
use Closure;

/**
 * Propagates the trusted service actor to synchronous model events
 * without replacing the authenticated user.
 */
final class AuditActorContext
{
    private ?User $actor = null;

    public function current(): ?User
    {
        return $this->actor ?? auth()->user();
    }

    public function run(User $actor, Closure $operation): mixed
    {
        $previous = $this->actor;
        $this->actor = $actor;

        try {
            return $operation();
        } finally {
            $this->actor = $previous;
        }
    }
}
