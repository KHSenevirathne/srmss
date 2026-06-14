<div class="page">
    <div class="page-header">
        <div class="page-heading">
            <span class="icon-chip icon-chip-red">
                <flux:icon.beaker />
            </span>
            <div>
                <h1 class="page-title">Fuel Logs</h1>
                <p class="page-sub">Fuel consumption per vehicle, filterable by vehicle and date range.</p>
            </div>
        </div>
        <button wire:click="create" class="btn-primary">+ Add Fuel Log</button>
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
                        <th>Date</th>
                        <th>Vehicle</th>
                        <th class="th-num">Litres</th>
                        <th class="th-num">Cost</th>
                        <th class="th-num">Odometer</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr wire:key="fuel-{{ $log->id }}">
                            <td>{{ $log->logged_at->format('Y-m-d') }}</td>
                            <td class="td-strong">{{ $log->vehicle?->registration_number ?? '-' }}</td>
                            <td class="td-num">{{ number_format($log->liters, 2) }} L</td>
                            <td class="td-num">{{ number_format($log->cost, 2) }}</td>
                            <td class="td-num">
                                {{ $log->odometer !== null ? number_format($log->odometer) . ' km' : '-' }}</td>
                            <td class="td-actions">
                                <button wire:click="edit({{ $log->id }})" class="link-action">Edit</button>
                                <button wire:click="delete({{ $log->id }})" wire:confirm="Delete this fuel log?"
                                    class="link-danger">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">No fuel logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="table-foot">{{ $logs->links() }}</div>
        @endif
    </div>

    {{-- Create / edit modal --}}
    @if ($showModal)
        <div class="modal-backdrop">
            <div class="modal-panel">
                <div class="modal-head">
                    <h2 class="modal-title">{{ $editingId ? 'Edit' : 'Add' }} Fuel Log</h2>
                    <button wire:click="$set('showModal', false)" class="modal-close">✕</button>
                </div>
                <div class="modal-body">
                    <div>
                        <label class="label">Vehicle</label>
                        <select wire:model="vehicle_id" class="input">
                            <option value="">Select a vehicle…</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_id')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Litres</label>
                            <input type="number" step="0.01" wire:model="liters" class="input">
                            @error('liters')
                                <p class="error-text">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="label">Cost</label>
                            <input type="number" step="0.01" wire:model="cost" class="input">
                            @error('cost')
                                <p class="error-text">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Odometer (km)</label>
                            <input type="number" wire:model="odometer" class="input">
                            @error('odometer')
                                <p class="error-text">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <label class="label">Date</label>
                            <input type="date" wire:model="logged_at" class="input">
                            @error('logged_at')
                                <p class="error-text">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <div>
                        <label class="label">Trip <span
                                class="font-normal text-zinc-400 dark:text-zinc-500">(optional)</span></label>
                        <select wire:model="trip_id" class="input">
                            <option value="">: none :</option>
                            @foreach ($trips as $trip)
                                <option value="{{ $trip->id }}">
                                    {{ $trip->trip_date?->format('Y-m-d') }} ·
                                    {{ $trip->schedule?->route?->code ?? 'route' }}
                                </option>
                            @endforeach
                        </select>
                        @error('trip_id')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
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
