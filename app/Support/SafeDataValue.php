<?php

namespace App\Support;

use App\Enums\CatalogType;

/**
 * Validates values eligible for audit retention or controlled AI export.
 * Classification alone does not make arbitrary input safe.
 */
final class SafeDataValue
{
    private const RULES = [
        'users' => [],
        'expert_profiles' => [
            'experience_years' => 'uint8',
        ],
        'code_sequences' => [
            'last_value' => 'uint',
        ],
        'catalog_items' => [
            'type' => 'catalog_type',
            'parent_id' => 'id',
            'parent_key' => 'uint',
            'source_ref' => 'source_ref',
            'location_ambiguous' => 'bool',
            'status' => 'catalog_status',
            'source' => 'catalog_source',
            'is_common' => 'bool',
            'sort_order' => 'uint16',
            'merged_into_id' => 'id',
        ],
        'customers' => [
            'customer_code' => 'customer_code',
            'customer_type' => 'customer_type',
            'province_id' => 'id',
            'city_id' => 'id',
            'status' => 'customer_status',
            'registered_via' => 'registered_via',
        ],
        'customer_user' => [
            'customer_id' => 'id',
            'is_primary' => 'bool',
        ],
        'greenhouses' => [
            'greenhouse_code' => 'greenhouse_code',
            'customer_id' => 'id',
            'province_id' => 'id',
            'city_id' => 'id',
            'altitude_m' => 'altitude',
            'area_m2' => 'area',
            'structure_type_id' => 'id',
            'cultivation_type_id' => 'id',
            'commissioned_on' => 'date',
            'status' => 'greenhouse_status',
            'completeness_percent' => 'percent',
        ],
        'greenhouse_options' => [
            'greenhouse_id' => 'id',
            'catalog_item_id' => 'id',
        ],
        'zones' => [
            'greenhouse_id' => 'id',
            'code' => 'zone_code',
            'zone_type_id' => 'id',
            'area_m2' => 'area',
            'capacity_plants' => 'uint32',
            'status' => 'zone_status',
        ],
        'zone_equipment' => [
            'zone_id' => 'id',
            'equipment_id' => 'id',
            'quantity' => 'quantity',
        ],
        'cultivation_cycles' => [
            'cycle_code' => 'cycle_code',
            'greenhouse_id' => 'id',
            'zone_id' => 'id',
            'crop_id' => 'id',
            'variety_id' => 'id',
            'substrate_id' => 'id',
            'planting_date' => 'date',
            'expected_harvest_date' => 'date',
            'harvest_started_on' => 'date',
            'harvest_ended_on' => 'date',
            'plant_count' => 'uint32',
            'planted_area_m2' => 'area',
            'status' => 'cycle_status',
            'total_yield_kg' => 'yield',
        ],
        'correction_requests' => [
            'greenhouse_id' => 'id',
            'cultivation_cycle_id' => 'id',
            'status' => 'correction_status',
        ],
        'activities' => [
            'occurred_at' => 'datetime',
            'event' => 'activity_event',
            'subject_type' => 'subject_type',
            'subject_id' => 'id',
            'customer_id' => 'id',
            'greenhouse_id' => 'id',
        ],
    ];

    private const ENUMS = [
        'catalog_status' => [
            'approved', 'pending', 'merged', 'rejected',
        ],
        'catalog_source' => [
            'system', 'user',
        ],
        'customer_type' => [
            'individual', 'company',
        ],
        'customer_status' => [
            'active', 'inactive',
        ],
        'registered_via' => [
            'staff', 'self_registration',
        ],
        'greenhouse_status' => [
            'active', 'inactive', 'archived', 'under_construction',
        ],
        'zone_status' => [
            'active', 'inactive',
        ],
        'cycle_status' => [
            'planned', 'growing', 'harvesting',
            'completed', 'aborted',
        ],
        'correction_status' => [
            'pending', 'resolved',
        ],
        'activity_event' => [
            'created',
            'updated',
            'deleted',
            'restored',
            'status_changed',
            'equipment_changed',
            'options_changed',
            'catalog_approved',
            'catalog_merged',
            'correction_requested',
            'correction_resolved',
            'user_linked',
        ],
        'subject_type' => [
            'customer',
            'greenhouse',
            'zone',
            'cultivation_cycle',
            'catalog_item',
            \App\Models\Customer::class,
            \App\Models\Greenhouse::class,
            \App\Models\Zone::class,
            \App\Models\CultivationCycle::class,
            \App\Models\CatalogItem::class,
        ],
    ];

    private static function rule(
        string $table,
        string $column,
    ): ?string {
        if (! array_key_exists($table, self::RULES)) {
            return null;
        }

        if (
            ! DataClassification::hasColumn($table, $column)
            || ! DataClassification::isSafe($table, $column)
        ) {
            return null;
        }

        return self::RULES[$table][$column] ?? match ($column) {
            'id' => 'id',
            'created_at', 'updated_at' => 'datetime',
            default => null,
        };
    }

    public static function hasRule(
        string $table,
        string $column,
    ): bool {
        return self::rule($table, $column) !== null;
    }

    public static function accepts(
        string $table,
        string $column,
        mixed $value,
    ): bool {
        $rule = self::rule($table, $column);

        if ($rule === null) {
            return false;
        }

        // Null contains no retained sensitive value. Database and domain
        // validation remain responsible for required/nullable fields.
        if ($value === null) {
            return true;
        }

        if (isset(self::ENUMS[$rule])) {
            return is_string($value)
                && in_array($value, self::ENUMS[$rule], true);
        }

        return match ($rule) {
            'id' => self::integer($value, 1, PHP_INT_MAX),
            'uint' => self::integer($value, 0, PHP_INT_MAX),
            'uint8' => self::integer($value, 0, 255),
            'uint16' => self::integer($value, 0, 65535),
            'uint32' => self::integer($value, 0, 4294967295),
            'quantity' => self::integer($value, 1, 65535),
            'percent' => self::integer($value, 0, 100),
            'altitude' => self::integer($value, -32768, 32767),
            'area' => self::decimal($value, 0.01, 99999999.99),
            'yield' => self::decimal($value, 0, 9999999999.99),
            'bool' => in_array(
                $value,
                [true, false, 0, 1, '0', '1'],
                true,
            ),
            'catalog_type' => is_string($value)
                && CatalogType::tryFrom($value) !== null,
            'source_ref' => self::matches(
                $value,
                '/\A[0-9]{1,19}\z/',
            ),
            'customer_code' => self::matches(
                $value,
                '/\ACU-[0-9]{6,17}\z/',
            ),
            'greenhouse_code' => self::matches(
                $value,
                '/\AGH-[0-9]{6,17}\z/',
            ),
            'cycle_code' => self::matches(
                $value,
                '/\ACC-[0-9]{6,17}\z/',
            ),
            'zone_code' => self::matches(
                $value,
                '/\AZ[0-9]{2,9}\z/',
            ),
            'date' => self::dateValue($value, false),
            'datetime' => self::dateValue($value, true),
            default => false,
        };
    }

    private static function matches(
        mixed $value,
        string $pattern,
    ): bool {
        return is_string($value)
            && preg_match($pattern, $value) === 1;
    }

    private static function integer(
        mixed $value,
        int $minimum,
        int $maximum,
    ): bool {
        if (! is_int($value) && ! is_string($value)) {
            return false;
        }

        if (
            is_string($value)
            && preg_match('/\A-?(0|[1-9][0-9]*)\z/', $value) !== 1
        ) {
            return false;
        }

        return filter_var(
            $value,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => $minimum,
                    'max_range' => $maximum,
                ],
            ],
        ) !== false;
    }

    private static function decimal(
        mixed $value,
        float $minimum,
        float $maximum,
    ): bool {
        if (
            ! is_int($value)
            && ! is_float($value)
            && ! is_string($value)
        ) {
            return false;
        }

        if (
            is_string($value)
            && preg_match(
                '/\A[0-9]+(?:\.[0-9]{1,2})?\z/',
                $value,
            ) !== 1
        ) {
            return false;
        }

        $number = (float) $value;

        return is_finite($number)
            && $number >= $minimum
            && $number <= $maximum
            && abs($number - round($number, 2)) < 0.0000001;
    }

    private static function dateValue(
        mixed $value,
        bool $withTime,
    ): bool {
        if (! is_string($value)) {
            return false;
        }

        $pattern = $withTime
            ? '/\A(\d{4})-(\d{2})-(\d{2})[ T]([01]\d|2[0-3]):([0-5]\d):([0-5]\d)(?:\.\d{1,6})?Z?\z/'
            : '/\A(\d{4})-(\d{2})-(\d{2})(?:[ T]00:00:00(?:\.0{1,6})?Z?)?\z/';

        if (preg_match($pattern, $value, $parts) !== 1) {
            return false;
        }

        return checkdate(
            (int) $parts[2],
            (int) $parts[3],
            (int) $parts[1],
        );
    }
}
