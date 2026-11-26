<?php

namespace Tests\Feature\Data;

use App\Enums\Role;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Services\GreenhouseRecordService;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GreenhouseRecordServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_save_non_key_fields_but_cannot_change_ownership(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'نام اول']);
        $service = app(GreenhouseRecordService::class);

        $service->saveBasics($greenhouse, $owner, ['name' => 'نام دوم', 'area_m2' => '200.50']);
        $this->assertDatabaseHas('greenhouses', ['id' => $greenhouse->id, 'name' => 'نام دوم', 'customer_id' => $customer->id]);

        try {
            $service->saveBasics($greenhouse, $owner, ['name' => 'نام سوم', 'customer_id' => 999]);
            $this->fail('A key field was accepted.');
        } catch (ValidationException) {
            $this->assertDatabaseHas('greenhouses', ['id' => $greenhouse->id, 'name' => 'نام دوم', 'customer_id' => $customer->id]);
        }
    }

    public function test_coordinate_pair_is_validated_before_saving(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->role(Role::Admin)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);

        try {
            app(GreenhouseRecordService::class)->saveBasics($greenhouse, $admin, ['name' => 'گلخانه', 'latitude' => 35]);
            $this->fail('An incomplete coordinate pair was accepted.');
        } catch (ValidationException) {
            $this->assertNull($greenhouse->fresh()->latitude);
        }
    }

    public function test_expert_cannot_save_the_record(): void
    {
        $this->seed(RoleSeeder::class);
        $expert = User::factory()->role(Role::Expert)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);

        $this->expectException(AuthorizationException::class);
        app(GreenhouseRecordService::class)->saveBasics($greenhouse, $expert, ['name' => 'ویرایش']);
    }
}
