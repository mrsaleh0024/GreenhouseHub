<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Services\SubstrateProfile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SubstrateProfileTest extends TestCase
{
    public function test_water_and_soil_have_distinct_fields_and_unknown_is_explicit(): void
    {
        $water = new CatalogItem(['slug' => 'water']);
        $soil = new CatalogItem(['slug' => 'field_soil']);
        $result = SubstrateProfile::validate($water, ['system' => 'nft', 'recirculation' => 'closed', 'aeration' => 'unknown']);
        $this->assertSame('water', $result['profile']);
        $this->assertSame('unknown', $result['fields']['aeration']);
        $this->assertArrayHasKey('texture', SubstrateProfile::forItem($soil)['fields']);
        $this->assertArrayNotHasKey('texture', SubstrateProfile::forItem($water)['fields']);
    }

    public function test_measurements_require_context_and_irrelevant_fields_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        SubstrateProfile::validate(new CatalogItem(['slug' => 'water']), ['system' => 'nft', 'recirculation' => 'closed', 'aeration' => 'pump', 'ph' => 6.2, 'texture' => 'clay']);
    }
}
