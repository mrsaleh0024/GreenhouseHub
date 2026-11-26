<?php

namespace Tests\Feature\Data;

use App\Enums\CatalogType;
use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Services\CatalogService;
use App\Services\GreenhouseRecordService;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CatalogPickerTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_and_find_normalized_value_and_other_owner_does_not_see_it(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $other = User::factory()->role(Role::GreenhouseOwner)->create();
        $admin = User::factory()->role(Role::Admin)->create();
        $catalog = app(CatalogService::class);

        $item = $catalog->findOrCreateCustom(CatalogType::Crop, 'گياه ويژه', $owner);
        $this->assertSame('pending', $item->status);
        $this->assertSame($item->id, $catalog->findOrCreateCustom(CatalogType::Crop, 'گیاه ویژه', $owner)->id);
        $this->assertTrue($catalog->forPicker(CatalogType::Crop, $owner)->contains('id', $item->id));
        $this->assertFalse($catalog->forPicker(CatalogType::Crop, $other)->contains('id', $item->id));
        $this->assertTrue($catalog->forPicker(CatalogType::Crop, $admin)->contains('id', $item->id));
    }

    public function test_custom_city_requires_its_province_and_is_auto_approved(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $province = CatalogItem::where('type', 'province')->where('slug', 'tehran')->firstOrFail();
        $city = app(CatalogService::class)->findOrCreateCustom(CatalogType::City, 'ورامین', $owner, $province->id);
        $this->assertSame('approved', $city->status);
        $this->assertSame($province->id, $city->parent_id);

        $this->expectException(ValidationException::class);
        app(CatalogService::class)->findOrCreateCustom(CatalogType::City, 'شهر دیگر', $owner, $city->id);
    }

    public function test_a_second_owner_can_reuse_a_pending_value_only_after_entering_the_same_label(): void
    {
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $first = User::factory()->role(Role::GreenhouseOwner)->create();
        $second = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($second);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $catalog = app(CatalogService::class);
        $item = $catalog->findOrCreateCustom(CatalogType::StructureType, 'سازه ویژه', $first);

        $this->assertFalse($catalog->forPicker(CatalogType::StructureType, $second)->contains('id', $item->id));
        try {
            app(GreenhouseRecordService::class)->saveStructure($greenhouse, $second, ['structure_type_id' => $item->id]);
            $this->fail('Another owner\'s pending item was accepted without a matching entry.');
        } catch (ValidationException) {
            $this->assertNull($greenhouse->fresh()->structure_type_id);
        }
        $this->assertSame($item->id, $catalog->findOrCreateCustom(CatalogType::StructureType, 'سازه ویژه', $second)->id);
        $this->assertTrue($catalog->forPicker(CatalogType::StructureType, $second)->contains('id', $item->id));
        app(GreenhouseRecordService::class)->saveStructure($greenhouse, $second, ['structure_type_id' => $item->id]);
        $this->assertDatabaseHas('greenhouses', ['id' => $greenhouse->id, 'structure_type_id' => $item->id]);
    }
}
