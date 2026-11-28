<?php

namespace Tests\Feature\Data;

use App\Enums\Role;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\User;
use App\Services\ActivityLogger;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActorTrackingTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_actor_is_recorded_on_creation_and_update(): void
    {
        $creator = User::factory()->create();
        $editor = User::factory()->create();

        $this->actingAs($creator);
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789', 'created_by' => $editor->id]);
        $this->assertSame($creator->id, $customer->created_by);
        $this->assertSame($creator->id, $customer->updated_by);

        $this->actingAs($editor);
        $customer->update(['full_name' => 'نام تازه', 'updated_by' => $creator->id]);
        $this->assertSame($creator->id, $customer->fresh()->created_by);
        $this->assertSame($editor->id, $customer->fresh()->updated_by);
    }

    public function test_activity_records_the_authenticated_actor_and_an_explicit_actor_wins(): void
    {
        $this->seed(RoleSeeder::class);
        $actor = User::factory()->role(Role::Admin)->create();
        $explicit = User::factory()->role(Role::GreenhouseOwner)->create();

        $this->actingAs($actor);
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);

        $created = Activity::where('subject_type', 'customer')
            ->where('subject_id', $customer->id)
            ->where('event', 'created')
            ->firstOrFail();

        $this->assertSame($actor->id, $created->actor_id);
        $this->assertSame(Role::Admin->value, $created->actor_role);

        $manual = app(ActivityLogger::class)->log($customer, 'updated', actor: $explicit);

        $this->assertSame($explicit->id, $manual->actor_id);
        $this->assertSame(Role::GreenhouseOwner->value, $manual->actor_role);
    }
}
