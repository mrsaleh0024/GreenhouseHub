<?php

namespace Tests\Feature\Data;

use App\Enums\CatalogType;
use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\User;
use App\Services\CatalogService;
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
}
