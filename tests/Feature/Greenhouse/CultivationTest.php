<?php

namespace Tests\Feature\Greenhouse;

use App\Enums\Role;
use App\Livewire\Greenhouse\Cultivation;
use App\Models\CatalogItem;
use App\Models\CultivationCycle;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CultivationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_finish_and_request_a_correction(): void
    {
        $this->withoutVite();
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $this->travelTo(now()->setDate(2026, 11, 26));
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $crop = CatalogItem::where('type', 'crop')->firstOrFail();

        $component = Livewire::actingAs($owner)->test(Cultivation::class, ['greenhouse' => $greenhouse])
            ->call('startNew')->set('crop_id', (string) $crop->id)->set('planting_date', '۱۴۰۵/۰۱/۰۱')
            ->call('save')->assertHasNoErrors()->assertSee('اطلاعات کشت ذخیره شد');
        $cycle = CultivationCycle::where('greenhouse_id', $greenhouse->id)->firstOrFail();
        $this->assertSame('2026-03-21', $cycle->planting_date->format('Y-m-d'));

        $component->call('startFinish', $cycle->id)->set('end_date', '۱۴۰۵/۰۸/۲۹')
            ->call('finish')->assertHasNoErrors()->assertSee('پایان برداشت ثبت شد');
        $this->assertSame('2026-11-20', $cycle->fresh()->harvest_ended_on->format('Y-m-d'));
        $component->call('startCorrection', $cycle->id)->set('correction_details', 'تاریخ برداشت نیاز به بررسی دارد')
            ->call('sendCorrection')->assertHasNoErrors()->assertSee('درخواست اصلاح برای بررسی مدیر ثبت شد');
        $this->assertDatabaseHas('correction_requests', ['cultivation_cycle_id' => $cycle->id, 'requested_by' => $owner->id, 'status' => 'pending']);
    }

    public function test_other_owner_cannot_open_the_cultivation_page(): void
    {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $other = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);

        $this->actingAs($other)->get(route('greenhouses.cultivation', $greenhouse))->assertNotFound();
    }

    public function test_owner_can_add_a_custom_crop_and_variety_and_use_them_immediately(): void
    {
        $this->withoutVite();
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);

        Livewire::actingAs($owner)->test(Cultivation::class, ['greenhouse' => $greenhouse])->call('startNew')
            ->set('customLabels.crop', 'محصول ویژه')->call('addCustom', 'crop')->assertHasNoErrors()
            ->set('customLabels.variety', 'رقم ویژه')->call('addCustom', 'variety')->assertHasNoErrors()
            ->set('planting_date', '۱۴۰۵/۰۱/۰۱')->call('save')->assertHasNoErrors();

        $crop = CatalogItem::where('type', 'crop')->where('label_fa', 'محصول ویژه')->firstOrFail();
        $variety = CatalogItem::where('type', 'variety')->where('label_fa', 'رقم ویژه')->firstOrFail();
        $this->assertSame($crop->id, $variety->parent_id);
        $this->assertSame('pending', $crop->status);
        $this->assertDatabaseHas('cultivation_cycles', ['greenhouse_id' => $greenhouse->id, 'crop_id' => $crop->id, 'variety_id' => $variety->id]);
    }
}
