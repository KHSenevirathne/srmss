<?php

namespace App\Livewire;

use App\Models\FuelLog;
use App\Models\Trip;
use App\Models\Vehicle;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;

/** Records fuel per vehicle, filterable by vehicle and date range. */
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

    // Optional link to a specific trip.
    #[Validate('nullable|exists:trips,id')]
    public string $trip_id = '';

    public function updatedFilterVehicleId(): void
    {
        $this->resetPage();
    }

    /** Trips are vehicle-specific : clear a stale trip link when the vehicle changes. */
    public function updatedVehicleId(): void
    {
        $this->trip_id = '';
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
        $this->reset(['editingId', 'vehicle_id', 'liters', 'cost', 'odometer', 'logged_at', 'trip_id']);
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
        $this->trip_id = $log->trip_id ? (string) $log->trip_id : '';
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
            'trip_id'    => 'nullable|exists:trips,id',
        ]);

        // Store an empty trip selection as null rather than an empty string.
        $data['trip_id'] = $data['trip_id'] ?: null;

        FuelLog::updateOrCreate(['id' => $this->editingId], $data);

        // Mileage only ever increases; a lower/backdated reading won't reduce it.
        if ($data['odometer'] !== null) {
            $vehicle = Vehicle::find($data['vehicle_id']);
            if ($vehicle && (int) $data['odometer'] > (int) $vehicle->mileage) {
                $vehicle->update(['mileage' => (int) $data['odometer']]);
            }
        }

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

        // Only the selected vehicle's trips, newest first.
        $trips = $this->vehicle_id
            ? Trip::query()
                ->with('schedule.route')
                ->whereHas('schedule', fn ($q) => $q->where('vehicle_id', $this->vehicle_id))
                ->latest('trip_date')
                ->limit(50)
                ->get()
            : collect();

        return view('livewire.fuel-log-manager', [
            'logs'            => $logs,
            'vehicles'        => Vehicle::orderBy('registration_number')->get(),
            'trips'           => $trips,
            'selectedVehicle' => $this->vehicle_id ? Vehicle::find($this->vehicle_id) : null,
        ]);
    }
}
