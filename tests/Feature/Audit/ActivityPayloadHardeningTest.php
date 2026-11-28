<?php

namespace Tests\Feature\Audit;

use App\Support\ActivityChangeRedactor;
use App\Support\DataClassification;
use Tests\TestCase;

class ActivityPayloadHardeningTest extends TestCase
{
    public function test_unknown_fields_remain_unsafe_even_with_bad_default_config(): void
    {
        config(['data_classification.default' => 'safe']);

        $this->assertSame(
            'internal',
            DataClassification::classify('customers', 'unknown_field'),
        );

        $this->assertFalse(
            DataClassification::isSafe('customers', 'unknown_field'),
        );

        $this->assertSame(
            [],
            DataClassification::aiExportable('customers', [
                'unknown_field' => 'private-value',
            ]),
        );
    }

    public function test_ai_export_does_not_accept_nested_payloads(): void
    {
        $result = DataClassification::aiExportable('customers', [
            'id' => 1,
            'status' => ['email' => 'private@example.test'],
            'unknown_field' => 'private-value',
        ]);

        $this->assertSame(['id' => 1], $result);
    }

    public function test_invalid_classification_fails_closed(): void
    {
        config([
            'data_classification.tables.customers.status' => 'invalid',
        ]);

        $this->assertSame(
            'internal',
            DataClassification::classify('customers', 'status'),
        );

        $this->assertSame(
            [],
            DataClassification::aiExportable('customers', [
                'status' => 'active',
            ]),
        );
    }

    public function test_unknown_keys_and_nested_pii_are_not_persisted(): void
    {
        $changes = app(ActivityChangeRedactor::class)->redact(
            'customers',
            [
                'status' => [
                    'old' => 'active',
                    'new' => ['mobile' => 'PRIVATE-MOBILE'],
                    'PRIVATE-KEY' => 'PRIVATE-VALUE',
                ],
                'ANOTHER-PRIVATE-KEY' => 'ANOTHER-PRIVATE-VALUE',
                'password' => 'PRIVATE-PASSWORD',
            ],
        );

        $encoded = json_encode($changes);

        foreach ([
            'PRIVATE-MOBILE',
            'PRIVATE-KEY',
            'PRIVATE-VALUE',
            'PRIVATE-PASSWORD',
        ] as $secret) {
            $this->assertStringNotContainsString($secret, $encoded);
        }

        $this->assertSame('active', $changes['status']['old']);
        $this->assertTrue($changes['status']['new']['redacted']);
        $this->assertArrayNotHasKey('password', $changes);
    }

    public function test_greenhouse_options_accept_ids_but_reject_unknown_payloads(): void
    {
        $changes = app(ActivityChangeRedactor::class)->redact(
            'greenhouses',
            [
                'options' => [
                    'old' => ['irrigation_type' => [1]],
                    'new' => [
                        'irrigation_type' => [
                            2,
                            ['email' => 'PRIVATE-EMAIL'],
                        ],
                        'PRIVATE-GROUP' => ['PRIVATE-VALUE'],
                    ],
                ],
            ],
        );

        $this->assertSame(
            [1],
            $changes['options']['old']['irrigation_type'],
        );

        $this->assertSame(
            2,
            $changes['options']['new']['irrigation_type'][0],
        );

        $this->assertTrue(
            $changes['options']['new']['irrigation_type'][1]['redacted'],
        );

        $encoded = json_encode($changes);

        foreach ([
            'PRIVATE-EMAIL',
            'PRIVATE-GROUP',
            'PRIVATE-VALUE',
        ] as $secret) {
            $this->assertStringNotContainsString($secret, $encoded);
        }
    }

    public function test_equipment_payload_keeps_ids_and_quantities_only(): void
    {
        $changes = app(ActivityChangeRedactor::class)->redact(
            'zones',
            [
                'equipment' => [
                    'old' => [],
                    'new' => [
                        [
                            'equipment_id' => 7,
                            'quantity' => 3,
                            'notes' => 'PRIVATE-NOTE',
                            'email' => 'PRIVATE-EMAIL',
                            'password' => 'PRIVATE-PASSWORD',
                        ],
                    ],
                ],
            ],
        );

        $row = $changes['equipment']['new'][0];

        $this->assertSame(7, $row['equipment_id']);
        $this->assertSame(3, $row['quantity']);
        $this->assertTrue($row['notes']['redacted']);
        $this->assertArrayNotHasKey('password', $row);
        $this->assertStringNotContainsString(
            'PRIVATE-',
            json_encode($changes),
        );
    }

    public function test_unknown_subject_cannot_use_synthetic_safe_keys(): void
    {
        $changes = app(ActivityChangeRedactor::class)->redact(
            'unknown_table',
            [
                'id' => 'PRIVATE-ID',
                'options' => ['PRIVATE-VALUE'],
            ],
        );

        $this->assertSame(
            [
                '__unknown' => [
                    'redacted' => true,
                    'classification' => 'internal',
                ],
            ],
            $changes,
        );
    }

    public function test_user_controlled_free_text_is_not_ai_exportable(): void
    {
        $this->assertSame(
            ['id' => 1],
            DataClassification::aiExportable('greenhouses', [
                'id' => 1,
                'name' => 'PRIVATE-NAME',
            ]),
        );

        $this->assertSame(
            [],
            DataClassification::aiExportable('catalog_items', [
                'label_fa' => 'PRIVATE-LABEL',
                'normalized_label' => 'PRIVATE-LABEL',
                'slug' => 'PRIVATE-SLUG',
            ]),
        );
    }

    public function test_merge_payload_preserves_safe_equipment_information(): void
    {
        $changes = app(ActivityChangeRedactor::class)->redact(
            'zones',
            [
                'merge' => [
                    'old' => [
                        ['id' => 1, 'quantity' => 2, 'notes' => 'PRIVATE-ONE'],
                        ['id' => 2, 'quantity' => 3, 'notes' => 'PRIVATE-TWO'],
                    ],
                    'new' => [
                        'id' => 2,
                        'quantity' => 5,
                        'notes' => 'PRIVATE-COMBINED',
                    ],
                ],
            ],
        );

        $this->assertSame(1, $changes['merge']['old'][0]['id']);
        $this->assertSame(2, $changes['merge']['new']['id']);
        $this->assertSame(5, $changes['merge']['new']['quantity']);

        $this->assertStringNotContainsString(
            'PRIVATE-',
            json_encode($changes),
        );
    }
}
