<?php

namespace Tests\Feature\Data;

use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\Zone;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZoneCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_zone_codes_are_generated_per_greenhouse_and_not_reused_after_deletion(): void
    {
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $firstGreenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه اول']);
        $secondGreenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه دوم']);

        $zone = Zone::create(['greenhouse_id' => $firstGreenhouse->id, 'name' => 'سالن اول', 'code' => 'Z99']);
        $this->assertSame('Z01', $zone->code);
        $zone->delete();
        $this->assertSame('Z02', Zone::create(['greenhouse_id' => $firstGreenhouse->id, 'name' => 'سالن دوم'])->code);
        $this->assertSame('Z01', Zone::create(['greenhouse_id' => $secondGreenhouse->id, 'name' => 'سالن اول'])->code);
    }
}
