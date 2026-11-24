<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['mobile' => '09120000000'],
            [
                'name' => 'مدیر ارشد سیستم',
                'password' => Hash::make('password'),
                'status' => 'active',
                'mobile_verified_at' => now(),
            ]
        );

        $admin->assignRole('super_admin');

        $expert = User::firstOrCreate(
            ['mobile' => '09121111111'],
            [
                'name' => 'کارشناس تست',
                'password' => Hash::make('password'),
                'status' => 'active',
                'mobile_verified_at' => now(),
            ]
        );

        $expert->assignRole('expert');

        $owner = User::firstOrCreate(
            ['mobile' => '09123333333'],
            [
                'name' => 'مالک گلخانه تست',
                'password' => Hash::make('password'),
                'status' => 'pending',
                'mobile_verified_at' => null,
            ]
        );

        $owner->assignRole('greenhouse_owner');
    }
}