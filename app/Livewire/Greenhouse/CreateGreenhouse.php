<?php

namespace App\Livewire\Greenhouse;

use App\Models\CatalogItem;
use App\Models\Greenhouse;
use App\Services\CatalogService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class CreateGreenhouse extends Component
{
    public string $name = '';

    public string $province_id = '';

    public string $city = '';

    public function mount(): void
    {
        $this->checkOwner();
    }

    private function checkOwner(): void
    {
        abort_unless(auth()->user()->hasRole('greenhouse_owner') && auth()->user()->isMobileVerified() && auth()->user()->customer(), 403);
    }

    public function save(): void
    {
        $this->checkOwner();
        $this->name = trim($this->name);
        $this->city = trim($this->city);
        $this->validate(['name' => 'required|string|max:120', 'city' => 'required|string|max:80', 'province_id' => ['required', 'integer', Rule::exists('catalog_items', 'id')->where('type', 'province')->where('status', 'approved')]], [], ['name' => 'نام گلخانه', 'city' => 'شهر', 'province_id' => 'استان']);
        $record = DB::transaction(function () {
            $user = auth()->user();
            $city = app(CatalogService::class)->findOrCreateCustom('city', $this->city, $user, (int) $this->province_id);

            return Greenhouse::create(['customer_id' => $user->customer()->id, 'name' => $this->name, 'province_id' => (int) $this->province_id, 'city_id' => $city->id, 'status' => 'active']);
        });
        $this->redirectRoute('greenhouses.complete', ['greenhouse' => $record->id], navigate: true);
    }

    public function render(): View
    {
        return view('livewire.greenhouse.create-greenhouse', ['provinces' => CatalogItem::where('type', 'province')->where('status', 'approved')->orderBy('sort_order')->get()]);
    }
}
