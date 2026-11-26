<?php

namespace Tests\Feature\Data;

use App\Enums\Role;
use App\Models\Activity;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Services\GreenhouseService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GreenhouseOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_saving_one_group_preserves_other_groups_and_no_op_is_not_logged(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $drip = CatalogItem::where('type', 'irrigation_type')->where('slug', 'drip')->firstOrFail();
        $heater = CatalogItem::where('type', 'heating_system')->where('slug', 'unit_heater_gas')->firstOrFail();
        $water = CatalogItem::where('type', 'water_source')->where('slug', 'well')->firstOrFail();
        $service = app(GreenhouseService::class);
        $this->actingAs($owner);

        $service->syncOptions($greenhouse, ['irrigation_type' => [$drip->id], 'heating_system' => [$heater->id]], $owner);
        $service->syncOptions($greenhouse, ['water_source' => [$water->id]], $owner);
        $service->syncOptions($greenhouse, ['water_source' => [$water->id]], $owner);

        $this->assertDatabaseHas('greenhouse_options', ['greenhouse_id' => $greenhouse->id, 'catalog_item_id' => $drip->id]);
        $this->assertDatabaseHas('greenhouse_options', ['greenhouse_id' => $greenhouse->id, 'catalog_item_id' => $heater->id]);
        $this->assertDatabaseHas('greenhouse_options', ['greenhouse_id' => $greenhouse->id, 'catalog_item_id' => $water->id]);
        $this->assertSame(2, Activity::where('greenhouse_id', $greenhouse->id)->where('event', 'options_changed')->count());
    }

    public function test_none_cannot_be_selected_with_another_item_in_the_same_group(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $admin = User::factory()->role(Role::Admin)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $items = CatalogItem::where('type', 'heating_system')->whereIn('slug', ['none', 'unit_heater_gas'])->pluck('id')->all();

        try {
            app(GreenhouseService::class)->syncOptions($greenhouse, ['heating_system' => $items], $admin);
            $this->fail('Mutually exclusive options were accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseMissing('greenhouse_options', ['greenhouse_id' => $greenhouse->id]);
        }
    }
}
