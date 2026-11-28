<?php

namespace Tests\Feature\Audit;

use App\Models\Activity;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ActivityParentProtectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_deletion_cannot_modify_audit_references(): void
    {
        $actor = User::factory()->create();

        $customer = Customer::create([
            'full_name' => 'Audit customer',
            'mobile' => '09120000001',
        ]);

        $otherCustomer = Customer::create([
            'full_name' => 'Greenhouse customer',
            'mobile' => '09120000002',
        ]);

        $greenhouse = Greenhouse::create([
            'customer_id' => $otherCustomer->id,
            'name' => 'Audit greenhouse',
        ]);

        $activity = Activity::create([
            'occurred_at' => now(),
            'actor_id' => $actor->id,
            'event' => 'created',
            'subject_type' => 'greenhouse',
            'subject_id' => $greenhouse->id,
            'customer_id' => $customer->id,
            'greenhouse_id' => $greenhouse->id,
        ]);

        foreach ([
            'users' => $actor->id,
            'customers' => $customer->id,
            'greenhouses' => $greenhouse->id,
        ] as $table => $id) {
            try {
                DB::table($table)->where('id', $id)->delete();

                $this->fail(
                    "Physical deletion was not blocked: {$table}"
                );
            } catch (QueryException $exception) {
                $message = strtolower($exception->getMessage());

                $this->assertTrue(
                    str_contains($message, 'immutable')
                    || str_contains($message, 'foreign key'),
                );
            }

            $this->assertDatabaseHas($table, ['id' => $id]);
        }

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'actor_id' => $actor->id,
            'customer_id' => $customer->id,
            'greenhouse_id' => $greenhouse->id,
        ]);
    }

    public function test_soft_delete_and_restore_remain_available(): void
    {
        $customer = Customer::create([
            'full_name' => 'Audit customer',
            'mobile' => '09120000003',
        ]);

        $activity = Activity::where('subject_type', 'customer')
            ->where('subject_id', $customer->id)
            ->where('event', 'created')
            ->sole();

        $customer->delete();

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
        ]);

        $customer->restore();

        $this->assertNotSoftDeleted('customers', [
            'id' => $customer->id,
        ]);

        $this->assertDatabaseHas('activities', [
            'id' => $activity->id,
            'customer_id' => $customer->id,
        ]);
    }
}
