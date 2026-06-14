<?php

namespace App\Livewire;

use App\Models\Driver;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;

/**
 * Drivers module : full CRUD, mirrors VehicleManager (the reference pattern).
 *
 * Same shape as the Vehicles screen: list + live search + pagination, a
 * modal-driven create/edit form, #[Validate] rules, and delete-with-confirm.
 * Guarded by the `manage-fleet` permission (drivers + vehicles share it), so
 * destructive buttons are wrapped in @can('manage-fleet') in the Blade view.
 */
class DriverManager extends Component
{
    use WithPagination;

    public string $search = '';
    public string $statusFilter = '';
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|string|max:20')]
    public string $nic = '';

    /** Read-only: auto-generated on save, shown (not editable) when editing. */
    public ?string $employeeNumber = null;

    #[Validate('nullable|string|max:30')]
    public string $phone = '';

    #[Validate('nullable|email|max:120')]
    public string $email = '';

    #[Validate('nullable|string|max:255')]
    public string $address = '';

    #[Validate('required|string|max:30')]
    public string $license_number = '';

    #[Validate('required|date')]
    public string $license_expiry = '';

    #[Validate('required|integer|min:0|max:168')]
    public int $weekly_hours = 40;

    #[Validate('required|in:active,inactive')]
    public string $status = 'active';

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
        $this->reset([
            'editingId', 'name', 'nic', 'employeeNumber', 'phone', 'email', 'address',
            'license_number', 'license_expiry', 'weekly_hours', 'status',
        ]);
        $this->weekly_hours = 40;
        $this->status = 'active';
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $driver = Driver::findOrFail($id);
        $this->editingId = $driver->id;
        $this->name = $driver->name;
        $this->nic = $driver->nic ?? '';
        $this->employeeNumber = $driver->employee_number;
        $this->phone = $driver->phone ?? '';
        $this->email = $driver->email ?? '';
        $this->address = $driver->address ?? '';
        $this->license_number = $driver->license_number;
        $this->license_expiry = $driver->license_expiry?->toDateString() ?? '';
        $this->weekly_hours = $driver->weekly_hours;
        $this->status = $driver->status;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name'           => 'required|string|max:120',
            'nic'            => 'required|string|max:20|unique:drivers,nic,' . $this->editingId,
            'phone'          => 'nullable|string|max:30',
            'email'          => 'nullable|email|max:120',
            'address'        => 'nullable|string|max:255',
            'license_number' => 'required|string|max:30|unique:drivers,license_number,' . $this->editingId,
            'license_expiry' => 'required|date',
            'weekly_hours'   => 'required|integer|min:0|max:168',
            'status'         => 'required|in:active,inactive',
        ]);

        Driver::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        session()->flash('status', $this->editingId ? 'Driver updated.' : 'Driver added.');
        $this->reset(['editingId']);
    }

    public function delete(int $id): void
    {
        Driver::findOrFail($id)->delete();
        session()->flash('status', 'Driver deleted.');
    }

    public function render()
    {
        $drivers = Driver::query()
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('license_number', 'like', "%{$this->search}%"))
            ->when($this->statusFilter, fn ($q) => $q->where('status', $this->statusFilter))
            ->latest()
            ->paginate(10);

        return view('livewire.driver-manager', compact('drivers'));
    }
}
