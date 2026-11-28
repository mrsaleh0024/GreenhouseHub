<?php

namespace App\Models\Builders;

use Illuminate\Database\Eloquent\Builder;
use LogicException;

/**
 * Blocks every mutating operation that would otherwise bypass the
 * Activity model events (bulk updates, deletes, upserts, inserts, etc.).
 */
final class ActivityBuilder extends Builder
{
    private const MESSAGE = 'رویدادها تغییرناپذیرند.';

    public function update(array $values): int
    {
        throw new LogicException(self::MESSAGE);
    }

    public function delete(): int
    {
        throw new LogicException(self::MESSAGE);
    }

    public function forceDelete(): int
    {
        throw new LogicException(self::MESSAGE);
    }

    public function upsert(array $values, $uniqueBy, $update = null): int
    {
        throw new LogicException(self::MESSAGE);
    }

    public function increment($column, $amount = 1, array $extra = []): int
    {
        throw new LogicException(self::MESSAGE);
    }

    public function decrement($column, $amount = 1, array $extra = []): int
    {
        throw new LogicException(self::MESSAGE);
    }

    public function incrementEach(array $columns, array $extra = []): array
    {
        throw new LogicException(self::MESSAGE);
    }

    public function decrementEach(array $columns, array $extra = []): array
    {
        throw new LogicException(self::MESSAGE);
    }

    public function __call($method, $parameters)
    {
        $mutating = [
            'insert',
            'insertgetid',
            'insertorignore',
            'insertorignoreusing',
            'insertusing',
            'truncate',
            'updateorinsert',
        ];

        if (in_array(strtolower($method), $mutating, true)) {
            throw new LogicException(self::MESSAGE);
        }

        return parent::__call($method, $parameters);
    }
}
