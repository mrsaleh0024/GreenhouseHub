<?php

namespace Tests\Feature\Audit;

use App\Models\Activity;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HistoricalActivityReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_historical_sensitive_fields_are_reported_without_exposing_or_deleting_values(): void
    {
        $customer = Customer::create(['full_name' => 'Ali Rezaei', 'mobile' => '09123456789']);

        $rawChanges = [
            'full_name' => ['old' => 'Old Secret Name', 'new' => 'New Secret Name'],
            'password' => ['old' => 'OldSecretPass', 'new' => 'NewSecretPass'],
            'notes' => ['old' => 'Old internal note', 'new' => 'New internal note'],
            'status' => ['old' => 'active', 'new' => 'inactive'],
        ];

        $activityId = DB::table('activities')->insertGetId([
            'occurred_at' => now(),
            'actor_id' => null,
            'actor_role' => null,
            'event' => 'updated',
            'subject_type' => 'customer',
            'subject_id' => $customer->id,
            'customer_id' => $customer->id,
            'greenhouse_id' => null,
            'description' => null,
            'changes' => json_encode($rawChanges, JSON_UNESCAPED_UNICODE),
            'ip_address' => null,
        ]);

        Artisan::call('audit:report-sensitive');
        $defaultOutput = Artisan::output();

        foreach (['Old Secret Name', 'New Secret Name', 'OldSecretPass', 'NewSecretPass', 'Old internal note', 'New internal note'] as $secret) {
            $this->assertStringNotContainsString($secret, $defaultOutput);
        }

        $this->assertStringContainsString('full_name', $defaultOutput);
        $this->assertStringContainsString('password', $defaultOutput);
        $this->assertStringContainsString('pii', $defaultOutput);
        $this->assertStringContainsString('credential', $defaultOutput);
        $this->assertStringNotContainsString('notes', $defaultOutput);

        Artisan::call('audit:report-sensitive', ['--include-internal' => true]);
        $internalOutput = Artisan::output();

        foreach (['Old Secret Name', 'New Secret Name', 'OldSecretPass', 'NewSecretPass', 'Old internal note', 'New internal note'] as $secret) {
            $this->assertStringNotContainsString($secret, $internalOutput);
        }

        $this->assertStringContainsString('notes', $internalOutput);
        $this->assertStringContainsString('internal', $internalOutput);

        $this->assertSame(1, DB::table('activities')->where('id', $activityId)->count());
        $this->assertSame($rawChanges, json_decode(DB::table('activities')->where('id', $activityId)->value('changes'), true));

        $activity = Activity::findOrFail($activityId);
        $redacted = $activity->redactedChanges();

        $this->assertSame(['redacted' => true, 'classification' => 'pii'], $redacted['full_name']['new']);
        $this->assertArrayNotHasKey('password', $redacted);
        $this->assertStringNotContainsString('New Secret Name', json_encode($redacted, JSON_UNESCAPED_UNICODE));
        $this->assertSame($rawChanges, $activity->changes);
    }

    public function test_redacted_records_are_not_reported(): void
    {
        $customer = Customer::create([
            'full_name' => 'Audit customer',
            'mobile' => '09120000001',
        ]);

        $marker = [
            'redacted' => true,
            'classification' => 'pii',
        ];

        DB::table('activities')->insert([
            'event' => 'updated',
            'subject_type' => 'customer',
            'subject_id' => $customer->id,
            'customer_id' => $customer->id,
            'changes' => json_encode([
                'full_name' => [
                    'old' => $marker,
                    'new' => $marker,
                ],
                'notes' => [
                    'redacted' => true,
                    'classification' => 'internal',
                ],
                'status' => [
                    'old' => 'active',
                    'new' => 'inactive',
                ],
                '__unknown' => [
                    'redacted' => true,
                    'classification' => 'internal',
                ],
            ]),
        ]);

        $this->assertSame(
            0,
            Artisan::call('audit:report-sensitive', [
                '--include-internal' => true,
            ]),
        );

        $output = Artisan::output();

        $this->assertStringContainsString(
            'Affected activity records: 0',
            $output,
        );

        $this->assertStringNotContainsString('full_name', $output);
        $this->assertStringNotContainsString('__unknown_field', $output);
    }

    public function test_mixed_payloads_and_unknown_keys_are_reported_safely(): void
    {
        $customer = Customer::create([
            'full_name' => 'Audit customer',
            'mobile' => '09120000002',
        ]);

        $marker = [
            'redacted' => true,
            'classification' => 'pii',
        ];

        $raw = [
            'full_name' => [
                'old' => $marker,
                'new' => 'PRIVATE-HISTORICAL-NAME',
            ],
            'PRIVATE-UNKNOWN-KEY' => 'PRIVATE-UNKNOWN-VALUE',
            'notes' => [
                'redacted' => true,
                'classification' => 'internal',
                'PRIVATE-EXTRA-KEY' => 'PRIVATE-EXTRA-VALUE',
            ],
        ];

        $id = DB::table('activities')->insertGetId([
            'event' => 'updated',
            'subject_type' => 'customer',
            'subject_id' => $customer->id,
            'customer_id' => $customer->id,
            'changes' => json_encode($raw),
        ]);

        DB::table('activities')->insert([
            'event' => 'updated',
            'subject_type' => 'PRIVATE-UNKNOWN-SUBJECT',
            'subject_id' => 1,
            'changes' => json_encode([
                'password' => 'PRIVATE-SECRET',
                'PRIVATE-NESTED-KEY' => 'PRIVATE-NESTED-VALUE',
            ]),
        ]);

        $this->assertSame(
            0,
            Artisan::call('audit:report-sensitive', [
                '--include-internal' => true,
            ]),
        );

        $output = Artisan::output();

        foreach ([
            'full_name',
            'password',
            '__unknown_field',
            '__unknown_subject',
            'Affected activity records: 2',
        ] as $expected) {
            $this->assertStringContainsString($expected, $output);
        }

        $this->assertStringNotContainsString('PRIVATE-', $output);

        $this->assertSame(
            $raw,
            json_decode(
                DB::table('activities')
                    ->where('id', $id)
                    ->value('changes'),
                true,
            ),
        );
    }

    public function test_nested_redacted_equipment_is_not_counted_as_raw_data(): void
    {
        DB::table('activities')->insert([
            'event' => 'equipment_changed',
            'subject_type' => 'zone',
            'subject_id' => 1,
            'changes' => json_encode([
                'equipment' => [
                    'old' => [],
                    'new' => [
                        [
                            'equipment_id' => 1,
                            'quantity' => 2,
                            'notes' => [
                                'redacted' => true,
                                'classification' => 'internal',
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->assertSame(
            0,
            Artisan::call('audit:report-sensitive', [
                '--include-internal' => true,
            ]),
        );

        $this->assertStringContainsString(
            'Affected activity records: 0',
            Artisan::output(),
        );
    }

    public function test_invalid_historical_safe_values_are_reported_without_exposure(): void
    {
        $customer = Customer::create([
            'full_name' => 'Historical customer',
            'mobile' => '09120000009',
        ]);

        DB::table('activities')->insert([
            'event' => 'updated',
            'subject_type' => 'customer',
            'subject_id' => $customer->id,
            'customer_id' => $customer->id,
            'changes' => json_encode([
                'status' => [
                    'old' => 'active',
                    'new' => 'PRIVATE-INVALID-STATUS',
                ],
            ]),
        ]);

        DB::table('activities')->insert([
            'event' => 'updated',
            'subject_type' => 'customer',
            'subject_id' => $customer->id,
            'customer_id' => $customer->id,
            'changes' => json_encode([
                'status' => [
                    'old' => 'active',
                    'new' => 'inactive',
                ],
            ]),
        ]);

        DB::table('activities')->insert([
            'event' => 'updated',
            'subject_type' => 'customer',
            'subject_id' => $customer->id,
            'customer_id' => $customer->id,
            'changes' => json_encode([
                'status' => [
                    'old' => 'active',
                    'new' => [
                        'redacted' => true,
                        'classification' => 'internal',
                    ],
                ],
            ]),
        ]);

        $this->assertSame(
            0,
            Artisan::call('audit:report-sensitive', [
                '--include-internal' => true,
            ]),
        );

        $output = Artisan::output();

        $this->assertStringContainsString(
            '__invalid_safe_value',
            $output,
        );

        $this->assertStringContainsString(
            'Affected activity records: 1',
            $output,
        );

        $this->assertStringNotContainsString(
            'PRIVATE-INVALID-STATUS',
            $output,
        );
    }
}
