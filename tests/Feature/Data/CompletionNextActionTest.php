<?php

namespace Tests\Feature\Data;

use App\Models\Greenhouse;
use App\Services\GreenhouseCompleteness;
use Tests\TestCase;

class CompletionNextActionTest extends TestCase
{
    public function test_the_next_action_points_to_the_first_missing_section(): void
    {
        $greenhouse = new Greenhouse;
        $greenhouse->forceFill(['structure_type_id' => 1, 'cultivation_type_id' => 2, 'zones_count' => 0, 'cycles_count' => 0]);
        $types = ['irrigation_type', 'heating_system', 'cooling_system', 'water_source', 'ventilation_system'];
        $service = app(GreenhouseCompleteness::class);

        $this->assertSame('greenhouses.zones', $service->nextAction($greenhouse, $types)['route']);
        $greenhouse->zones_count = 1;
        $this->assertSame('greenhouses.cultivation', $service->nextAction($greenhouse, $types)['route']);
        $greenhouse->cycles_count = 1;
        $this->assertSame(1, $service->nextAction($greenhouse, $types)['step']);
    }
}
