<?php

namespace App\Providers;

use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\CultivationCycle;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Models\Zone;
use App\Services\Sms\LogSmsGateway;
use App\Services\Sms\SmsGateway;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // To add sms.ir later: create SmsIrGateway implementing SmsGateway
        // and add a 'smsir' => new SmsIrGateway(...) arm below.
        $this->app->bind(SmsGateway::class, fn () => match (config('sms.driver')) {
            'log' => new LogSmsGateway,
            default => throw new InvalidArgumentException('Unsupported SMS driver ['.config('sms.driver').'].'),
        });
    }

    public function boot(): void
    {
        // The super admin passes every gate/policy check.
        Gate::before(fn (User $user) => $user->hasRole(Role::SuperAdmin->value) ? true : null);
        Relation::morphMap(['customer' => Customer::class, 'greenhouse' => Greenhouse::class, 'zone' => Zone::class, 'cultivation_cycle' => CultivationCycle::class, 'catalog_item' => CatalogItem::class]);
    }
}
