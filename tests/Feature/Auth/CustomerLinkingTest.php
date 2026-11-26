<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\VerifyMobile;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Services\CustomerLinker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class CustomerLinkingTest extends TestCase
{
    use InteractsWithAuth, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuth();
    }

    public function test_staff_created_customer_is_linked_only_after_valid_otp(): void
    {
        $customer = Customer::create(['full_name' => 'اطلاعات ثبت‌شده', 'mobile' => '09123456789', 'registered_via' => 'staff']);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه موجود']);
        $province = CatalogItem::where('type', 'province')->where('slug', 'tehran')->firstOrFail();

        Livewire::test(Register::class)->set('role', 'greenhouse_owner')->set('name', 'نام فرم')
            ->set('mobile', '09123456789')->set('password', 'Password1')->set('password_confirmation', 'Password1')
            ->set('greenhouse_name', 'نام جدید')->set('province_id', (string) $province->id)->set('city', 'تهران')
            ->call('register')->assertHasNoErrors();
        $user = User::where('mobile', '09123456789')->firstOrFail();
        $this->assertFalse($user->customers()->exists());
        $this->assertFalse(Greenhouse::visibleTo($user)->whereKey($greenhouse)->exists());
        $this->assertSame('اطلاعات ثبت‌شده', $customer->fresh()->full_name);
        $this->assertSame(1, $customer->greenhouses()->count());

        Livewire::test(VerifyMobile::class)->set('code', $this->sms->lastCode($user->mobile))->call('verify')->assertHasNoErrors();
        $this->assertTrue($user->customers()->whereKey($customer->id)->exists());
        $this->assertTrue(Greenhouse::visibleTo($user)->whereKey($greenhouse)->exists());
        $this->assertDatabaseHas('activities', ['event' => 'user_linked', 'customer_id' => $customer->id, 'actor_id' => $user->id]);
    }

    public function test_linker_refuses_an_unverified_user_even_if_called_directly(): void
    {
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $user = User::factory()->unverified()->role(Role::GreenhouseOwner)->create(['mobile' => '09123456789']);

        app(CustomerLinker::class)->linkUserToCustomer($user);

        $this->assertFalse($customer->users()->exists());
    }
}
