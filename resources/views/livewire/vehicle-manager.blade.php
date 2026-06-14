<div class="page">
    <div class="page-header">
        <div class="page-heading">
            <span class="icon-chip icon-chip-green"><flux:icon.truck /></span>
            <div>
                <h1 class="page-title">Vehicles</h1>
                <p class="page-sub">The depot fleet — search, filter by status, and manage records.</p>
            </div>
        </div>
        @can('manage-fleet')
            <button wire:click="create" class="btn-primary">+ Add Vehicle</button>
        @endcan
    </div>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <div class="filter-bar">
        <div class="w-full sm:w-64">
            <label class="label">Search</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Reg no or type…" class="input">
        </div>
        <div class="w-full sm:w-44">
            <label class="label">Status</label>
            <select wire:model.live="statusFilter" class="input">
                <option value="">All statuses</option>
                <option value="available">Available</option>
                <option value="in_service">In service</option>
                <option value="maintenance">Maintenance</option>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Reg No</th>
                        <th>Type</th>
                        <th class="th-num">Capacity</th>
                        <th class="th-num">Mileage</th>
                        <th>Status</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($vehicles as $vehicle)
                        <tr wire:key="vehicle-{{ $vehicle->id }}">
                            <td class="td-strong">{{ $vehicle->registration_number }}</td>
                            <td class="capitalize">{{ $vehicle->type }}</td>
                            <td class="td-num">{{ $vehicle->seating_capacity }}</td>
                            <td class="td-num">{{ number_format($vehicle->mileage) }} km</td>
                            <td>
                                @php
                                    $badge = match ($vehicle->status) {
                                        'available' => 'badge-green',
                                        'in_service' => 'badge-blue',
                                        default => 'badge-amber',
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">{{ str_replace('_', ' ', $vehicle->status) }}</span>
                            </td>
                            <td class="td-actions">
                                @can('manage-fleet')
                                    <button wire:click="edit({{ $vehicle->id }})" class="link-action">Edit</button>
                                    <button wire:click="delete({{ $vehicle->id }})"
                                            wire:confirm="Delete this vehicle?"
                                            class="link-danger">Delete</button>
                                @else
                                    <span class="text-xs text-zinc-400 dark:text-zinc-500">View only</span>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="empty">No vehicles yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($vehicles->hasPages())<div class="table-foot">{{ $vehicles->links() }}</div>@endif
    </div>

    {{-- Create / edit modal --}}
    @if ($showModal)
        <div class="modal-backdrop">
            <div class="modal-panel">
                <div class="modal-head">
                    <h2 class="modal-title">{{ $editingId ? 'Edit' : 'Add' }} Vehicle</h2>
                    <button wire:click="$set('showModal', false)" class="modal-close">✕</button>
                </div>
                <div class="modal-body">
                    <div>
                        <label class="label">Registration No</label>
                        <input type="text" wire:model="registration_number" class="input">
                        @error('registration_number') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Brand <span class="text-zinc-400">(optional)</span></label>
                            <input type="text" wire:model="brand" placeholder="e.g. Tata" class="input">
                            @error('brand') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Model <span class="text-zinc-400">(optional)</span></label>
                            <input type="text" wire:model="model" placeholder="e.g. Starbus" class="input">
                            @error('model') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Type</label>
                            <select wire:model="type" class="input">
                                <option value="bus">Bus</option>
                                <option value="minibus">Minibus</option>
                                <option value="coach">Coach</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Fuel Type</label>
                            <select wire:model="fuel_type" class="input">
                                <option value="diesel">Diesel</option>
                                <option value="petrol">Petrol</option>
                                <option value="electric">Electric</option>
                                <option value="hybrid">Hybrid</option>
                                <option value="cng">CNG</option>
                            </select>
                            @error('fuel_type') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Seating Capacity</label>
                            <input type="number" wire:model="seating_capacity" class="input">
                            @error('seating_capacity') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Mileage (km)</label>
                            <input type="number" wire:model="mileage" class="input">
                            @error('mileage') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select wire:model="status" class="input">
                            <option value="available">Available</option>
                            <option value="in_service">In service</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
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
