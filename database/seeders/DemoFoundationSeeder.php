<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\CultivationCycle;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DemoFoundationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $crop = CatalogItem::where('type', 'crop')->where('slug', 'tomato')->first();
        if (! $crop) {
            $this->command?->warn('Demo greenhouse skipped: seed the catalog first.');

            return;
        }

        foreach (['09123333333' => 'گلخانه‌دار تست', '09000000004' => 'گلخانه‌دار آزمایشی'] as $mobile => $name) {
            $user = User::where('mobile', $mobile)->where('name', $name)->first();
            if (! $user || ! $user->isActive() || ! $user->isMobileVerified() || ! $user->hasRole(Role::GreenhouseOwner->value)) {
                continue;
            }
            if (Customer::withTrashed()->where('mobile', $mobile)->exists() || $user->customers()->exists()) {
                continue;
            }

            DB::transaction(function () use ($user, $crop) {
                $customer = Customer::create(['full_name' => $user->name, 'mobile' => $user->mobile, 'registered_via' => 'self_registration']);
                $customer->users()->attach($user->id, ['is_primary' => true]);
                $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه نمونه']);
                $first = Zone::create(['greenhouse_id' => $greenhouse->id, 'name' => 'سالن تولید']);
                Zone::create(['greenhouse_id' => $greenhouse->id, 'name' => 'نشاءخانه']);
                CultivationCycle::create(['greenhouse_id' => $greenhouse->id, 'zone_id' => $first->id, 'crop_id' => $crop->id, 'planting_date' => now()->subDays(30)->toDateString(), 'status' => 'growing']);
            });
        }
    }
}
