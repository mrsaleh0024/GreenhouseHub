<?php

namespace Database\Factories;

use App\Enums\Role;
use App\Enums\UserStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role as RoleModel;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'mobile' => '09'.fake()->unique()->numerify('#########'),
            'mobile_verified_at' => now(),
            'password' => static::$password ??= Hash::make('Password1'),
            'status' => UserStatus::Active,
            'remember_token' => Str::random(10),
        ];
    }

    public function role(Role $role): static
    {
        return $this->afterCreating(function (User $user) use ($role) {
            $user->assignRole(RoleModel::findOrCreate($role->value, 'web'));
        });
    }

    /** Self-registered account that has not confirmed its mobile yet. */
    public function unverified(): static
    {
        return $this->state(fn () => [
            'mobile_verified_at' => null,
            'status' => UserStatus::Pending,
        ]);
    }

    /** Admin-created account waiting for its owner to activate it. */
    public function withoutPassword(): static
    {
        return $this->state(fn () => [
            'password' => null,
            'mobile_verified_at' => null,
            'status' => UserStatus::Pending,
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => ['status' => UserStatus::Inactive]);
    }
}
