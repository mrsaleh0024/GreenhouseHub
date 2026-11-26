<?php

namespace App\Livewire\Greenhouse;

use App\Models\Greenhouse;
use App\Services\ActivityLogger;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;

class RecordRemoval extends Component
{
    #[Locked]
    public int $greenhouseId;

    #[Locked]
    public ?int $confirmedAt = null;

    public function mount(int $greenhouseId): void
    {
        $this->greenhouseId = $greenhouseId;
    }

    public function confirmFirst(): void
    {
        Gate::authorize('archive', Greenhouse::findOrFail($this->greenhouseId));
        $this->confirmedAt = now()->timestamp;
    }

    public function remove(): void
    {
        $record = Greenhouse::findOrFail($this->greenhouseId);
        Gate::authorize('archive', $record);
        abort_unless($this->confirmedAt !== null && now()->timestamp - $this->confirmedAt >= 3 && now()->timestamp - $this->confirmedAt < 900, 422);
        DB::transaction(function () {
            $record = Greenhouse::whereKey($this->greenhouseId)->lockForUpdate()->firstOrFail();
            Gate::authorize('archive', $record);
            $record->deleted_at = now();
            $record->save();
            app(ActivityLogger::class)->log($record, 'deleted');
        });
        $this->redirectRoute('dashboard', navigate: true);
    }

    public function render(): View
    {
        return view('livewire.greenhouse.record-removal');
    }
}
