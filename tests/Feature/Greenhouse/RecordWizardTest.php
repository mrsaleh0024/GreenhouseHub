<?php

namespace Tests\Feature\Greenhouse;

use App\Enums\Role;
use App\Livewire\Greenhouse\RecordWizard;
use App\Models\CatalogItem;
use App\Models\Customer;
use App\Models\Greenhouse;
use App\Models\User;
use Database\Seeders\CatalogSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class RecordWizardTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_saves_each_step_and_can_resume(): void
    {
        $this->withoutVite();
        $this->seed([RoleSeeder::class, CatalogSeeder::class]);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);
        $structure = CatalogItem::where('type', 'structure_type')->firstOrFail();
        $drip = CatalogItem::where('type', 'irrigation_type')->where('slug', 'drip')->firstOrFail();

        Livewire::actingAs($owner)->test(RecordWizard::class, ['greenhouse' => $greenhouse])
            ->set('name', 'گلخانه سبز')
            ->set('area_m2', '500')
            ->call('saveAndContinue')->assertHasNoErrors()->assertSet('step', 2)
            ->set('structure_type_id', (string) $structure->id)
            ->call('saveAndContinue')->assertHasNoErrors()->assertSet('step', 3)
            ->set('options.irrigation_type', [(string) $drip->id])
            ->call('saveAndContinue')->assertHasNoErrors()->assertSet('step', 4);

        $this->assertDatabaseHas('greenhouses', ['id' => $greenhouse->id, 'name' => 'گلخانه سبز', 'structure_type_id' => $structure->id]);
        $this->assertDatabaseHas('greenhouse_options', ['greenhouse_id' => $greenhouse->id, 'catalog_item_id' => $drip->id]);
        Livewire::actingAs($owner)->test(RecordWizard::class, ['greenhouse' => $greenhouse->fresh()])->assertSet('name', 'گلخانه سبز');
    }

    public function test_another_owner_cannot_open_or_edit_the_record_and_expert_cannot_edit(): void
    {
        $this->withoutVite();
        $this->seed(RoleSeeder::class);
        $owner = User::factory()->role(Role::GreenhouseOwner)->create();
        $other = User::factory()->role(Role::GreenhouseOwner)->create();
        $expert = User::factory()->role(Role::Expert)->create();
        $customer = Customer::create(['full_name' => 'مشتری', 'mobile' => '09123456789']);
        $customer->users()->attach($owner);
        $greenhouse = Greenhouse::create(['customer_id' => $customer->id, 'name' => 'گلخانه']);

        $this->actingAs($other)->get(route('greenhouses.complete', $greenhouse))->assertNotFound();
        $this->actingAs($expert)->get(route('greenhouses.complete', $greenhouse))->assertForbidden();
    }
}
