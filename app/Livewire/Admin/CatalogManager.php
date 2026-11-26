<?php

namespace App\Livewire\Admin;

use App\Enums\CatalogType;
use App\Enums\Role;
use App\Models\CatalogItem;
use App\Services\CatalogService;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('بررسی گزینه‌های پیشنهادی')]
class CatalogManager extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $reviewId = null;

    #[Locked]
    public ?int $mergeSourceId = null;

    #[Locked]
    public ?int $mergeTargetId = null;

    #[Locked]
    public array $conflicts = [];

    public string $slug = '';

    public string $labelEn = '';

    public string $candidateId = '';

    public array $quantities = [];

    public string $notice = '';

    public function mount(): void
    {
        $this->authorizeManager();
    }

    public function startReview(int $id): void
    {
        $this->authorizeManager();
        $item = CatalogItem::where('status', 'pending')->findOrFail($id);
        $this->clearForm();
        $this->reviewId = $item->id;
    }

    public function approve(CatalogService $service): void
    {
        $this->authorizeManager();
        $item = CatalogItem::where('status', 'pending')->findOrFail($this->reviewId ?? 0);
        $service->approve($item, auth()->user(), trim($this->slug) ?: null, trim($this->labelEn) ?: null);
        $this->clearForm();
        $this->notice = 'گزینه تأیید شد.';
    }

    public function reject(int $id, CatalogService $service): void
    {
        $this->authorizeManager();
        $item = CatalogItem::where('status', 'pending')->findOrFail($id);
        $service->reject($item, auth()->user());
        $this->clearForm();
        $this->notice = 'گزینه بدون استفاده رد شد.';
    }

    public function startMerge(int $id): void
    {
        $this->authorizeManager();
        $item = CatalogItem::where('status', 'pending')->findOrFail($id);
        $this->clearForm();
        $this->mergeSourceId = $item->id;
    }

    public function inspectMerge(CatalogService $service): void
    {
        $this->authorizeManager();
        $source = CatalogItem::where('status', 'pending')->findOrFail($this->mergeSourceId ?? 0);
        $data = Validator::make(['candidate' => $this->candidateId], ['candidate' => ['required', 'integer']], [
            'candidate.required' => 'ابتدا گزینه مقصد را انتخاب کنید.', 'candidate.integer' => 'گزینه مقصد معتبر نیست.',
        ])->validate();
        $target = CatalogItem::where('type', $source->type)->where('parent_key', $source->parent_key)
            ->whereIn('status', ['approved', 'pending'])->whereKeyNot($source->id)->findOrFail($data['candidate']);
        $this->mergeTargetId = $target->id;
        $this->conflicts = $service->equipmentConflicts($source, $target, auth()->user())->map(fn ($row) => (array) $row)->all();
        $this->quantities = [];
    }

    public function changeTarget(): void
    {
        $this->authorizeManager();
        $this->mergeTargetId = null;
        $this->conflicts = [];
        $this->quantities = [];
        $this->resetErrorBag();
    }

    public function merge(CatalogService $service): void
    {
        $this->authorizeManager();
        $source = CatalogItem::where('status', 'pending')->findOrFail($this->mergeSourceId ?? 0);
        $target = CatalogItem::findOrFail($this->mergeTargetId ?? 0);
        $service->merge($source, $target, auth()->user(), $this->quantities);
        $this->clearForm();
        $this->notice = 'گزینه‌ها ادغام شدند.';
    }

    public function cancel(): void
    {
        $this->authorizeManager();
        $this->clearForm();
    }

    private function clearForm(): void
    {
        $this->reset(['reviewId', 'mergeSourceId', 'mergeTargetId', 'conflicts', 'slug', 'labelEn', 'candidateId', 'quantities', 'notice']);
        $this->resetErrorBag();
    }

    private function authorizeManager(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([Role::Admin->value, Role::SuperAdmin->value]), 403);
    }

    public function render()
    {
        $this->authorizeManager();
        $items = CatalogItem::where('status', 'pending')->with('parent:id,label_fa')->orderBy('created_at')->orderBy('id')->paginate(12);
        $source = $this->mergeSourceId ? CatalogItem::where('status', 'pending')->find($this->mergeSourceId) : null;
        $target = $this->mergeTargetId ? CatalogItem::find($this->mergeTargetId) : null;
        $candidates = $source ? CatalogItem::where('type', $source->type)->where('parent_key', $source->parent_key)
            ->whereIn('status', ['approved', 'pending'])->whereKeyNot($source->id)->orderByDesc('is_common')->orderBy('label_fa')->limit(100)->get() : collect();

        return view('livewire.admin.catalog-manager', [
            'items' => $items, 'source' => $source, 'target' => $target, 'candidates' => $candidates,
            'catalogLabels' => collect(CatalogType::cases())->mapWithKeys(fn (CatalogType $type) => [$type->value => $type->label()]),
        ]);
    }
}
