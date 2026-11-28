<?php

namespace App\Support;

/**
 * Applies a fail-closed policy to activity payloads.
 *
 * Normal columns accept scalar values or old/new scalar pairs.
 * Synthetic payloads have explicit structural schemas.
 * Unknown keys and unsupported values are never copied verbatim.
 */
final class ActivityChangeRedactor
{
    private const OPTION_GROUPS = [
        'irrigation_type',
        'heating_system',
        'cooling_system',
        'water_source',
        'ventilation_system',
    ];

    public function redact(string $table, array $changes): array
    {
        $result = [];

        foreach ($changes as $field => $value) {
            $field = (string) $field;

            if (DataClassification::isCredentialKey($field)) {
                continue;
            }

            if (
                ! DataClassification::hasTable($table)
                || DataClassification::isExcludedTable($table)
            ) {
                $result['__unknown'] = $this->marker(DataClassification::INTERNAL);
                continue;
            }

            if ($table === 'greenhouses' && $field === 'options') {
                $result[$field] = $this->pair(
                    $value,
                    fn (mixed $item): mixed => $this->options($item),
                );
                continue;
            }

            if (
                $table === 'zones'
                && in_array($field, ['equipment', 'merge'], true)
            ) {
                $result[$field] = $this->pair(
                    $value,
                    fn (mixed $item): mixed => $this->equipment($item, $field),
                );
                continue;
            }

            if (! DataClassification::hasColumn($table, $field)) {
                $result['__unknown'] = $this->marker(DataClassification::INTERNAL);
                continue;
            }

            $classification = DataClassification::classify($table, $field);

            if ($classification === DataClassification::CREDENTIAL) {
                continue;
            }

            if ($classification === DataClassification::SAFE) {
                $result[$field] = $this->pair(
                    $value,
                    fn (mixed $item): mixed => $this->scalar($table, $field, $item),
                );
                continue;
            }

            $result[$field] = $this->pair(
                $value,
                fn (mixed $item): array => $this->marker($classification),
            );
        }

        return $result;
    }

    private function pair(mixed $value, callable $filter): mixed
    {
        if (
            is_array($value)
            && (
                array_key_exists('old', $value)
                || array_key_exists('new', $value)
            )
        ) {
            $result = [];

            foreach (['old', 'new'] as $key) {
                if (array_key_exists($key, $value)) {
                    $result[$key] = $filter($value[$key]);
                }
            }

            if (array_diff(array_keys($value), ['old', 'new']) !== []) {
                $result['__unknown'] = $this->marker(DataClassification::INTERNAL);
            }

            return $result;
        }

        return $filter($value);
    }

    private function scalar(
        string $table,
        string $column,
        mixed $value,
    ): mixed {
        if (SafeDataValue::accepts($table, $column, $value)) {
            return $value;
        }

        return $this->marker(DataClassification::INTERNAL);
    }

    private function positiveInteger(mixed $value, int $maximum = PHP_INT_MAX): mixed
    {
        if (! is_int($value) && ! is_string($value)) {
            return $this->marker(DataClassification::INTERNAL);
        }

        $validated = filter_var(
            $value,
            FILTER_VALIDATE_INT,
            ["options" => ["min_range" => 1, "max_range" => $maximum]],
        );

        return $validated === false
            ? $this->marker(DataClassification::INTERNAL)
            : $validated;
    }

    private function options(mixed $value): array
    {
        if (! is_array($value)) {
            return $this->marker(DataClassification::INTERNAL);
        }

        $result = [];

        foreach ($value as $group => $items) {
            if (! in_array($group, self::OPTION_GROUPS, true)) {
                $result['__unknown'] = $this->marker(DataClassification::INTERNAL);
                continue;
            }

            if (! is_array($items) || ! array_is_list($items)) {
                $result[$group] = $this->marker(DataClassification::INTERNAL);
                continue;
            }

            $result[$group] = array_map(
                fn (mixed $item): mixed => $this->positiveInteger($item),
                $items,
            );
        }

        return $result;
    }

    private function equipment(mixed $value, string $kind): array
    {
        if (! is_array($value)) {
            return $this->marker(DataClassification::INTERNAL);
        }

        if (array_is_list($value)) {
            return array_map(
                fn (mixed $row): array => $this->equipmentRow($row, $kind),
                $value,
            );
        }

        return $this->equipmentRow($value, $kind);
    }

    private function equipmentRow(mixed $value, string $kind): array
    {
        if (! is_array($value) || array_is_list($value)) {
            return $this->marker(DataClassification::INTERNAL);
        }

        $result = [];
        $identifier = $kind === 'merge' ? 'id' : 'equipment_id';

        foreach ($value as $field => $item) {
            if (DataClassification::isCredentialKey((string) $field)) {
                continue;
            }

            if (in_array($field, [$identifier, 'quantity'], true)) {
                $result[$field] = $this->positiveInteger($item, $field === "quantity" ? 65535 : PHP_INT_MAX);
                continue;
            }

            if ($field === 'notes') {
                $result[$field] = $this->marker(DataClassification::INTERNAL);
                continue;
            }

            $result['__unknown'] = $this->marker(DataClassification::INTERNAL);
        }

        return $result;
    }

    private function marker(string $classification): array
    {
        return [
            'redacted' => true,
            'classification' => $classification,
        ];
    }
}
