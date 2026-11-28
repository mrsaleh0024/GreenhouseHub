<?php

namespace Tests\Feature\Audit;

use App\Support\ActivityChangeRedactor;
use App\Support\DataClassification;
use App\Support\SafeDataValue;
use Tests\TestCase;

class SafeDataValueTest extends TestCase
{
    public function test_every_safe_column_has_a_value_rule(): void
    {
        foreach (DataClassification::tables() as $table => $columns) {
            foreach (DataClassification::safeColumns($table) as $column) {
                $this->assertTrue(
                    SafeDataValue::hasRule($table, $column),
                    "Missing value rule: {$table}.{$column}",
                );
            }
        }
    }

    public function test_valid_domain_values_are_preserved(): void
    {
        $cases = [
            ['customers', 'id', 1],
            ['customers', 'id', '123'],
            ['customers', 'status', 'active'],
            ['greenhouses', 'status', 'under_construction'],
            ['customers', 'customer_code', 'CU-000001'],
            ['greenhouses', 'greenhouse_code', 'GH-000001'],
            ['greenhouses', 'area_m2', '1250.50'],
            ['greenhouses', 'completeness_percent', 100],
            ['greenhouses', 'altitude_m', -100],
            ['zones', 'code', 'Z01'],
            ['zone_equipment', 'quantity', 65535],
            ['cultivation_cycles', 'cycle_code', 'CC-000001'],
            ['cultivation_cycles', 'status', 'harvesting'],
            ['cultivation_cycles', 'planting_date', '2026-11-28'],
            ['cultivation_cycles', 'total_yield_kg', '450.25'],
            ['catalog_items', 'type', 'equipment'],
            ['catalog_items', 'source_ref', '1234'],
            ['catalog_items', 'parent_key', 0],
            ['catalog_items', 'is_common', false],
            ['customers', 'created_at', '2026-11-28T10:00:00.000000Z'],
        ];

        foreach ($cases as [$table, $column, $value]) {
            $this->assertTrue(
                SafeDataValue::accepts($table, $column, $value),
                "{$table}.{$column}",
            );

            $this->assertSame(
                [$column => $value],
                DataClassification::aiExportable(
                    $table,
                    [$column => $value],
                ),
            );
        }
    }

    public function test_invalid_values_fail_closed(): void
    {
        $cases = [
            ['customers', 'id', 0],
            ['customers', 'id', -1],
            ['customers', 'id', true],
            ['customers', 'id', 'PRIVATE-ID'],
            ['customers', 'status', 'private@example.test'],
            ['customers', 'status', '09123456789'],
            ['customers', 'customer_code', 'PRIVATE-CODE'],
            ['greenhouses', 'area_m2', 'PRIVATE-AREA'],
            ['greenhouses', 'area_m2', -10],
            ['greenhouses', 'area_m2', '1e20'],
            ['greenhouses', 'area_m2', '12.345'],
            ['greenhouses', 'completeness_percent', 101],
            ['greenhouses', 'altitude_m', 32768],
            ['zone_equipment', 'quantity', 65536],
            ['zone_equipment', 'quantity', 0],
            ['catalog_items', 'is_common', 'yes'],
            ['catalog_items', 'type', 'PRIVATE-TYPE'],
            ['catalog_items', 'source_ref', 'PRIVATE-SOURCE'],
            ['cultivation_cycles', 'planting_date', '2026-02-30'],
            ['cultivation_cycles', 'status', 'PRIVATE-STATUS'],
            ['activities', 'event', 'PRIVATE-EVENT'],
            ['activities', 'subject_type', 'PRIVATE-SUBJECT'],
            ['customers', 'created_at', 'PRIVATE-DATE'],
            ['customers', 'created_at', '2026-11-28 99:00:00'],
            ['customers', 'unknown_column', 'PRIVATE-VALUE'],
            ['users', 'password', 'PRIVATE-PASSWORD'],
        ];

        foreach ($cases as [$table, $column, $value]) {
            $this->assertFalse(
                SafeDataValue::accepts($table, $column, $value),
                "{$table}.{$column}",
            );

            $this->assertSame(
                [],
                DataClassification::aiExportable(
                    $table,
                    [$column => $value],
                ),
            );
        }
    }

    public function test_audit_rejects_invalid_scalar_values(): void
    {
        $result = app(ActivityChangeRedactor::class)->redact(
            'customers',
            [
                'status' => [
                    'old' => 'active',
                    'new' => 'PRIVATE-STATUS',
                ],
                'customer_code' => [
                    'old' => 'CU-000001',
                    'new' => 'PRIVATE-CODE',
                ],
                'province_id' => [
                    'old' => 1,
                    'new' => 'PRIVATE-ID',
                ],
            ],
        );

        $this->assertSame('active', $result['status']['old']);
        $this->assertSame(
            'CU-000001',
            $result['customer_code']['old'],
        );
        $this->assertSame(1, $result['province_id']['old']);

        foreach (['status', 'customer_code', 'province_id'] as $field) {
            $this->assertSame(
                [
                    'redacted' => true,
                    'classification' => 'internal',
                ],
                $result[$field]['new'],
            );
        }

        $this->assertStringNotContainsString(
            'PRIVATE-',
            json_encode($result),
        );
    }

    public function test_equipment_quantities_respect_database_range(): void
    {
        $result = app(ActivityChangeRedactor::class)->redact(
            'zones',
            [
                'equipment' => [
                    'new' => [
                        ['equipment_id' => 1, 'quantity' => 65535],
                        ['equipment_id' => 2, 'quantity' => 65536],
                    ],
                ],
            ],
        );

        $this->assertSame(
            65535,
            $result['equipment']['new'][0]['quantity'],
        );

        $this->assertTrue(
            $result['equipment']['new'][1]['quantity']['redacted'],
        );
    }

    public function test_new_safe_column_without_rule_is_not_exportable(): void
    {
        config([
            'data_classification.tables.customers.future_column' => 'safe',
        ]);

        $this->assertFalse(
            SafeDataValue::hasRule('customers', 'future_column'),
        );

        $this->assertSame(
            [],
            DataClassification::aiExportable(
                'customers',
                ['future_column' => 'PRIVATE-VALUE'],
            ),
        );
    }
}
