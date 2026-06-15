<?php

namespace App\Livewire;

use App\Models\Driver;
use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Drivers module : full CRUD, mirrors VehicleManager (the reference pattern).
 *
 * Same shape as the Vehicles screen: list + live search + pagination, a
 * modal-driven create/edit form, #[Validate] rules, and delete-with-confirm.
 * Guarded by the `manage-fleet` permission (drivers + vehicles share it), so
 * destructive buttons are wrapped in @can('manage-fleet') in the Blade view.
 *
 * A driver may optionally be given a login (the "needs login" toggle): that
 * provisions a linked User with the `driver` role, who logs in by employee
 * number. Drivers are therefore never created from the Users screen.
 */
class DriverManager extends Component
{
    use WithPagination;

    public string $search = '';

    public string $statusFilter = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    /** Login toggle + password for the optional linked account. */
    public bool $needsLogin = false;

    public string $password = '';

    /** Whether the driver being edited already has a login (drives password rules). */
    public bool $hasLogin = false;

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
            'license_number', 'license_expiry', 'weekly_hours', 'status', 'needsLogin', 'password', 'hasLogin',
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
        $this->hasLogin = $driver->user_id !== null;
        $this->needsLogin = $this->hasLogin;
        $this->password = '';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:120',
            'nic' => 'required|string|max:20|unique:drivers,nic,'.$this->editingId,
            'phone' => 'nullable|string|max:30',
            'email' => array_values(array_filter([
                'nullable', 'email', 'max:120',
                // A login account stores this email, which must be unique among users.
                $this->needsLogin ? Rule::unique('users', 'email')->ignore(Driver::find($this->editingId)?->user_id) : null,
            ])),
            'address' => 'nullable|string|max:255',
            'license_number' => 'required|string|max:30|unique:drivers,license_number,'.$this->editingId,
            'license_expiry' => 'required|date',
            'weekly_hours' => 'required|integer|min:0|max:168',
            'status' => 'required|in:active,inactive',
            'needsLogin' => 'boolean',
            // Password is required only when first enabling a login; on a driver
            // that already has one, blank means "keep the current password".
            'password' => [Rule::requiredIf($this->needsLogin && ! $this->hasLogin), 'nullable', 'string', 'min:8'],
        ]);

        $driver = Driver::findOrNew($this->editingId);
        $driver->fill(collect($data)->except(['needsLogin', 'password'])->all());
        $driver->save();

        $this->syncLoginAccount($driver, $data['password']);

        $this->showModal = false;
        session()->flash('status', $this->editingId ? 'Driver updated.' : 'Driver added.');
        $this->reset(['editingId']);
    }

    /**
     * Provision, update or remove the driver's linked login account to match the
     * "needs login" toggle. The account carries the `driver` role and logs in by
     * the driver's employee number.
     */
    private function syncLoginAccount(Driver $driver, string $password): void
    {
        if ($this->needsLogin) {
            $user = $driver->user ?: new User;
            $isNew = ! $user->exists;

            $user->name = $driver->name;
            $user->email = $driver->email ?: null;
            if ($password !== '') {
                $user->password = $password; // hashed by the model cast
            }
            if ($isNew) {
                $user->email_verified_at = now();
            }
            $user->save();
            $user->syncRoles(['driver']);

            $driver->user()->associate($user)->save();

            return;
        }

        // Login switched off : delete the account and unlink it.
        if ($driver->user) {
            $user = $driver->user;
            $driver->user()->disassociate()->save();
            $user->delete();
        }
    }

    public function delete(int $id): void
    {
        $driver = Driver::findOrFail($id);
        $driver->user?->delete(); // remove the linked login, if any
        $driver->delete();
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
