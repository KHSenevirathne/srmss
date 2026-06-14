<div class="page">
    <div class="page-header">
        <div class="page-heading">
            <span class="icon-chip icon-chip-blue">
                <flux:icon.users />
            </span>
            <div>
                <h1 class="page-title">Users</h1>
                <p class="page-sub">Create accounts and assign roles : admin, supervisor or operator.</p>
            </div>
        </div>
        <button wire:click="create" class="btn-primary">+ Add User</button>
    </div>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    {{-- Quick legend: what each role is allowed to do. --}}
    <div class="card card-pad mb-4 text-sm">
        <p class="mb-2 flex items-center gap-1.5 font-medium text-zinc-700 dark:text-zinc-200">
            <flux:icon.information-circle class="size-4" /> What each role can do
        </p>
        <ul class="space-y-1.5 text-zinc-500 dark:text-zinc-400">
            <li><span class="badge badge-blue">Admin</span> Full access : including managing users and the activity log.</li>
            <li><span class="badge badge-zinc">Supervisor</span> Vehicles, drivers, routes, schedules, fuel &amp; maintenance, reports : everything except managing users.</li>
            <li><span class="badge badge-zinc">Operator</span> Log fuel &amp; maintenance, and view the dashboard &amp; reports only.</li>
        </ul>
    </div>

    <div class="filter-bar">
        <div class="w-full sm:w-64">
            <label class="label">Search</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Name or email…" class="input">
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr wire:key="user-{{ $user->id }}">
                            <td class="td-strong">{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span
                                    class="badge {{ $user->roles->first()?->name === 'admin' ? 'badge-blue' : 'badge-zinc' }}">
                                    {{ $user->roles->first()?->name ?? '-' }}
                                </span>
                            </td>
                            <td class="td-actions">
                                <button wire:click="edit({{ $user->id }})" class="link-action">Edit</button>
                                @if ($user->id !== auth()->id())
                                    <button wire:click="delete({{ $user->id }})" wire:confirm="Delete this user?"
                                        class="link-danger">Delete</button>
                                @else
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">(you)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="empty">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())
            <div class="table-foot">{{ $users->links() }}</div>
        @endif
    </div>

    {{-- Create / edit modal --}}
    @if ($showModal)
        <div class="modal-backdrop">
            <div class="modal-panel">
                <div class="modal-head">
                    <h2 class="modal-title">{{ $editingId ? 'Edit' : 'Add' }} User</h2>
                    <button wire:click="$set('showModal', false)" class="modal-close">✕</button>
                </div>
                <div class="modal-body">
                    <div>
                        <label class="label">Name</label>
                        <input type="text" wire:model="name" class="input">
                        @error('name')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input type="email" wire:model="email" class="input">
                        @error('email')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Password {{ $editingId ? '(blank = keep current)' : '' }}</label>
                            <div x-data="{ show: false }" class="relative">
                                <input :type="show ? 'text' : 'password'" wire:model="password"
                                    class="input" style="padding-right: 2.5rem">
                                <button type="button" tabindex="-1" @click="show = !show"
                                    :aria-label="show ? 'Hide password' : 'Show password'"
                                    class="absolute inset-y-0 right-0 flex items-center px-3 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                                    <flux:icon.eye x-show="!show" class="size-4" />
                                    <flux:icon.eye-slash x-show="show" style="display:none" class="size-4" />
                                </button>
                            </div>
                            @error('password')
                                <p class="error-text">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="label">Role</label>
                            <select wire:model="role" class="input">
                                @foreach ($roles as $r)
                                    <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                                @endforeach
                            </select>
                            @error('role')
                                <p class="error-text">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button wire:click="$set('showModal', false)" class="btn-ghost">Cancel</button>
                    <button wire:click="save" class="btn-primary">Save</button>
                </div>
            </div>
        </div>
    @endif
</div>
