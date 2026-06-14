<?php

namespace App\Livewire;

use App\Models\Vehicle;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;

/**
 * REFERENCE PATTERN — full CRUD for the Vehicles module.
 *
 * This is the template to replicate for the other master-data modules
 * (Drivers, Routes, Fuel logs, Maintenance logs, etc). It demonstrates:
 *   - list + live search + pagination
 *   - a modal-driven create/edit form
 *   - validation via #[Validate] attributes
 *   - delete with confirmation
 *
 * Works on Livewire 3 and 4 (class-based component).
 * Compatible with the spatie/laravel-permission gate checks added in
 * Phase 1 — wrap destructive buttons in @can('manage-fleet') in Blade.
 */
class VehicleManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:30')]
    public string $registration_number = '';

    #[Validate('required|string|max:30')]
    public string $type = 'bus';

    #[Validate('nullable|string|max:60')]
    public string $brand = '';

    #[Validate('nullable|string|max:60')]
    public string $model = '';

    #[Validate('nullable|in:diesel,petrol,electric,hybrid,cng')]
    public string $fuel_type = 'diesel';

    #[Validate('required|integer|min:1|max:120')]
    public int $seating_capacity = 50;

    #[Validate('required|integer|min:0')]
    public int $mileage = 0;

    #[Validate('required|in:available,in_service,maintenance')]
    public string $status = 'available';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'registration_number', 'type', 'brand', 'model', 'fuel_type', 'seating_capacity', 'mileage', 'status']);
        $this->type = 'bus';
        $this->fuel_type = 'diesel';
        $this->seating_capacity = 50;
        $this->status = 'available';
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $vehicle = Vehicle::findOrFail($id);
        $this->editingId = $vehicle->id;
        $this->registration_number = $vehicle->registration_number;
        $this->type = $vehicle->type;
        $this->brand = $vehicle->brand ?? '';
        $this->model = $vehicle->model ?? '';
        $this->fuel_type = $vehicle->fuel_type ?? 'diesel';
        $this->seating_capacity = $vehicle->seating_capacity;
        $this->mileage = $vehicle->mileage;
        $this->status = $vehicle->status;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'registration_number' => 'required|string|max:30|unique:vehicles,registration_number,' . $this->editingId,
            'type'                => 'required|string|max:30',
            'brand'               => 'nullable|string|max:60',
            'model'               => 'nullable|string|max:60',
            'fuel_type'           => 'nullable|in:diesel,petrol,electric,hybrid,cng',
            'seating_capacity'    => 'required|integer|min:1|max:120',
            'mileage'             => 'required|integer|min:0',
            'status'              => 'required|in:available,in_service,maintenance',
        ]);

        // Store blank optional text fields as null rather than empty strings.
        $data['brand'] = $data['brand'] ?: null;
        $data['model'] = $data['model'] ?: null;

        Vehicle::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        session()->flash('status', $this->editingId ? 'Vehicle updated.' : 'Vehicle added.');
        $this->reset(['editingId']);
    }

    public function delete(int $id): void
    {
        Vehicle::findOrFail($id)->delete();
        session()->flash('status', 'Vehicle deleted.');
    }

    public function render()
    {
        $vehicles = Vehicle::query()
            ->when($this->search, fn ($q) => $q
                ->where('registration_number', 'like', "%{$this->search}%")
                ->orWhere('type', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        return view('livewire.vehicle-manager', compact('vehicles'));
    }
}
