<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedSuperAdmin();

        if (app()->environment(['local', 'testing'])) {
            $this->seedDemoUsers();
        }
    }

    /**
     * The first super admin comes from .env (SUPER_ADMIN_*), never from a
     * hard-coded password. Skipped when the variables are not set.
     */
    private function seedSuperAdmin(): void
    {
        $mobile = env('SUPER_ADMIN_MOBILE');
        $password = env('SUPER_ADMIN_PASSWORD');

        if (! $mobile || ! $password) {
            $this->command?->warn('SUPER_ADMIN_MOBILE / SUPER_ADMIN_PASSWORD not set: no super admin created.');

            return;
        }

        $this->makeUser($mobile, env('SUPER_ADMIN_NAME', 'مدیر ارشد سیستم'), $password, Role::SuperAdmin);
    }

    /** Local development accounts only. Password: Password1 */
    private function seedDemoUsers(): void
    {
        $this->makeUser('09120000001', 'مدیر تست', 'Password1', Role::Admin);
        $this->makeUser('09121111111', 'کارشناس تست', 'Password1', Role::Expert);
        $this->makeUser('09123333333', 'گلخانه‌دار تست', 'Password1', Role::GreenhouseOwner);
    }

    private function makeUser(string $mobile, string $name, string $password, Role $role): void
    {
        $user = User::firstOrNew(['mobile' => $mobile]);
        $user->fill(['name' => $name, 'password' => $password]);
        $user->forceFill([
            'mobile_verified_at' => $user->mobile_verified_at ?? now(),
            'status' => UserStatus::Active,
        ])->save();

        $user->syncRoles([$role->value]);
    }
}
