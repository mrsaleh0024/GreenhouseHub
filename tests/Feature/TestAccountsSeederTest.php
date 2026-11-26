<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use Database\Seeders\TestAccountsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TestAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_test_accounts_have_the_requested_roles_and_password(): void
    {
        $this->seed(TestAccountsSeeder::class);

        foreach ([
            '09000000001' => Role::SuperAdmin,
            '09000000002' => Role::Admin,
            '09000000003' => Role::Expert,
            '09000000004' => Role::GreenhouseOwner,
        ] as $mobile => $role) {
            $user = User::where('mobile', $mobile)->firstOrFail();
            $this->assertTrue($user->hasRole($role->value));
            $this->assertTrue(Hash::check('123456789', $user->password));
            $this->assertTrue($user->isActive());
            $this->assertTrue($user->isMobileVerified());
        }
    }

    public function test_existing_account_is_not_modified(): void
    {
        $user = User::factory()->create(['mobile' => '09000000001', 'password' => 'ExistingPassword1']);
        $this->seed(TestAccountsSeeder::class);
        $this->assertTrue(Hash::check('ExistingPassword1', $user->fresh()->password));
        $this->assertCount(0, $user->fresh()->roles);
    }

    public function test_seeder_does_nothing_outside_local_and_testing(): void
    {
        $original = $this->app->environment();
        $this->app->detectEnvironment(fn () => 'production');
        try {
            app(TestAccountsSeeder::class)->run();
            $this->assertDatabaseCount('users', 0);
        } finally {
            $this->app->detectEnvironment(fn () => $original);
        }
    }
}
