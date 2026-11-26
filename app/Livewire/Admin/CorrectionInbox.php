<?php

namespace App\Livewire\Admin;

use App\Enums\Role;
use App\Models\CorrectionRequest;
use App\Services\CorrectionRequestService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('درخواست‌های اصلاح')]
class CorrectionInbox extends Component
{
    use WithPagination;

    #[Locked]
    public ?int $resolvingId = null;

    public string $resolution = '';

    public string $notice = '';

    public function mount(): void
    {
        $this->authorizeManager();
    }

    public function startResolve(int $id): void
    {
        $this->authorizeManager();
        $request = CorrectionRequest::where('status', 'pending')->findOrFail($id);
        $this->resolvingId = $request->id;
        $this->resolution = '';
        $this->notice = '';
        $this->resetErrorBag();
    }

    public function resolve(CorrectionRequestService $service): void
    {
        $this->authorizeManager();
        $request = CorrectionRequest::findOrFail($this->resolvingId ?? 0);
        try {
            $service->resolve(auth()->user(), $request, $this->resolution);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $field => $messages) {
                $this->addError($field, $messages[0]);
            }

            return;
        }
        $this->resolvingId = null;
        $this->resolution = '';
        $this->notice = 'نتیجه بررسی ثبت شد.';
    }

    public function cancel(): void
    {
        $this->authorizeManager();
        $this->resolvingId = null;
        $this->resolution = '';
        $this->resetErrorBag();
    }

    private function authorizeManager(): void
    {
        abort_unless(auth()->user()?->hasAnyRole([Role::Admin->value, Role::SuperAdmin->value]), 403);
    }

    public function render()
    {
        $this->authorizeManager();
        $requests = CorrectionRequest::with(['greenhouse:id,name,greenhouse_code,deleted_at', 'cycle:id,cycle_code,deleted_at'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderByDesc('created_at')->orderByDesc('id')->paginate(12);

        return view('livewire.admin.correction-inbox', ['requests' => $requests]);
    }
}
