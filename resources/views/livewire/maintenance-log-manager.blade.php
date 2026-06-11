<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Maintenance Logs</h1>
            <p class="page-sub">Routine and corrective servicing, with overdue vehicles flagged.</p>
        </div>
        <button wire:click="create" class="btn-primary">+ Add Maintenance Log</button>
    </div>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <div class="filter-bar">
        <div class="w-full sm:w-52">
            <label class="label">Vehicle</label>
            <select wire:model.live="filterVehicleId" class="input">
                <option value="">All vehicles</option>
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-44">
            <label class="label">From</label>
            <input type="date" wire:model.live="dateFrom" class="input">
        </div>
        <div class="w-full sm:w-44">
            <label class="label">To</label>
            <input type="date" wire:model.live="dateTo" class="input">
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Serviced</th>
                        <th>Vehicle</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="th-num">Cost</th>
                        <th>Next Due</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr wire:key="maint-{{ $log->id }}">
                            <td>{{ $log->serviced_at->format('Y-m-d') }}</td>
                            <td class="td-strong">{{ $log->vehicle?->registration_number ?? '—' }}</td>
                            <td>
                                <span class="badge {{ $log->type === 'routine' ? 'badge-blue' : 'badge-amber' }}">{{ $log->type }}</span>
                            </td>
                            <td class="max-w-64 truncate" title="{{ $log->description }}">{{ $log->description }}</td>
                            <td class="td-num">{{ number_format($log->cost, 2) }}</td>
                            <td>
                                @if ($log->next_due_at)
                                    {{ $log->next_due_at->format('Y-m-d') }}
                                    @if ($log->serviceDue())
                                        <span class="badge badge-red ml-1">service due</span>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="td-actions">
                                <button wire:click="edit({{ $log->id }})" class="link-action">Edit</button>
                                <button wire:click="delete({{ $log->id }})"
                                        wire:confirm="Delete this maintenance log?"
                                        class="link-danger">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="empty">No maintenance logs found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())<div class="table-foot">{{ $logs->links() }}</div>@endif
    </div>

    {{-- Create / edit modal --}}
    @if ($showModal)
        <div class="modal-backdrop">
            <div class="modal-panel">
                <div class="modal-head">
                    <h2 class="modal-title">{{ $editingId ? 'Edit' : 'Add' }} Maintenance Log</h2>
                    <button wire:click="$set('showModal', false)" class="modal-close">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Vehicle</label>
                            <select wire:model="vehicle_id" class="input">
                                <option value="">Select a vehicle…</option>
                                @foreach ($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>
                                @endforeach
                            </select>
                            @error('vehicle_id') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Type</label>
                            <select wire:model="type" class="input">
                                <option value="routine">Routine</option>
                                <option value="corrective">Corrective</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="label">Description</label>
                        <input type="text" wire:model="description" class="input">
                        @error('description') <p class="error-text">{{ $message }}</p> @enderror
                    </div>
                    <div class="form-grid-3">
                        <div>
                            <label class="label">Cost</label>
                            <input type="number" step="0.01" wire:model="cost" class="input">
                            @error('cost') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Serviced</label>
                            <input type="date" wire:model="serviced_at" class="input">
                            @error('serviced_at') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Next Due</label>
                            <input type="date" wire:model="next_due_at" class="input">
                            @error('next_due_at') <p class="error-text">{{ $message }}</p> @enderror
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
