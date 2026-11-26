<?php

namespace App\Livewire\Greenhouse;

use App\Enums\Role;
use App\Models\CatalogItem;
use App\Models\CorrectionRequest;
use App\Models\CultivationCycle;
use App\Models\Greenhouse;
use App\Models\Zone;
use App\Services\CatalogService;
use App\Services\CatalogVisibility;
use App\Services\CorrectionRequestService;
use App\Services\CultivationCycleService;
use App\Services\SubstrateProfile;
use App\Support\JalaliDate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('کشت‌های گلخانه')]
class Cultivation extends Component
{
    use WithPagination;

    #[Locked]
    public int $greenhouseId;

    #[Locked]
    public ?int $editingId = null;

    #[Locked]
    public bool $editingClosed = false;

    #[Locked]
    public ?int $finishId = null;

    #[Locked]
    public ?int $correctionId = null;

    public bool $showForm = false;

    public string $crop_id = '';

    public string $variety_id = '';

    public string $zone_id = '';

    public string $substrate_id = '';

    public string $planting_date = '';

    public string $expected_harvest_date = '';

    public string $harvest_started_on = '';

    public string $plant_count = '';

    public string $planted_area_m2 = '';

    public string $end_date = '';

    public string $yield_kg = '';

    public string $correction_details = '';

    public string $notice = '';

    public array $customLabels = [];

    public array $substrateDetails = [];

    public function updatedSubstrateId(): void
    {
        $this->substrateDetails = [];
    }

    public function mount(Greenhouse $greenhouse): void
    {
        $this->greenhouseId = $greenhouse->id;
        $this->record();
    }

    public function startNew(): void
    {
        abort_unless(auth()->user()->hasRole(Role::GreenhouseOwner->value), 403);
        Gate::authorize('manageCycles', $this->record());
        $this->resetForm();
        $this->showForm = true;
    }

    public function startEdit(int $id): void
    {
        $cycle = $this->cycle($id);
        Gate::authorize('update', $cycle);
        $this->resetForm();
        $this->editingId = $cycle->id;
        $this->substrateDetails = $cycle->substrate_details['fields'] ?? [];
        if (! empty($this->substrateDetails['measurement_date'])) {
            $this->substrateDetails['measurement_date'] = JalaliDate::fromGregorian($this->substrateDetails['measurement_date']);
        }
        foreach (['crop_id', 'variety_id', 'zone_id', 'substrate_id', 'plant_count', 'planted_area_m2'] as $field) {
            $this->{$field} = (string) ($cycle->{$field} ?? '');
        }
        foreach (['planting_date', 'expected_harvest_date', 'harvest_started_on'] as $field) {
            $this->{$field} = $cycle->{$field} ? JalaliDate::fromGregorian($cycle->{$field}) : '';
        }
        if ($cycle->harvest_ended_on && auth()->user()->hasAnyRole([Role::Admin->value, Role::SuperAdmin->value])) {
            $this->editingClosed = true;
            $this->end_date = JalaliDate::fromGregorian($cycle->harvest_ended_on);
            $this->yield_kg = (string) ($cycle->total_yield_kg ?? '');
        }
        $this->showForm = true;
    }

    public function save(CultivationCycleService $service): void
    {
        $greenhouse = $this->record();
        Gate::authorize('manageCycles', $greenhouse);
        $cycle = $this->editingId ? $this->cycle($this->editingId) : null;
        $this->resetErrorBag();
        $data = [
            'crop_id' => $this->nullable($this->crop_id), 'variety_id' => $this->nullable($this->variety_id), 'zone_id' => $this->nullable($this->zone_id),
            'substrate_id' => $this->nullable($this->substrate_id), 'plant_count' => $this->nullable($this->plant_count),
            'substrate_details' => $this->substrateDetails,
            'planted_area_m2' => $this->nullable($this->planted_area_m2),
            'planting_date' => $this->jalali($this->planting_date, 'planting_date'),
            'expected_harvest_date' => $this->jalali($this->expected_harvest_date, 'expected_harvest_date'),
            'harvest_started_on' => $this->jalali($this->harvest_started_on, 'harvest_started_on'),
        ];
        if (! empty($data['substrate_details']['measurement_date']) && is_string($data['substrate_details']['measurement_date'])) {
            $data['substrate_details']['measurement_date'] = JalaliDate::toGregorian($data['substrate_details']['measurement_date'], 'substrateDetails.measurement_date');
        }
        $correctEnd = $cycle && $cycle->harvest_ended_on && auth()->user()->hasAnyRole([Role::Admin->value, Role::SuperAdmin->value]);
        $endDate = $correctEnd ? $this->jalali($this->end_date, 'end_date') : null;
        if ($correctEnd && $endDate === null) {
            $this->addError('end_date', 'تاریخ پایان برداشت را وارد کنید.');

            return;
        }
        DB::transaction(function () use ($service, $greenhouse, $cycle, $data, $correctEnd, $endDate) {
            if ($correctEnd) {
                $service->correctClosedHarvest($greenhouse, $cycle, auth()->user(), $endDate, $this->nullable($this->yield_kg));
            }
            $service->save($greenhouse, auth()->user(), $data, $cycle);
        });
        $this->resetForm();
        $this->notice = 'اطلاعات کشت ذخیره شد.';
    }

    public function startFinish(int $id): void
    {
        $cycle = $this->cycle($id);
        Gate::authorize('update', $cycle);
        $this->resetForm();
        $this->finishId = $cycle->id;
        $this->end_date = JalaliDate::fromGregorian(now());
    }

    public function finish(CultivationCycleService $service): void
    {
        $greenhouse = $this->record();
        $cycle = $this->cycle($this->finishId ?? 0);
        $this->resetErrorBag();
        $date = $this->jalali($this->end_date, 'end_date');
        if ($date === null) {
            $this->addError('end_date', 'تاریخ پایان برداشت را وارد کنید.');

            return;
        }
        try {
            $service->finish($greenhouse, $cycle, auth()->user(), $date, $this->nullable($this->yield_kg));
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field === 'end' ? 'end_date' : ($field === 'yield' ? 'yield_kg' : $field), $messages[0]);
            }

            return;
        }
        $this->resetForm();
        $this->notice = 'پایان برداشت ثبت شد. برای اصلاح این سابقه می‌توانید درخواست بفرستید.';
    }

    public function startCorrection(int $id): void
    {
        $cycle = $this->cycle($id);
        Gate::authorize('view', $cycle);
        $this->resetForm();
        $this->correctionId = $cycle->id;
    }

    public function sendCorrection(CorrectionRequestService $service): void
    {
        $greenhouse = $this->record();
        $cycle = $this->cycle($this->correctionId ?? 0);
        $this->resetErrorBag();
        try {
            $service->request(auth()->user(), $greenhouse, $this->correction_details, $cycle);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field === 'details' ? 'correction_details' : $field, $messages[0]);
            }

            return;
        }
        $this->resetForm();
        $this->notice = 'درخواست اصلاح برای بررسی مدیر ثبت شد.';
    }

    public function cancel(): void
    {
        $this->record();
        $this->resetForm();
    }

    public function updatedCropId(): void
    {
        $this->variety_id = '';
    }

    public function addCustom(string $type, CatalogService $service): void
    {
        Gate::authorize('manageCycles', $this->record());
        if ($this->editingId) {
            Gate::authorize('update', $this->cycle($this->editingId));
        }
        $label = $this->customLabels[$type] ?? '';
        if (! in_array($type, ['crop', 'variety', 'substrate'], true) || ! is_string($label)) {
            throw ValidationException::withMessages(['cycle' => 'گزینه معتبر نیست.']);
        }
        if ($type === 'variety' && ! ctype_digit($this->crop_id)) {
            $this->addError('customLabels.variety', 'ابتدا محصول را انتخاب کنید.');

            return;
        }
        try {
            $item = $service->findOrCreateCustom($type, $label, auth()->user(), $type === 'variety' ? (int) $this->crop_id : null);
        } catch (ValidationException $exception) {
            $this->addError('customLabels.'.$type, collect($exception->errors())->flatten()->first());

            return;
        }
        $this->{$type.'_id'} = (string) $item->id;
        if ($type === 'crop') {
            $this->variety_id = '';
        }
        $this->customLabels[$type] = '';
        $this->notice = $item->status === 'pending' ? 'گزینه اضافه شد؛ پس از ذخیره کشت قابل استفاده است و مدیر آن را بررسی می‌کند.' : 'گزینه موجود انتخاب شد.';
    }

    private function resetForm(): void
    {
        $this->substrateDetails = [];
        $this->reset(['editingId', 'editingClosed', 'finishId', 'correctionId', 'showForm', 'crop_id', 'variety_id', 'zone_id', 'substrate_id', 'planting_date', 'expected_harvest_date', 'harvest_started_on', 'plant_count', 'planted_area_m2', 'end_date', 'yield_kg', 'correction_details', 'notice', 'customLabels']);
        $this->resetErrorBag();
    }

    private function nullable(string $value): ?string
    {
        return trim($value) === '' ? null : trim($value);
    }

    private function jalali(string $value, string $field): ?string
    {
        return $this->nullable($value) === null ? null : JalaliDate::toGregorian($value, $field);
    }

    private function record(): Greenhouse
    {
        $greenhouse = Greenhouse::visibleTo(auth()->user())->findOrFail($this->greenhouseId);
        Gate::authorize('view', $greenhouse);

        return $greenhouse;
    }

    private function cycle(int $id): CultivationCycle
    {
        return CultivationCycle::visibleTo(auth()->user())->where('greenhouse_id', $this->greenhouseId)->findOrFail($id);
    }

    public function render()
    {
        $greenhouse = $this->record();
        $cycles = CultivationCycle::visibleTo(auth()->user())->where('greenhouse_id', $greenhouse->id)
            ->with(['crop:id,label_fa', 'zone:id,name'])->orderByDesc('planting_date')->orderByDesc('id')->paginate(12);
        $editing = $this->editingId ? $this->cycle($this->editingId) : null;
        if ($editing) {
            Gate::authorize('update', $editing);
        }
        $attached = array_filter([$editing?->crop_id, $editing?->variety_id, $editing?->substrate_id]);
        $catalogQuery = CatalogItem::whereIn('type', ['crop', 'substrate']);
        CatalogVisibility::constrain($catalogQuery, auth()->user(), $attached);
        $catalog = $catalogQuery->orderByDesc('is_common')->orderBy('sort_order')->orderBy('label_fa')->get()->groupBy('type');
        $varieties = collect();
        if (ctype_digit($this->crop_id)) {
            $varietyQuery = CatalogItem::where('type', 'variety')->where('parent_id', (int) $this->crop_id);
            CatalogVisibility::constrain($varietyQuery, auth()->user(), $attached);
            $varieties = $varietyQuery->orderBy('label_fa')->get();
        }
        $corrections = collect();
        if (auth()->user()->hasRole(Role::GreenhouseOwner->value)) {
            $corrections = CorrectionRequest::where('greenhouse_id', $greenhouse->id)
                ->where('requested_by', auth()->id())->whereIn('cultivation_cycle_id', $cycles->pluck('id'))
                ->orderByDesc('id')->get()->groupBy('cultivation_cycle_id');
        }

        return view('livewire.greenhouse.cultivation', [
            'substrateProfile' => SubstrateProfile::forItem($catalog->get('substrate', collect())->firstWhere('id', (int) $this->substrate_id)),
            'greenhouse' => $greenhouse, 'cycles' => $cycles, 'catalog' => $catalog, 'corrections' => $corrections, 'varieties' => $varieties,
            'zones' => Zone::where('greenhouse_id', $greenhouse->id)->orderBy('code')->get(['id', 'name', 'code']),
        ]);
    }
}
