<?php

namespace App\Livewire\Greenhouse;

use App\Models\CatalogItem;
use App\Models\Greenhouse;
use App\Models\Zone;
use App\Services\ZoneService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('ناحیه‌های گلخانه')]
class Zones extends Component
{
    use WithPagination;

    #[Locked]
    public int $greenhouseId;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public ?int $equipmentZoneId = null;

    public bool $showForm = false;

    public string $name = '';

    public string $zone_type_id = '';

    public string $area_m2 = '';

    public string $capacity_plants = '';

    public array $equipmentIds = [];

    public array $equipmentQuantities = [];

    public string $notice = '';

    public function mount(Greenhouse $greenhouse): void
    {
        $this->greenhouseId = $greenhouse->id;
        $this->record();
    }

    public function startNew(): void
    {
        Gate::authorize('manageZones', $this->record());
        $this->resetForm();
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        $zone = $this->zone($id);
        Gate::authorize('update', $zone);
        $this->resetForm();
        $this->editingId = $zone->id;
        foreach (['name', 'zone_type_id', 'area_m2', 'capacity_plants'] as $field) {
            $this->{$field} = (string) ($zone->{$field} ?? '');
        }
        $this->showForm = true;
    }

    public function save(ZoneService $service): void
    {
        $greenhouse = $this->record();
        Gate::authorize('manageZones', $greenhouse);
        $zone = $this->editingId ? $this->zone($this->editingId) : null;
        $service->save($greenhouse, auth()->user(), [
            'name' => trim($this->name), 'zone_type_id' => $this->nullable($this->zone_type_id),
            'area_m2' => $this->nullable($this->area_m2), 'capacity_plants' => $this->nullable($this->capacity_plants),
        ], $zone);
        $this->resetForm();
        $this->notice = 'اطلاعات ناحیه ذخیره شد.';
    }

    public function startEquipment(int $id): void
    {
        $zone = $this->zone($id);
        Gate::authorize('update', $zone);
        $this->resetForm();
        $this->equipmentZoneId = $zone->id;
        foreach (DB::table('zone_equipment')->where('zone_id', $zone->id)->get(['equipment_id', 'quantity']) as $item) {
            $this->equipmentIds[] = (string) $item->equipment_id;
            $this->equipmentQuantities[$item->equipment_id] = (string) $item->quantity;
        }
    }

    public function saveEquipment(ZoneService $service): void
    {
        $zone = $this->zone($this->equipmentZoneId ?? 0);
        Gate::authorize('update', $zone);
        Validator::make(['ids' => $this->equipmentIds, 'quantities' => $this->equipmentQuantities], [
            'ids' => ['array'], 'ids.*' => ['integer', 'distinct'], 'quantities' => ['array'], 'quantities.*' => ['nullable', 'integer', 'between:1,65535'],
        ], ['integer' => 'تعداد تجهیزات باید عدد صحیح باشد.', 'between' => 'تعداد تجهیزات باید دست‌کم یک عدد باشد.'])->validate();
        $rows = [];
        foreach ($this->equipmentIds as $id) {
            $rows[] = ['equipment_id' => (int) $id, 'quantity' => ($this->equipmentQuantities[$id] ?? '') === '' ? 1 : $this->equipmentQuantities[$id]];
        }
        $service->syncEquipment($zone, auth()->user(), $rows);
        $this->resetForm();
        $this->notice = 'تجهیزات ناحیه ذخیره شد.';
    }

    public function cancel(): void
    {
        $this->record();
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->reset(['editingId', 'equipmentZoneId', 'showForm', 'name', 'zone_type_id', 'area_m2', 'capacity_plants', 'equipmentIds', 'equipmentQuantities', 'notice']);
        $this->resetErrorBag();
    }

    private function nullable(string $value): ?string
    {
        return trim($value) === '' ? null : trim($value);
    }

    private function record(): Greenhouse
    {
        $greenhouse = Greenhouse::visibleTo(auth()->user())->findOrFail($this->greenhouseId);
        Gate::authorize('view', $greenhouse);

        return $greenhouse;
    }

    private function zone(int $id): Zone
    {
        return Zone::visibleTo(auth()->user())->where('greenhouse_id', $this->greenhouseId)->findOrFail($id);
    }

    public function render()
    {
        $greenhouse = $this->record();
        $zones = Zone::visibleTo(auth()->user())->where('greenhouse_id', $greenhouse->id)->orderBy('id')->paginate(12);
        $equipment = CatalogItem::where('type', 'equipment')->where('status', 'approved')->orderByDesc('is_common')->orderBy('sort_order')->get();
        $zoneTypes = CatalogItem::where('type', 'zone_type')->where('status', 'approved')->orderByDesc('is_common')->orderBy('sort_order')->get();
        $equipmentByZone = DB::table('zone_equipment')->join('catalog_items', 'catalog_items.id', '=', 'zone_equipment.equipment_id')
            ->whereIn('zone_id', $zones->pluck('id'))->orderBy('catalog_items.sort_order')->get(['zone_equipment.zone_id', 'zone_equipment.quantity', 'catalog_items.label_fa'])->groupBy('zone_id');

        return view('livewire.greenhouse.zones', compact('greenhouse', 'zones', 'zoneTypes', 'equipment', 'equipmentByZone'));
    }
}
