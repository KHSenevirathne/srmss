<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

/**
 * User management (Phase 1) : admin-only screen to create users and assign a
 * role. Follows the VehicleManager CRUD pattern. Guarded by `manage-users`,
 * which only the admin role holds, so the route 403s for everyone else.
 *
 * Each user is given exactly one role (admin | supervisor | operator), matching
 * how DemoDataSeeder provisions the demo logins.
 */
class UserManager extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'operator';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    /**
     * Assignable role names from the RBAC seeder (single source of truth).
     * The `driver` role is excluded : driver logins are provisioned from the
     * Drivers screen, never created or reassigned here.
     */
    public function rolesList(): array
    {
        return Role::where('name', '!=', 'driver')->orderBy('name')->pluck('name')->all();
    }

    public function create(): void
    {
        $this->reset(['editingId', 'name', 'email', 'password', 'role']);
        $this->role = 'operator';
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $user = User::findOrFail($id);
        $this->editingId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->roles->first()?->name ?? 'operator';
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'name' => 'required|string|max:120',
            'email' => ['required', 'email', 'max:120', Rule::unique('users', 'email')->ignore($this->editingId)],
            // Password is required when creating, optional (leave blank to keep) when editing.
            'password' => [$this->editingId ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in($this->rolesList())],
        ]);

        $user = User::findOrNew($this->editingId);
        $user->name = $data['name'];
        $user->email = $data['email'];
        if (! empty($data['password'])) {
            $user->password = $data['password']; // hashed by the model cast
        }
        if (! $user->exists) {
            $user->email_verified_at = now();
        }
        $user->save();

        $user->syncRoles([$data['role']]);

        $this->showModal = false;
        session()->flash('status', $this->editingId ? 'User updated.' : 'User created.');
        $this->reset(['editingId']);
    }

    public function delete(int $id): void
    {
        if ($id === auth()->id()) {
            session()->flash('status', 'You cannot delete your own account.');

            return;
        }

        User::findOrFail($id)->delete();
        session()->flash('status', 'User deleted.');
    }

    public function render()
    {
        $users = User::query()
            ->with('roles')
            // Drivers are managed on the Drivers screen, not here.
            ->whereDoesntHave('roles', fn ($q) => $q->where('name', 'driver'))
            ->when($this->search, fn ($q) => $q
                ->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        return view('livewire.user-manager', [
            'users' => $users,
            'roles' => $this->rolesList(),
        ]);
    }
}
