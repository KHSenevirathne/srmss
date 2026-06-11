<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Drivers</h1>
            <p class="page-sub">Driver records, licence validity and availability.</p>
        </div>
        @can('manage-fleet')
            <button wire:click="create" class="btn-primary">+ Add Driver</button>
        @endcan
    </div>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <div class="filter-bar">
        <div class="w-full sm:w-64">
            <label class="label">Search</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Name or licence no…" class="input">
        </div>
        <div class="w-full sm:w-44">
            <label class="label">Status</label>
            <select wire:model.live="statusFilter" class="input">
                <option value="">All statuses</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Licence No</th>
                        <th>Licence Expiry</th>
                        <th class="th-num">Weekly Hrs</th>
                        <th>Status</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($drivers as $driver)
                        <tr wire:key="driver-{{ $driver->id }}">
                            <td class="td-strong">{{ $driver->name }}</td>
                            <td>{{ $driver->phone ?: '—' }}</td>
                            <td>{{ $driver->license_number }}</td>
                            <td>
                                {{ $driver->license_expiry?->format('Y-m-d') }}
                                @if ($driver->licenseExpiringSoon())
                                    <span class="badge badge-amber ml-1">expiring soon</span>
                                @endif
                            </td>
                            <td class="td-num">{{ $driver->weekly_hours }}</td>
                            <td>
                                <span class="badge {{ $driver->status === 'active' ? 'badge-green' : 'badge-zinc' }}">{{ $driver->status }}</span>
                            </td>
                            <td class="td-actions">
                                @can('manage-fleet')
                                    <button wire:click="edit({{ $driver->id }})" class="link-action">Edit</button>
                                    <button wire:click="delete({{ $driver->id }})"
                                            wire:confirm="Delete this driver?"
                                            class="link-danger">Delete</button>
                                @else
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">View only</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No drivers yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($drivers->hasPages())<div class="table-foot">{{ $drivers->links() }}</div>@endif
    </div>

    {{-- Create / edit modal --}}
    @if ($showModal)
        <div class="modal-backdrop">
            <div class="modal-panel">
                <div class="modal-head">
                    <h2 class="modal-title">{{ $editingId ? 'Edit' : 'Add' }} Driver</h2>
                    <button wire:click="$set('showModal', false)" class="modal-close">✕</button>
                </div>
                <div class="modal-body">
                    <div>
                        <label class="label">Name</label>
                        <input type="text" wire:model="name" class="input">
                        @error('name') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Phone</label>
                            <input type="text" wire:model="phone" class="input">
                            @error('phone') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Email</label>
                            <input type="email" wire:model="email" class="input">
                            @error('email') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="label">Address</label>
                        <input type="text" wire:model="address" class="input">
                        @error('address') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Licence No</label>
                            <input type="text" wire:model="license_number" class="input">
                            @error('license_number') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Licence Expiry</label>
                            <input type="date" wire:model="license_expiry" class="input">
                            @error('license_expiry') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Weekly Hours</label>
                            <input type="number" wire:model="weekly_hours" class="input">
                            @error('weekly_hours') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Status</label>
                            <select wire:model="status" class="input">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
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
