<?php

namespace App\Livewire\Greenhouse;

use App\Models\CatalogItem;
use App\Models\CorrectionRequest;
use App\Models\Greenhouse;
use App\Enums\Role;
use App\Services\CorrectionRequestService;
use App\Services\CatalogService;
use App\Services\CatalogVisibility;
use App\Services\GreenhouseRecordService;
use App\Services\GreenhouseService;
use App\Support\JalaliDate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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

    public array $customLabels = [];

    public string $customNotice = '';

    public string $correctionDetails = '';

    public bool $showCorrection = false;

    public string $provinceId = '';

    public string $cityId = '';

    public function updatedProvinceId(): void
    {
        $this->cityId = '';
    }

    public function correctLocation(GreenhouseRecordService $records): void
    {
        $records->correctLocation($this->record(), auth()->user(), ['province_id' => $this->provinceId, 'city_id' => $this->cityId]);
        $this->customNotice = 'موقعیت اصلی گلخانه اصلاح شد.';
    }

    public function requestCorrection(CorrectionRequestService $service): void
    {
        $this->resetErrorBag();
        try {
            $service->request(auth()->user(), $this->record(), $this->correctionDetails);
        } catch (ValidationException $exception) {
            $this->addError('correctionDetails', collect($exception->errors())->flatten()->first());
            return;
        }
        $this->correctionDetails = '';
        $this->showCorrection = false;
        $this->customNotice = 'درخواست اصلاح برای بررسی مدیر ثبت شد.';
    }

    public function mount(Greenhouse $greenhouse): void
    {
        $this->greenhouseId = $greenhouse->id;
        $requestedStep = (int) request()->query('step', 1);
        $this->step = in_array($requestedStep, [1, 2, 3], true) ? $requestedStep : 1;
        $greenhouse = $this->record();
        $this->provinceId = (string) ($greenhouse->province_id ?? '');
        $this->cityId = (string) ($greenhouse->city_id ?? '');
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
        $this->customNotice = '';
        $this->step = min(4, $this->step + 1);
    }

    public function previous(): void
    {
        $this->record();
        $this->resetErrorBag();
        $this->saved = false;
        $this->customNotice = '';
        $this->step = max(1, $this->step - 1);
    }

    public function addCustom(string $type, CatalogService $catalog): void
    {
        $this->record();
        $allowed = $this->step === 2 ? ['structure_type', 'cultivation_type'] : ($this->step === 3 ? ['irrigation_type', 'heating_system', 'cooling_system', 'water_source', 'ventilation_system'] : []);
        $label = $this->customLabels[$type] ?? '';
        if (! in_array($type, $allowed, true) || ! is_string($label) || ($this->step === 3 && ! is_array($this->options[$type] ?? null))) {
            throw ValidationException::withMessages(['record' => 'گزینه معتبر نیست.']);
        }
        try {
            $item = $catalog->findOrCreateCustom($type, $label, auth()->user());
        } catch (ValidationException $exception) {
            $this->addError('customLabels.'.$type, collect($exception->errors())->flatten()->first());

            return;
        }
        if ($type === 'structure_type' || $type === 'cultivation_type') {
            $this->{$type.'_id'} = (string) $item->id;
        } else {
            $noneIds = CatalogItem::where('type', $type)->where('slug', 'none')->pluck('id')->map(fn ($id) => (string) $id)->all();
            $existing = array_diff($this->options[$type] ?? [], $noneIds);
            $this->options[$type] = array_values(array_unique([...$existing, (string) $item->id]));
        }
        $this->customLabels[$type] = '';
        $this->customNotice = $item->status === 'pending' ? 'گزینه اضافه شد و پس از ذخیره در پرونده قابل استفاده است؛ مدیر آن را بررسی می‌کند.' : 'گزینه موجود انتخاب شد.';
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
        $attached = DB::table('greenhouse_options')->where('greenhouse_id', $greenhouse->id)->pluck('catalog_item_id')->all();
        $attached = array_merge($attached, array_filter([$greenhouse->structure_type_id, $greenhouse->cultivation_type_id]));
        $query = CatalogItem::whereIn('type', $types);
        CatalogVisibility::constrain($query, auth()->user(), $attached);
        $catalog = $query->orderByDesc('is_common')->orderBy('sort_order')->orderBy('label_fa')->get()->groupBy('type');

        $corrections = auth()->user()->hasRole(Role::GreenhouseOwner->value)
            ? CorrectionRequest::where('greenhouse_id', $greenhouse->id)->whereNull('cultivation_cycle_id')->where('requested_by', auth()->id())->latest()->limit(10)->get()
            : collect();

        $manager = auth()->user()->hasAnyRole([Role::Admin->value, Role::SuperAdmin->value]);
        $provinces = $manager ? CatalogItem::where('type', 'province')->where('status', 'approved')->orderBy('label_fa')->get() : collect();
        $cities = $manager && $this->provinceId !== '' ? CatalogItem::where('type', 'city')->where('status', 'approved')->where('parent_id', $this->provinceId)->orderBy('label_fa')->get() : collect();

        return view('livewire.greenhouse.record-wizard', ['greenhouse' => $greenhouse, 'catalog' => $catalog, 'corrections' => $corrections, 'provinces' => $provinces, 'cities' => $cities]);
    }
}
