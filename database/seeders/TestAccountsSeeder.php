<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestAccountsSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->command?->warn('Test accounts can only be created in local or testing environments.');

            return;
        }

        $this->call(RoleSeeder::class);

        foreach ([
            ['09000000001', 'مدیر ارشد آزمایشی', Role::SuperAdmin],
            ['09000000002', 'مدیر آزمایشی', Role::Admin],
            ['09000000003', 'کارشناس آزمایشی', Role::Expert],
            ['09000000004', 'گلخانه‌دار آزمایشی', Role::GreenhouseOwner],
        ] as [$mobile, $name, $role]) {
            if (User::withTrashed()->where('mobile', $mobile)->exists()) {
                $this->command?->warn("The test number {$mobile} already exists; it was not changed.");

                continue;
            }

            $user = new User(['name' => $name, 'mobile' => $mobile, 'password' => '123456789']);
            $user->forceFill(['status' => UserStatus::Active, 'mobile_verified_at' => now()])->save();
            $user->assignRole($role->value);
        }
    }
}
