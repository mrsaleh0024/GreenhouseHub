<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Livewire\Greenhouse\RecordRemoval;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class GreenhouseRemovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_archive_and_manager_restore_preserve_the_record(): void
    {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مالک', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $record = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $otherOwner = User::factory()->role(Role::GreenhouseOwner)->create();
        Livewire::actingAs($otherOwner)->test(RecordRemoval::class, ['greenhouseId' => $record->id])->call('confirmFirst')->assertForbidden();
        $component = Livewire::actingAs($owner)->test(RecordRemoval::class, ['greenhouseId' => $record->id]);
        $component->call('remove')->assertStatus(422);
        $component = Livewire::actingAs($owner)->test(RecordRemoval::class, ['greenhouseId' => $record->id])->call('confirmFirst');
        $this->travel(4)->seconds();
        $component->call('remove')->assertRedirect(route('dashboard'));
        $this->assertStringContainsString('با موفقیت حذف شد', session('greenhouse_notice'));
        $this->assertSoftDeleted($record);
        $this->assertFalse(Greenhouse::visibleTo($owner)->whereKey($record->id)->exists());
        $this->actingAs($owner)->post(route('greenhouses.restore', $record->id))->assertForbidden();
        $manager = User::factory()->role(Role::Admin)->create();
        $this->actingAs($manager)->post(route('greenhouses.restore', $record->id))->assertRedirect(route('dashboard'));
        $this->assertNotSoftDeleted($record);
    }
}
