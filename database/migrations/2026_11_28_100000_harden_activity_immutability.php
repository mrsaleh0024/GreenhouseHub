<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Defence in depth for audit-log immutability.
 *
 * The Activity model and its query builder already reject updates/deletes.
 * These triggers also protect against direct query-builder/DB facade writes
 * and bulk operations that bypass Eloquent. Unsupported drivers are left to
 * the application-level guard; grant production DB users only the minimum
 * privileges required by the application.
 */
return new class extends Migration
{
    public function up(): void
    {
        match (DB::connection()->getDriverName()) {
            'sqlite' => $this->sqliteUp(),
            'mysql', 'mariadb' => $this->mysqlUp(),
            'pgsql' => $this->pgsqlUp(),
            default => null,
        };
    }

    public function down(): void
    {
        match (DB::connection()->getDriverName()) {
            'sqlite' => $this->sqliteDown(),
            'mysql', 'mariadb' => $this->mysqlDown(),
            'pgsql' => $this->pgsqlDown(),
            default => null,
        };
    }

    private function sqliteUp(): void
    {
        DB::statement("CREATE TRIGGER IF NOT EXISTS activities_prevent_update BEFORE UPDATE ON activities BEGIN SELECT RAISE(ABORT, 'activities are immutable'); END");
        DB::statement("CREATE TRIGGER IF NOT EXISTS activities_prevent_delete BEFORE DELETE ON activities BEGIN SELECT RAISE(ABORT, 'activities are immutable'); END");
    }

    private function sqliteDown(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS activities_prevent_update');
        DB::statement('DROP TRIGGER IF EXISTS activities_prevent_delete');
    }

    private function mysqlUp(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS activities_prevent_update');
        DB::statement('DROP TRIGGER IF EXISTS activities_prevent_delete');
        DB::statement("CREATE TRIGGER activities_prevent_update BEFORE UPDATE ON activities FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'activities are immutable'");
        DB::statement("CREATE TRIGGER activities_prevent_delete BEFORE DELETE ON activities FOR EACH ROW SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'activities are immutable'");
    }

    private function mysqlDown(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS activities_prevent_update');
        DB::statement('DROP TRIGGER IF EXISTS activities_prevent_delete');
    }

    private function pgsqlUp(): void
    {
        DB::statement("CREATE OR REPLACE FUNCTION activities_immutable() RETURNS trigger AS $$ BEGIN RAISE EXCEPTION 'activities are immutable'; END; $$ LANGUAGE plpgsql");
        DB::statement('DROP TRIGGER IF EXISTS activities_prevent_update ON activities');
        DB::statement('DROP TRIGGER IF EXISTS activities_prevent_delete ON activities');
        DB::statement('CREATE TRIGGER activities_prevent_update BEFORE UPDATE ON activities FOR EACH ROW EXECUTE FUNCTION activities_immutable()');
        DB::statement('CREATE TRIGGER activities_prevent_delete BEFORE DELETE ON activities FOR EACH ROW EXECUTE FUNCTION activities_immutable()');
    }

    private function pgsqlDown(): void
    {
        DB::statement('DROP TRIGGER IF EXISTS activities_prevent_update ON activities');
        DB::statement('DROP TRIGGER IF EXISTS activities_prevent_delete ON activities');
        DB::statement('DROP FUNCTION IF EXISTS activities_immutable()');
    }
};
