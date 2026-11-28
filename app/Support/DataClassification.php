<?php

namespace App\Support;

/**
 * Central, fail-closed registry for application data classification.
 *
 * Unknown tables and columns are treated as "internal" (never safe for
 * export). Infrastructure tables are explicitly excluded and are never
 * eligible for export.
 */
final class DataClassification
{
    public const SAFE = 'safe';

    public const INTERNAL = 'internal';

    public const PII = 'pii';

    public const CREDENTIAL = 'credential';

    public const CONTAINER = 'container';

    /** @return array<string, array<string, string>> */
    public static function tables(): array
    {
        return config('data_classification.tables', []);
    }

    /** @return array<string, string> */
    public static function tableColumns(string $table): array
    {
        return self::tables()[$table] ?? [];
    }

    public static function hasTable(string $table): bool
    {
        return array_key_exists($table, self::tables());
    }

    public static function hasColumn(string $table, string $column): bool
    {
        return array_key_exists($column, self::tableColumns($table));
    }

    /** @return array<string, string> */
    public static function excludedTables(): array
    {
        return config('data_classification.excluded_tables', []);
    }

    public static function isExcludedTable(string $table): bool
    {
        return array_key_exists($table, self::excludedTables());
    }

    public static function defaultClassification(): string
    {
        return self::INTERNAL;
    }

    /**
     * Classify a column. Unknown columns and excluded/unknown tables fail
     * closed to "internal" rather than "safe".
     */
    public static function classify(string $table, string $column): string
    {
        if (self::isCredentialKey($column)) {
            return self::CREDENTIAL;
        }

        if (self::isExcludedTable($table) || ! self::hasTable($table)) {
            return self::INTERNAL;
        }

        $classification = self::tableColumns($table)[$column] ?? self::INTERNAL;

        if (! in_array($classification, [
            self::SAFE,
            self::INTERNAL,
            self::PII,
            self::CREDENTIAL,
        ], true)) {
            return self::INTERNAL;
        }

        return $classification;
    }

    /**
     * Classify a key inside an activity change payload. This includes
     * synthetic keys that are not table columns and may return "container".
     */
    public static function classifyActivityKey(string $table, string $key): string
    {
        $key = strtolower($key);

        if (self::isCredentialKey($key)) {
            return self::CREDENTIAL;
        }

        $activityKeys = config('data_classification.activity_change_keys', []);
        if (array_key_exists($key, $activityKeys)) {
            return $activityKeys[$key];
        }

        if (self::hasColumn($table, $key)) {
            return self::classify($table, $key);
        }

        if (in_array($key, config('data_classification.sensitive_keys', []), true)) {
            return self::PII;
        }

        return self::defaultClassification();
    }

    public static function isCredentialKey(string $key): bool
    {
        return in_array(strtolower($key), config('data_classification.credential_keys', []), true);
    }

    public static function isSafe(string $table, string $column): bool
    {
        return self::classify($table, $column) === self::SAFE;
    }

    public static function requiresRedaction(string $table, string $column): bool
    {
        return ! self::isSafe($table, $column);
    }

    /** @return list<string> */
    public static function safeColumns(string $table): array
    {
        if (self::isExcludedTable($table)) {
            return [];
        }

        return array_values(array_filter(
            array_keys(self::tableColumns($table)),
            static fn (string $column): bool => self::isSafe($table, $column),
        ));
    }

    /**
     * Return only the safe columns of a row. This is the default gate for
     * any future AI/export integration: PII, internal and credential data
     * are never included, and unknown tables/columns are never included.
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public static function aiExportable(string $table, array $row): array
    {
        if (self::isExcludedTable($table) || ! self::hasTable($table)) {
            return [];
        }

        $safe = [];

        foreach ($row as $column => $value) {
            if (
                self::hasColumn($table, (string) $column)
                && self::isSafe($table, (string) $column)
                && SafeDataValue::accepts($table, (string) $column, $value)
            ) {
                $safe[(string) $column] = $value;
            }
        }

        return $safe;
    }
}
