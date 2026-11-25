<?php

namespace Tests\Concerns;

use App\Enums\Role;
use App\Models\User;
use App\Services\Sms\SmsGateway;
use Database\Seeders\RoleSeeder;
use Tests\Support\FakeSmsGateway;

trait InteractsWithAuth
{
    protected FakeSmsGateway $sms;

    protected function setUpAuth(): void
    {
        // Pages render without a compiled Vite manifest in tests.
        $this->withoutVite();

        $this->seed(RoleSeeder::class);

        $this->sms = new FakeSmsGateway();
        $this->app->instance(SmsGateway::class, $this->sms);
    }

    protected function userWithRole(Role $role, array $attributes = []): User
    {
        return User::factory()->role($role)->create($attributes);
    }
}
