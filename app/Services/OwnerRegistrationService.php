<?php

namespace App\Services;

use App\Support\AuditActorContext;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OwnerRegistrationService
{
    public function register(User $user, array $data): Customer
    {
        return app(AuditActorContext::class)->run($user, fn () => DB::transaction(function () use ($user, $data) {
            $customer = Customer::where('mobile', $user->mobile)->first();
            if (! $customer) {
                $customer = Customer::create(['full_name' => $user->name, 'mobile' => $user->mobile, 'province_id' => $data['province_id'], 'city_id' => $data['city_id'], 'registered_via' => 'self_registration', 'status' => 'active']);
                Greenhouse::create(['customer_id' => $customer->id, 'name' => $data['greenhouse_name'], 'province_id' => $data['province_id'], 'city_id' => $data['city_id']]);
            } elseif ($customer->registered_via === 'self_registration' && ! $customer->users()->exists()) {
                $customer->update(['full_name' => $user->name, 'province_id' => $data['province_id'], 'city_id' => $data['city_id']]);
                $greenhouse = $customer->greenhouses()->oldest('id')->first();
                if ($greenhouse) {
                    $greenhouse->update(['name' => $data['greenhouse_name'], 'province_id' => $data['province_id'], 'city_id' => $data['city_id']]);
                }
            }

            return $customer;
        }));
    }
}
