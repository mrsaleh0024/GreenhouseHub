<?php

namespace App\Livewire\Greenhouse;

use App\Models\CatalogItem;
use App\Models\Greenhouse;
use App\Services\GreenhouseRecordService;
use App\Services\GreenhouseService;
use App\Support\JalaliDate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('تکمیل پرونده گلخانه')]
class RecordWizard extends Component
{
    #[Locked]
    public int $greenhouseId;

    public int $step = 1;

    public string $name = '';

    public string $area_m2 = '';

    public string $address = '';

    public string $latitude = '';

    public string $longitude = '';

    public string $altitude_m = '';

    public string $commissioned_on = '';

    public string $structure_type_id = '';

    public string $cultivation_type_id = '';

    public array $options = [
        'irrigation_type' => [], 'heating_system' => [], 'cooling_system' => [],
        'water_source' => [], 'ventilation_system' => [],
    ];

    public bool $saved = false;

    public function mount(Greenhouse $greenhouse): void
    {
        $this->greenhouseId = $greenhouse->id;
        $greenhouse = $this->record();
        $this->name = $greenhouse->name;
        foreach (['area_m2', 'address', 'latitude', 'longitude', 'altitude_m', 'structure_type_id', 'cultivation_type_id'] as $field) {
            $this->{$field} = (string) ($greenhouse->{$field} ?? '');
        }
        $this->commissioned_on = $greenhouse->commissioned_on ? JalaliDate::fromGregorian($greenhouse->commissioned_on) : '';
        $items = DB::table('greenhouse_options')->join('catalog_items', 'catalog_items.id', '=', 'greenhouse_options.catalog_item_id')
            ->where('greenhouse_options.greenhouse_id', $greenhouse->id)->get(['catalog_items.type', 'catalog_items.id']);
        foreach ($items as $item) {
            if (array_key_exists($item->type, $this->options)) {
                $this->options[$item->type][] = (string) $item->id;
            }
        }
    }

    public function saveAndContinue(GreenhouseRecordService $records, GreenhouseService $systems): void
    {
        $greenhouse = $this->record();
        $user = auth()->user();
        $this->resetErrorBag();

        if ($this->step === 1) {
            $records->saveBasics($greenhouse, $user, [
                'name' => trim($this->name), 'area_m2' => $this->nullable($this->area_m2),
                'address' => $this->nullable($this->address), 'latitude' => $this->nullable($this->latitude),
                'longitude' => $this->nullable($this->longitude), 'altitude_m' => $this->nullable($this->altitude_m),
                'commissioned_on' => $this->nullable($this->commissioned_on) === null ? null : JalaliDate::toGregorian($this->commissioned_on, 'commissioned_on'),
            ]);
        } elseif ($this->step === 2) {
            $records->saveStructure($greenhouse, $user, [
                'structure_type_id' => $this->nullable($this->structure_type_id),
                'cultivation_type_id' => $this->nullable($this->cultivation_type_id),
            ]);
        } elseif ($this->step === 3) {
            $systems->syncOptions($greenhouse, $this->options, $user);
        } else {
            abort(404);
        }

        $this->saved = true;
        $this->step = min(4, $this->step + 1);
    }

    public function previous(): void
    {
        $this->record();
        $this->resetErrorBag();
        $this->saved = false;
        $this->step = max(1, $this->step - 1);
    }

    private function nullable(string $value): ?string
    {
        $value = trim($value);

        return $value === '' ? null : $value;
    }

    private function record(): Greenhouse
    {
        $greenhouse = Greenhouse::visibleTo(auth()->user())->findOrFail($this->greenhouseId);
        Gate::authorize('update', $greenhouse);

        return $greenhouse;
    }

    public function render()
    {
        $greenhouse = $this->record();
        $types = ['structure_type', 'cultivation_type', 'irrigation_type', 'heating_system', 'cooling_system', 'water_source', 'ventilation_system'];
        $catalog = CatalogItem::whereIn('type', $types)->where(function ($query) {
            $query->where('status', 'approved')->orWhere(function ($query) {
                $query->where('status', 'pending')->where('created_by', auth()->id());
            });
        })->orderByDesc('is_common')->orderBy('sort_order')->orderBy('label_fa')->get()->groupBy('type');

        return view('livewire.greenhouse.record-wizard', ['greenhouse' => $greenhouse, 'catalog' => $catalog]);
    }
}
