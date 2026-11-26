<?php

namespace Tests\Feature\Auth;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Livewire\Auth\Register;
use App\Livewire\Auth\VerifyMobile;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Concerns\InteractsWithAuth;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use InteractsWithAuth;
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpAuth();
    }

    private function fillOwner(Testable $c, string $mobile = '09123456789'): Testable
    {
        return $c->set('role', 'greenhouse_owner')
            ->set('name', 'علی رضایی')
            ->set('mobile', $mobile)
            ->set('password', 'Password1')
            ->set('password_confirmation', 'Password1')
            ->set('greenhouse_name', 'گلخانه سبز')
            ->set('province_id', CatalogItem::where('type', 'province')->where('slug', 'tehran')->value('id'))
            ->set('city', 'ورامین');
    }

    public function test_registration_page_renders(): void
    {
        $this->get(route('register'))->assertOk()->assertSeeLivewire(Register::class);
    }

    public function test_a_greenhouse_owner_can_register(): void
    {
        $this->fillOwner(Livewire::test(Register::class))
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('verify-mobile'));

        $user = User::where('mobile', '09123456789')->firstOrFail();

        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertNull($user->mobile_verified_at);
        $this->assertTrue($user->hasRole(Role::GreenhouseOwner->value));
        $this->assertSame('گلخانه سبز', Customer::where('mobile', $user->mobile)->firstOrFail()->greenhouses->first()->name);
        $this->assertNull($user->expertProfile);
        $this->assertNotNull($this->sms->lastCode('09123456789'));
    }

    public function test_an_expert_can_register(): void
    {
        Livewire::test(Register::class)
            ->set('role', 'expert')
            ->set('name', 'سارا احمدی')
            ->set('mobile', '09121111111')
            ->set('password', 'Password1')
            ->set('password_confirmation', 'Password1')
            ->set('specialty', 'بیماری‌شناسی گیاهی')
            ->set('license_number', '12345')
            ->set('experience_years', '8')
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('verify-mobile'));

        $user = User::where('mobile', '09121111111')->firstOrFail();

        $this->assertTrue($user->hasRole(Role::Expert->value));
        $this->assertSame(8, $user->expertProfile->experience_years);
        $this->assertNull($user->ownerProfile);
    }

    public function test_privileged_roles_cannot_be_self_registered(): void
    {
        foreach (['admin', 'super_admin', 'nonsense'] as $role) {
            $this->fillOwner(Livewire::test(Register::class))
                ->set('role', $role)
                ->call('register')
                ->assertHasErrors('role');
        }

        $this->assertDatabaseCount('users', 0);
    }

    public function test_role_specific_fields_are_required(): void
    {
        $this->fillOwner(Livewire::test(Register::class))
            ->set('greenhouse_name', '')
            ->set('province_id', 999999)
            ->call('register')
            ->assertHasErrors(['greenhouse_name', 'province_id']);

        Livewire::test(Register::class)
            ->set('role', 'expert')
            ->set('name', 'سارا احمدی')
            ->set('mobile', '09121111111')
            ->set('password', 'Password1')
            ->set('password_confirmation', 'Password1')
            ->call('register')
            ->assertHasErrors(['specialty', 'experience_years']);
    }

    public function test_weak_or_mismatched_passwords_are_rejected(): void
    {
        foreach (['short1', 'onlyletters', '12345678'] as $weak) {
            $this->fillOwner(Livewire::test(Register::class))
                ->set('password', $weak)
                ->set('password_confirmation', $weak)
                ->call('register')
                ->assertHasErrors('password');
        }

        $this->fillOwner(Livewire::test(Register::class))
            ->set('password_confirmation', 'Different1')
            ->call('register')
            ->assertHasErrors('password');

        $this->assertDatabaseCount('users', 0);
    }

    public function test_an_invalid_mobile_is_rejected(): void
    {
        $this->fillOwner(Livewire::test(Register::class), '0812345')
            ->call('register')
            ->assertHasErrors('mobile');
    }

    public function test_a_confirmed_mobile_cannot_be_registered_again(): void
    {
        User::factory()->role(Role::Expert)->create(['mobile' => '09123456789']);

        $this->fillOwner(Livewire::test(Register::class))
            ->call('register')
            ->assertHasErrors('mobile');

        $this->assertDatabaseCount('users', 1);
        $this->assertNull($this->sms->lastCode('09123456789'));
    }

    public function test_an_admin_created_pending_account_cannot_be_taken_over_by_registration(): void
    {
        $admin = User::factory()->withoutPassword()->role(Role::Admin)->create(['mobile' => '09123456789']);

        $this->fillOwner(Livewire::test(Register::class))
            ->call('register')
            ->assertHasErrors('mobile');

        $this->assertFalse($admin->fresh()->hasPassword());
        $this->assertTrue($admin->fresh()->hasRole(Role::Admin->value));
    }

    public function test_a_never_confirmed_registration_can_be_reclaimed(): void
    {
        $squatter = User::factory()->unverified()->role(Role::Expert)->create([
            'mobile' => '09123456789',
            'name' => 'Squatter',
        ]);

        $this->fillOwner(Livewire::test(Register::class))
            ->call('register')
            ->assertHasNoErrors()
            ->assertRedirect(route('verify-mobile'));

        $this->assertDatabaseCount('users', 1);

        $user = $squatter->fresh();
        $this->assertSame('علی رضایی', $user->name);
        $this->assertTrue($user->hasRole(Role::GreenhouseOwner->value));
        $this->assertFalse($user->hasRole(Role::Expert->value));
        $this->assertNull($user->expertProfile);
    }

    public function test_the_registration_otp_activates_the_account_and_logs_in(): void
    {
        $this->fillOwner(Livewire::test(Register::class))->call('register');

        Livewire::test(VerifyMobile::class)
            ->set('code', $this->sms->lastCode('09123456789'))
            ->call('verify')
            ->assertHasNoErrors()
            ->assertRedirect(route('dashboard'));

        $user = User::where('mobile', '09123456789')->firstOrFail();

        $this->assertSame(UserStatus::Active, $user->status);
        $this->assertNotNull($user->mobile_verified_at);
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_wrong_otp_does_not_activate_the_account(): void
    {
        $this->fillOwner(Livewire::test(Register::class))->call('register');

        $real = $this->sms->lastCode('09123456789');

        Livewire::test(VerifyMobile::class)
            ->set('code', $real === '000000' ? '111111' : '000000')
            ->call('verify')
            ->assertHasErrors('code');

        $user = User::where('mobile', '09123456789')->firstOrFail();

        $this->assertSame(UserStatus::Pending, $user->status);
        $this->assertGuest();
    }

    public function test_verify_page_requires_a_pending_registration(): void
    {
        $this->get(route('verify-mobile'))->assertRedirect(route('login'));
    }
}
