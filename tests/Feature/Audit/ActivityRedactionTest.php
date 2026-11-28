<?php

namespace Tests\Feature\Audit;

use App\Enums\Role;
use App\Models\Activity;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\ZoneService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityRedactionTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_pii_and_internal_notes_are_not_written_to_activity_changes(): void
    {
        $customer = Customer::create([
            'full_name' => 'Ali Rezaei',
            'mobile' => '09123456789',
            'national_id' => '1234567890',
            'email' => 'ali@example.com',
            'address' => 'Secret Street 1',
            'notes' => 'Internal note',
            'status' => 'active',
        ]);

        $customer->update([
            'full_name' => 'New Person',
            'mobile' => '09120000000',
            'national_id' => '0987654321',
            'email' => 'new@example.com',
            'address' => 'New Secret Street',
            'notes' => 'New internal note',
            'status' => 'inactive',
        ]);

        $activity = Activity::where('subject_type', 'customer')
            ->where('subject_id', $customer->id)
            ->latest('id')
            ->firstOrFail();

        $encoded = json_encode($activity->changes, JSON_UNESCAPED_UNICODE);

        foreach (['New Person', '09120000000', '0987654321', 'new@example.com', 'New Secret Street', 'New internal note'] as $secret) {
            $this->assertStringNotContainsString($secret, $encoded);
        }

        foreach (['full_name', 'mobile', 'national_id', 'email', 'address'] as $field) {
            $this->assertSame(['redacted' => true, 'classification' => 'pii'], $activity->changes[$field]['old'], $field);
            $this->assertSame(['redacted' => true, 'classification' => 'pii'], $activity->changes[$field]['new'], $field);
        }

        $this->assertSame(['redacted' => true, 'classification' => 'internal'], $activity->changes['notes']['old']);
        $this->assertSame(['redacted' => true, 'classification' => 'internal'], $activity->changes['notes']['new']);
        $this->assertSame('active', $activity->changes['status']['old']);
        $this->assertSame('inactive', $activity->changes['status']['new']);
    }

    public function test_credentials_are_never_written_to_activity_changes(): void
    {
        $customer = Customer::create([
            'full_name' => 'Ali Rezaei',
            'mobile' => '09123456789',
        ]);

        $activity = app(ActivityLogger::class)->log($customer, 'updated', [
            'password' => ['old' => 'OldSecret123', 'new' => 'NewSecret456'],
            'remember_token' => ['old' => 'token-one', 'new' => 'token-two'],
            'options' => ['password' => 'nested-secret', 'irrigation_type' => ['drip']],
            'status' => ['old' => 'active', 'new' => 'inactive'],
        ]);

        $encoded = json_encode($activity->changes, JSON_UNESCAPED_UNICODE);

        foreach (['OldSecret123', 'NewSecret456', 'token-one', 'token-two', 'nested-secret'] as $secret) {
            $this->assertStringNotContainsString($secret, $encoded);
        }

        $this->assertArrayNotHasKey('password', $activity->changes);
        $this->assertArrayNotHasKey('remember_token', $activity->changes);
        $this->assertArrayNotHasKey('options', $activity->changes);
        $this->assertSame(
            ['redacted' => true, 'classification' => 'internal'],
            $activity->changes['__unknown'],
        );
        $this->assertSame('active', $activity->changes['status']['old']);
        $this->assertSame('inactive', $activity->changes['status']['new']);
    }

    public function test_nested_internal_equipment_notes_are_redacted_but_safe_fields_are_kept(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $equipment = CatalogItem::where('type', 'equipment')->firstOrFail();
        $this->actingAs($owner);

        $zone = app(ZoneService::class)->save($greenhouse, $owner, ['name' => 'سالن یک']);
        app(ZoneService::class)->syncEquipment($zone, $owner, [
            ['equipment_id' => $equipment->id, 'quantity' => 3, 'notes' => 'Sensitive equipment note'],
        ]);

        $activity = Activity::where('subject_type', 'zone')
            ->where('subject_id', $zone->id)
            ->where('event', 'equipment_changed')
            ->latest('id')
            ->firstOrFail();

        $row = $activity->changes['equipment']['new'][0];

        $this->assertSame($equipment->id, $row['equipment_id']);
        $this->assertSame(3, $row['quantity']);
        $this->assertSame(['redacted' => true, 'classification' => 'internal'], $row['notes']);
        $this->assertStringNotContainsString('Sensitive equipment note', json_encode($activity->changes, JSON_UNESCAPED_UNICODE));
    }

    public function test_activity_serialization_hides_raw_changes_and_redacted_changes_are_safe(): void
    {
        $customer = Customer::create(['full_name' => 'Ali Rezaei', 'mobile' => '09123456789']);
        $activity = app(ActivityLogger::class)->log($customer, 'updated', [
            'full_name' => ['old' => 'Ali Rezaei', 'new' => 'New Person'],
            'status' => ['old' => 'active', 'new' => 'inactive'],
        ]);

        $serialized = $activity->toArray();

        $this->assertArrayNotHasKey('changes', $serialized);
        $this->assertArrayNotHasKey('ip_address', $serialized);
        $this->assertSame(['redacted' => true, 'classification' => 'pii'], $activity->redactedChanges()['full_name']['new']);
        $this->assertSame('inactive', $activity->redactedChanges()['status']['new']);
    }
}
