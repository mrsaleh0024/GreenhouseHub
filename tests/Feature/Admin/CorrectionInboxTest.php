<?php

namespace Tests\Feature\Admin;

use App\Enums\Role;
use App\Livewire\Admin\CorrectionInbox;
use App\Livewire\Greenhouse\Cultivation;
use App\Models\CatalogItem;
use App\Models\CultivationCycle;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Services\CorrectionRequestService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CorrectionInboxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_correct_closed_harvest_and_record_a_response_for_owner(): void
    {
        $this->withoutVite();
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $this->travelTo(now()->setDate(2026, 11, 26));
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $admin = User::factory()->role(Role::Admin)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $crop = CatalogItem::where('type', 'crop')->firstOrFail();
        $cycle = CultivationCycle::create(['greenhouse_id' => $greenhouse->id, 'crop_id' => $crop->id, 'planting_date' => '2026-03-21', 'harvest_ended_on' => '2026-11-20', 'status' => 'completed']);
        $request = app(CorrectionRequestService::class)->request($owner, $greenhouse, 'تاریخ پایان نیاز به اصلاح دارد', $cycle);

        Livewire::actingAs($admin)->test(Cultivation::class, ['greenhouse' => $greenhouse])
            ->call('startEdit', $cycle->id)->set('end_date', '۱۴۰۵/۰۹/۰۱')->call('save')->assertHasNoErrors();
        $this->assertSame('2026-11-22', $cycle->fresh()->harvest_ended_on->format('Y-m-d'));
        Livewire::actingAs($admin)->test(CorrectionInbox::class)
            ->call('startResolve', $request->id)->set('resolution', 'تاریخ اصلاح شد')->call('resolve')->assertHasNoErrors();
        $this->assertDatabaseHas('correction_requests', ['id' => $request->id, 'status' => 'resolved', 'resolution' => 'تاریخ اصلاح شد']);
        Livewire::actingAs($owner)->test(Cultivation::class, ['greenhouse' => $greenhouse])->assertSee('تاریخ اصلاح شد');
    }

    public function test_non_managers_cannot_open_inbox(): void
    {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        foreach ([Role::GreenhouseOwner, Role::Expert] as $role) {
            $user = User::factory()->role($role)->create();
            $this->actingAs($user)->get(route('admin.corrections'))->assertForbidden();
        }
    }
}
