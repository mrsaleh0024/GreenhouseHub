<?php

namespace App\Providers;

use App\Support\AuditActorContext;
use App\Enums\Role;
use App\Models\Activity;
use App\Models\CatalogItem;
use App\Models\CultivationCycle;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Models\Zone;
use App\Policies\ActivityPolicy;
use App\Policies\CatalogItemPolicy;
use App\Policies\CultivationCyclePolicy;
use App\Policies\CustomerPolicy;
use App\Policies\GreenhousePolicy;
use App\Policies\ZonePolicy;
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
        $this->app->scoped(AuditActorContext::class);

        // To add sms.ir later: create SmsIrGateway implementing SmsGateway
        // and add a 'smsir' => new SmsIrGateway(...) arm below.
        $this->app->bind(SmsGateway::class, fn () => match (config('sms.driver')) {
            'log' => new LogSmsGateway,
            default => throw new InvalidArgumentException('Unsupported SMS driver ['.config('sms.driver').'].'),
        });
    }

    public function boot(): void
    {
        // Audit records are read-only, including for super administrators.
        // All other domain permissions retain the existing behaviour.
        Gate::before(function (User $user, string $ability, array $arguments = []): ?bool {
            $target = $arguments[0] ?? null;

            if (
                ($target === Activity::class || $target instanceof Activity)
                && ! in_array($ability, ['viewAny', 'view'], true)
            ) {
                return false;
            }

            return $user->hasRole(Role::SuperAdmin->value) ? true : null;
        });
        Relation::morphMap(['customer' => Customer::class, 'greenhouse' => Greenhouse::class, 'zone' => Zone::class, 'cultivation_cycle' => CultivationCycle::class, 'catalog_item' => CatalogItem::class]);
        Gate::policy(Greenhouse::class, GreenhousePolicy::class);
        Gate::policy(Zone::class, ZonePolicy::class);
        Gate::policy(CultivationCycle::class, CultivationCyclePolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(CatalogItem::class, CatalogItemPolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
    }
}
