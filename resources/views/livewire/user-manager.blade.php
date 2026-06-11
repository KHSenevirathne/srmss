<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Users</h1>
            <p class="page-sub">Create accounts and assign roles — admin, supervisor or operator.</p>
        </div>
        <button wire:click="create" class="btn-primary">+ Add User</button>
    </div>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

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
                                <span class="badge {{ $user->roles->first()?->name === 'admin' ? 'badge-blue' : 'badge-zinc' }}">
                                    {{ $user->roles->first()?->name ?? '—' }}
                                </span>
                            </td>
                            <td class="td-actions">
                                <button wire:click="edit({{ $user->id }})" class="link-action">Edit</button>
                                @if ($user->id !== auth()->id())
                                    <button wire:click="delete({{ $user->id }})"
                                            wire:confirm="Delete this user?"
                                            class="link-danger">Delete</button>
                                @else
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">(you)</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No users found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($users->hasPages())<div class="table-foot">{{ $users->links() }}</div>@endif
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
                        @error('name') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label">Email</label>
                        <input type="email" wire:model="email" class="input">
                        @error('email') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Password {{ $editingId ? '(blank = keep current)' : '' }}</label>
                            <input type="password" wire:model="password" class="input">
                            @error('password') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Role</label>
                            <select wire:model="role" class="input">
                                @foreach ($roles as $r)
                                    <option value="{{ $r }}">{{ ucfirst($r) }}</option>
                                @endforeach
                            </select>
                            @error('role') <p class="error-text">{{ $message }}</p> @enderror
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
