<?php

namespace App\Console\Commands;

use App\Models\Activity;
use App\Models\CatalogItem;
use App\Models\CultivationCycle;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\Zone;
use App\Support\DataClassification;
use App\Support\SafeDataValue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use JsonException;

class ReportSensitiveActivityData extends Command
{
    protected $signature = 'audit:report-sensitive
                            {--include-internal : Include internal and unclassified fields}';

    protected $description = 'Report potentially sensitive historical activity data without displaying stored values';

    public function handle(): int
    {
        if (! Schema::hasTable('activities')) {
            $this->error('The activities table does not exist.');

            return self::FAILURE;
        }

        $includeInternal = (bool) $this->option('include-internal');
        $report = [];
        $affectedRecords = 0;
        $invalidRecords = 0;

        Activity::query()
            ->select(['id', 'subject_type', 'changes'])
            ->chunkById(500, function ($activities) use (
                &$report,
                &$affectedRecords,
                &$invalidRecords,
                $includeInternal
            ): void {
                foreach ($activities as $activity) {
                    $table = $this->subjectTable(
                        (string) $activity->subject_type
                    );

                    $raw = $activity->getRawOriginal('changes');

                    if ($raw === null) {
                        continue;
                    }

                    try {
                        $payload = json_decode(
                            $raw,
                            true,
                            512,
                            JSON_THROW_ON_ERROR,
                        );
                    } catch (JsonException) {
                        $invalidRecords++;
                        continue;
                    }

                    if ($payload === null) {
                        continue;
                    }

                    if (! is_array($payload)) {
                        $invalidRecords++;
                        continue;
                    }

                    $fields = [];

                    $this->collectFields($table, $payload, $fields);

                    $recordHasSensitiveField = false;

                    foreach ($fields as $field => $classification) {
                        if (
                            $classification === DataClassification::INTERNAL
                            && ! $includeInternal
                        ) {
                            continue;
                        }

                        $bucket = $table.'|'.$field.'|'.$classification;

                        $report[$bucket] ??= [
                            'subject' => $table,
                            'field' => $field,
                            'classification' => $classification,
                            'records' => 0,
                        ];

                        $report[$bucket]['records']++;

                        $recordHasSensitiveField = true;
                    }

                    if ($recordHasSensitiveField) {
                        $affectedRecords++;
                    }
                }
            });

        if ($report === []) {
            $this->info(
                'No historical sensitive fields were found in activity changes.'
            );
        } else {
            ksort($report);

            $this->warn(
                'Potentially sensitive historical fields detected. Values are not displayed.'
            );

            $this->table(
                ['Subject', 'Field', 'Classification', 'Records'],
                array_map(
                    static fn (array $row): array => [
                        $row['subject'],
                        $row['field'],
                        $row['classification'],
                        $row['records'],
                    ],
                    array_values($report),
                ),
            );
        }

        $this->line('Affected activity records: '.$affectedRecords);
        $this->line('Invalid payload records: '.$invalidRecords);

        $this->line(
            'No records were modified or deleted. Review retention and remediation with the data owner.'
        );

        return $invalidRecords > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function subjectTable(string $subjectType): string
    {
        return match ($subjectType) {
            'customer', Customer::class => 'customers',
            'greenhouse', Greenhouse::class => 'greenhouses',
            'zone', Zone::class => 'zones',
            'cultivation_cycle', CultivationCycle::class => 'cultivation_cycles',
            'catalog_item', CatalogItem::class => 'catalog_items',
            default => DataClassification::hasTable($subjectType)
                && ! DataClassification::isExcludedTable($subjectType)
                    ? $subjectType
                    : '__unknown_subject',
        };
    }

    private function isMarker(mixed $value): bool
    {
        return is_array($value)
            && count($value) === 2
            && ($value['redacted'] ?? null) === true
            && in_array(
                $value['classification'] ?? null,
                [
                    DataClassification::INTERNAL,
                    DataClassification::PII,
                    DataClassification::CREDENTIAL,
                ],
                true,
            );
    }

    private function containsRawValue(mixed $value): bool
    {
        if ($this->isMarker($value)) {
            return false;
        }

        if (! is_array($value)) {
            return $value !== null && $value !== '';
        }

        foreach ($value as $child) {
            if ($this->containsRawValue($child)) {
                return true;
            }
        }

        return false;
    }

    private function collectFields(
        string $table,
        mixed $value,
        array &$fields,
    ): void {
        if (! is_array($value) || $this->isMarker($value)) {
            return;
        }

        foreach ($value as $key => $child) {
            if (
                is_int($key)
                || in_array($key, ['old', 'new'], true)
            ) {
                $this->collectFields($table, $child, $fields);
                continue;
            }

            $field = strtolower($key);

            if ($field === '__unknown' && $this->isMarker($child)) {
                continue;
            }

            $known = false;

            if (DataClassification::isCredentialKey($field)) {
                $classification = DataClassification::CREDENTIAL;
                $known = true;
            } elseif (DataClassification::hasColumn($table, $field)) {
                $classification = DataClassification::classify(
                    $table,
                    $field,
                );
                $known = true;
            } elseif (in_array(
                $field,
                config('data_classification.sensitive_keys', []),
                true,
            )) {
                $classification = DataClassification::PII;
                $known = true;
            } elseif (
                $table === 'greenhouses'
                && in_array($field, [
                    'options',
                    'irrigation_type',
                    'heating_system',
                    'cooling_system',
                    'water_source',
                    'ventilation_system',
                ], true)
            ) {
                $classification = DataClassification::SAFE;
                $known = true;
            } elseif (
                $table === 'zones'
                && in_array($field, [
                    'equipment',
                    'merge',
                    'equipment_id',
                    'id',
                    'quantity',
                ], true)
            ) {
                $classification = DataClassification::SAFE;
                $known = true;
            } else {
                $classification = DataClassification::INTERNAL;
            }

            if (! $known) {
                // Unknown keys may themselves contain private information.
                // Never copy them into the report.
                $fields['__unknown_field'] = DataClassification::INTERNAL;
            } elseif (
                $classification !== DataClassification::SAFE
                && $this->containsRawValue($child)
            ) {
                $fields[$field] = $classification;
            }

            if (
                $known
                && $classification === DataClassification::SAFE
                && DataClassification::hasColumn($table, $field)
                && $this->hasInvalidSafeValue($table, $field, $child)
            ) {
                $fields['__invalid_safe_value'] = DataClassification::INTERNAL;
            }

            $this->collectFields($table, $child, $fields);
        }
    }

    private function hasInvalidSafeValue(
        string $table,
        string $column,
        mixed $value,
    ): bool {
        if ($this->isMarker($value)) {
            return false;
        }

        if (
            is_array($value)
            && (
                array_key_exists('old', $value)
                || array_key_exists('new', $value)
            )
        ) {
            foreach (['old', 'new'] as $side) {
                if (
                    array_key_exists($side, $value)
                    && $this->hasInvalidSafeValue(
                        $table,
                        $column,
                        $value[$side],
                    )
                ) {
                    return true;
                }
            }

            return false;
        }

        return $this->containsRawValue($value)
            && ! SafeDataValue::accepts($table, $column, $value);
    }
}
