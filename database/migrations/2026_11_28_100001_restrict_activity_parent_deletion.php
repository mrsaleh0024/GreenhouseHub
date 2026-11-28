<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->replaceForeignKeys(true);
    }

    public function down(): void
    {
        $this->replaceForeignKeys(false);
    }

    private function replaceForeignKeys(bool $restrict): void
    {
        $driver = DB::connection()->getDriverName();

        // SQLite and PostgreSQL apply the existing row-level
        // activity triggers to foreign-key-induced updates.
        if (in_array($driver, ['sqlite', 'pgsql'], true)) {
            return;
        }

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new LogicException(
                'Unsupported audit database driver.'
            );
        }

        $constraints = [
            'actor_id' => [
                'users',
                'activity_actor_fk',
                'activity_actor_restrict_fk',
            ],
            'customer_id' => [
                'customers',
                'activity_customer_fk',
                'activity_customer_restrict_fk',
            ],
            'greenhouse_id' => [
                'greenhouses',
                'activity_greenhouse_fk',
                'activity_greenhouse_restrict_fk',
            ],
        ];

        $drop = [];
        $add = [];

        foreach ($constraints as $column => [$table, $old, $new]) {
            $current = $restrict ? $old : $new;
            $replacement = $restrict ? $new : $old;
            $deleteRule = $restrict ? 'RESTRICT' : 'SET NULL';

            $drop[] = "DROP FOREIGN KEY `{$current}`";

            $add[] = "ADD CONSTRAINT `{$replacement}` "
                ."FOREIGN KEY (`{$column}`) "
                ."REFERENCES `{$table}` (`id`) "
                ."ON DELETE {$deleteRule} ON UPDATE RESTRICT";
        }

        DB::statement(
            'ALTER TABLE `activities` '
            .implode(', ', array_merge($drop, $add))
        );
    }
};
