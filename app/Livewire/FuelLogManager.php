<?php

namespace App\Livewire;

use App\Models\FuelLog;
use App\Models\Vehicle;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;

/**
 * Fuel logging (Phase 3) — record fuel per vehicle, filter by vehicle + date
 * range. Follows the VehicleManager pattern. The whole screen is guarded by the
 * `log-fuel` permission (route middleware), which every role holds, so there is
 * no per-button @can gating here.
 */
class FuelLogManager extends Component
{
    use WithPagination;

    // --- Filters ---
    public string $filterVehicleId = '';
    public string $dateFrom = '';
    public string $dateTo = '';

    // --- Form state ---
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Validate('required|exists:vehicles,id')]
    public string $vehicle_id = '';

    #[Validate('required|numeric|min:0')]
    public float $liters = 0;

    #[Validate('required|numeric|min:0')]
    public float $cost = 0;

    #[Validate('nullable|integer|min:0')]
    public ?int $odometer = null;

    #[Validate('required|date')]
    public string $logged_at = '';

    public function updatedFilterVehicleId(): void
    {
        $this->resetPage();
    }

    public function updatedDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatedDateTo(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'vehicle_id', 'liters', 'cost', 'odometer', 'logged_at']);
        $this->logged_at = now()->toDateString();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $log = FuelLog::findOrFail($id);
        $this->editingId = $log->id;
        $this->vehicle_id = (string) $log->vehicle_id;
        $this->liters = (float) $log->liters;
        $this->cost = (float) $log->cost;
        $this->odometer = $log->odometer;
        $this->logged_at = $log->logged_at->toDateString();
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'vehicle_id' => 'required|exists:vehicles,id',
            'liters'     => 'required|numeric|min:0',
            'cost'       => 'required|numeric|min:0',
            'odometer'   => 'nullable|integer|min:0',
            'logged_at'  => 'required|date',
        ]);

        FuelLog::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        session()->flash('status', $this->editingId ? 'Fuel log updated.' : 'Fuel log added.');
        $this->reset(['editingId']);
    }

    public function delete(int $id): void
    {
        FuelLog::findOrFail($id)->delete();
        session()->flash('status', 'Fuel log deleted.');
    }

    public function render()
    {
        $logs = FuelLog::query()
            ->with('vehicle')
            ->when($this->filterVehicleId, fn ($q) => $q->where('vehicle_id', $this->filterVehicleId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('logged_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('logged_at', '<=', $this->dateTo))
            ->latest('logged_at')
            ->paginate(10);

        return view('livewire.fuel-log-manager', [
            'logs'     => $logs,
            'vehicles' => Vehicle::orderBy('registration_number')->get(),
        ]);
    }
}
