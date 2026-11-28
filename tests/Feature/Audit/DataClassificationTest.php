<?php

namespace Tests\Feature\Audit;

use App\Support\DataClassification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DataClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_application_table_and_column_is_classified(): void
    {
        $tables = Schema::getTableListing(null, false);

        $this->assertContains('activities', $tables);
        $this->assertContains('customers', $tables);
        $this->assertContains('users', $tables);

        foreach ($tables as $table) {
            if (DataClassification::isExcludedTable($table)) {
                continue;
            }

            $this->assertTrue(
                DataClassification::hasTable($table),
                "Table [{$table}] is not classified in config/data_classification.php.",
            );

            $actual = Schema::getColumnListing($table);
            $classified = array_keys(DataClassification::tableColumns($table));
            sort($actual);
            sort($classified);

            $this->assertSame($actual, $classified, "Columns for [{$table}] are not fully classified.");

            foreach (DataClassification::tableColumns($table) as $column => $classification) {
                $this->assertContains(
                    $classification,
                    ['safe', 'internal', 'pii', 'credential'],
                    "Invalid classification [{$classification}] for {$table}.{$column}.",
                );
            }
        }
    }

    public function test_infrastructure_tables_are_explicitly_excluded(): void
    {
        foreach (['cache', 'cache_locks', 'failed_jobs', 'job_batches', 'jobs', 'migrations', 'permissions', 'roles', 'sessions'] as $table) {
            $this->assertTrue(DataClassification::isExcludedTable($table), "Infrastructure table [{$table}] is not excluded.");
        }

        foreach (['activities', 'customers', 'greenhouses', 'users'] as $table) {
            $this->assertFalse(DataClassification::isExcludedTable($table), "Application table [{$table}] was wrongly excluded.");
        }
    }

    public function test_unclassified_fields_fail_closed(): void
    {
        $this->assertSame('internal', DataClassification::defaultClassification());
        $this->assertSame('internal', DataClassification::classify('unknown_table', 'unknown_column'));
        $this->assertSame('internal', DataClassification::classify('customers', 'unknown_column'));
        $this->assertSame('internal', DataClassification::classify('sessions', 'payload'));
        $this->assertFalse(DataClassification::isSafe('customers', 'unknown_column'));
        $this->assertTrue(DataClassification::requiresRedaction('customers', 'unknown_column'));
    }

    public function test_ai_export_only_contains_safe_columns(): void
    {
        $row = [
            'id' => 10,
            'customer_code' => 'CU-000010',
            'full_name' => 'Ali Rezaei',
            'mobile' => '09123456789',
            'email' => 'ali@example.com',
            'address' => 'Secret Street',
            'notes' => 'Internal note',
            'search_text' => 'internal search index',
            'status' => 'active',
            'created_at' => '2026-11-28 10:00:00',
        ];

        $this->assertSame([
            'id' => 10,
            'customer_code' => 'CU-000010',
            'status' => 'active',
            'created_at' => '2026-11-28 10:00:00',
        ], DataClassification::aiExportable('customers', $row));

        $this->assertSame([], DataClassification::aiExportable('unknown_table', ['id' => 1]));
        $this->assertSame([], DataClassification::aiExportable('sessions', ['user_id' => 1]));
    }

    public function test_activity_payload_keys_are_classified(): void
    {
        $this->assertSame('safe', DataClassification::classifyActivityKey('greenhouses', 'options'));
        $this->assertSame('container', DataClassification::classifyActivityKey('zones', 'equipment'));
        $this->assertSame('internal', DataClassification::classifyActivityKey('zones', 'notes'));
        $this->assertSame('credential', DataClassification::classifyActivityKey('users', 'password'));
        $this->assertSame('pii', DataClassification::classifyActivityKey('customers', 'national_id'));
        $this->assertSame('internal', DataClassification::classifyActivityKey('customers', 'unknown_key'));
    }
}
