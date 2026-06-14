<?php

namespace App\Livewire;

use App\Models\MaintenanceLog;
use App\Models\Vehicle;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;

/**
 * Maintenance logging (Phase 3) : routine/corrective records per vehicle, with a
 * "service due" flag when next_due_at has reached/passed today (see the
 * MaintenanceLog::serviceDue() helper). Filter by vehicle + service-date range.
 * Guarded by `log-fuel` at the route level (every role holds it).
 */
class MaintenanceLogManager extends Component
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

    #[Validate('required|in:routine,corrective')]
    public string $type = 'routine';

    #[Validate('required|string|max:255')]
    public string $description = '';

    #[Validate('required|numeric|min:0')]
    public float $cost = 0;

    #[Validate('required|date')]
    public string $serviced_at = '';

    #[Validate('nullable|date')]
    public string $next_due_at = '';

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
        $this->reset(['editingId', 'vehicle_id', 'type', 'description', 'cost', 'serviced_at', 'next_due_at']);
        $this->type = 'routine';
        $this->serviced_at = now()->toDateString();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $log = MaintenanceLog::findOrFail($id);
        $this->editingId = $log->id;
        $this->vehicle_id = (string) $log->vehicle_id;
        $this->type = $log->type;
        $this->description = $log->description;
        $this->cost = (float) $log->cost;
        $this->serviced_at = $log->serviced_at->toDateString();
        $this->next_due_at = $log->next_due_at?->toDateString() ?? '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'vehicle_id'  => 'required|exists:vehicles,id',
            'type'        => 'required|in:routine,corrective',
            'description' => 'required|string|max:255',
            'cost'        => 'required|numeric|min:0',
            'serviced_at' => 'required|date',
            'next_due_at' => 'nullable|date|after_or_equal:serviced_at',
        ]);

        // Store an empty next-due as null rather than an empty string.
        $data['next_due_at'] = $data['next_due_at'] ?: null;

        MaintenanceLog::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        session()->flash('status', $this->editingId ? 'Maintenance log updated.' : 'Maintenance log added.');
        $this->reset(['editingId']);
    }

    public function delete(int $id): void
    {
        MaintenanceLog::findOrFail($id)->delete();
        session()->flash('status', 'Maintenance log deleted.');
    }

    public function render()
    {
        $logs = MaintenanceLog::query()
            ->with('vehicle')
            ->when($this->filterVehicleId, fn ($q) => $q->where('vehicle_id', $this->filterVehicleId))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('serviced_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('serviced_at', '<=', $this->dateTo))
            ->latest('serviced_at')
            ->paginate(10);

        return view('livewire.maintenance-log-manager', [
            'logs'     => $logs,
            'vehicles' => Vehicle::orderBy('registration_number')->get(),
        ]);
    }
}
