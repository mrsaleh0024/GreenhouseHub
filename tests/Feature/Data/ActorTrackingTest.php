<?php

namespace Tests\Feature\Data;

use App\Models\Customer;
use App\Models\User;
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
}
