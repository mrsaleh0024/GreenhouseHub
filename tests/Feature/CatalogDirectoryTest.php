<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\User;
use App\Services\CatalogDirectory;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CatalogDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_catalog_invalidates_on_change_and_pending_choices_remain_private(): void
    {
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $other = User::factory()->role(Role::GreenhouseOwner)->create();
        Cache::put('catalog.approved.v2', collect(['stale']), 600);
        $approved = CatalogItem::create(['type' => 'substrate', 'slug' => 'sample', 'label_fa' => 'بستر عمومی', 'status' => 'approved', 'source' => 'system']);
        $this->assertFalse(Cache::has('catalog.approved.v2'));
        $pending = CatalogItem::create(['type' => 'substrate', 'slug' => 'private_sample', 'label_fa' => 'بستر اختصاصی', 'status' => 'pending', 'source' => 'user', 'created_by' => $owner->id]);
        $this->assertEqualsCanonicalizing([$approved->id, $pending->id], CatalogDirectory::visible(['substrate'], $owner)->pluck('id')->all());
        $this->assertSame([$approved->id], CatalogDirectory::visible(['substrate'], $other)->pluck('id')->all());
    }
}
