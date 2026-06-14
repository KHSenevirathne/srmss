<div class="page">
    <div class="page-header">
        <div class="page-heading">
            <span class="icon-chip icon-chip-amber"><flux:icon.calendar-days /></span>
            <div>
                <h1 class="page-title">Schedules</h1>
                <p class="page-sub">Timetables with automatic vehicle/driver conflict detection and trip generation.</p>
            </div>
        </div>
        @can('manage-schedules')
            <button wire:click="create" class="btn-primary">+ Add Schedule</button>
        @endcan
    </div>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <div class="filter-bar">
        <div class="w-full sm:w-44">
            <label class="label">Route</label>
            <select wire:model.live="filterRouteId" class="input">
                <option value="">All routes</option>
                @foreach ($routes as $route)
                    <option value="{{ $route->id }}">{{ $route->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-44">
            <label class="label">Status</label>
            <select wire:model.live="filterStatus" class="input">
                <option value="">All statuses</option>
                <option value="scheduled">Scheduled</option>
                <option value="active">Active</option>
                <option value="cancelled">Cancelled</option>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Route</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Frequency</th>
                        <th>Window</th>
                        <th>Validity</th>
                        <th class="th-num">Trips</th>
                        <th>Status</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($schedules as $schedule)
                        <tr wire:key="schedule-{{ $schedule->id }}">
                            <td class="td-strong">{{ $schedule->route?->code ?? '-' }}</td>
                            <td>{{ $schedule->vehicle?->registration_number ?? '-' }}</td>
                            <td>{{ $schedule->driver?->name ?? '-' }}</td>
                            <td class="capitalize">{{ $schedule->frequency }}</td>
                            <td class="tabular-nums">
                                {{ substr($schedule->departure_time, 0, 5) }}–{{ substr($schedule->arrival_time, 0, 5) }}
                            </td>
                            <td class="tabular-nums">{{ $schedule->valid_from->format('Y-m-d') }} →
                                {{ $schedule->valid_to?->format('Y-m-d') ?? '∞' }}</td>
                            <td class="td-num">{{ $schedule->trips_count }}</td>
                            <td>
                                @php
                                    $badge = match ($schedule->status) {
                                        'active' => 'badge-green',
                                        'cancelled' => 'badge-red',
                                        default => 'badge-blue',
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">{{ $schedule->status }}</span>
                            </td>
                            <td class="td-actions">
                                @can('manage-schedules')
                                    <button wire:click="viewTrips({{ $schedule->id }})" class="link-muted">Trips</button>
                                    <button wire:click="edit({{ $schedule->id }})" class="link-action">Edit</button>
                                    @if ($schedule->status !== 'cancelled')
                                        <button wire:click="cancel({{ $schedule->id }})"
                                            wire:confirm="Cancel this schedule?" class="link-warn">Cancel</button>
                                    @endif
                                    <button wire:click="delete({{ $schedule->id }})"
                                        wire:confirm="Delete this schedule and its trips?"
                                        class="link-danger">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="empty">No schedules yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($schedules->hasPages())
            <div class="table-foot">{{ $schedules->links() }}</div>
        @endif
    </div>

    {{-- Create / edit modal --}}
    @if ($showModal)
        <div class="modal-backdrop">
            <div class="modal-panel modal-lg">
                <div class="modal-head">
                    <h2 class="modal-title">{{ $editingId ? 'Edit' : 'Add' }} Schedule</h2>
                    <button wire:click="$set('showModal', false)" class="modal-close">✕</button>
                </div>
                <div class="modal-body">
                    @error('conflict')
                        <div class="callout-danger">{{ $message }}</div>
                    @enderror

                    <div>
                        <label class="label">Route</label>
                        <select wire:model="bus_route_id" class="input">
                            <option value="">Select a route…</option>
                            @foreach ($routes as $route)
                                <option value="{{ $route->id }}">{{ $route->code }} : {{ $route->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('bus_route_id')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Vehicle</label>
                            <select wire:model="vehicle_id" class="input">
                                <option value="">Select…</option>
                                @foreach ($vehicles as $vehicle)
                                    <option value="{{ $vehicle->id }}" @disabled($vehicle->status !== 'available')>
                                        {{ $vehicle->registration_number }}@unless ($vehicle->status === 'available')
                                        · {{ str_replace('_', ' ', $vehicle->status) }}
                                    @endunless
                                </option>
                            @endforeach
                        </select>
                        @error('vehicle_id')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Driver</label>
                        <select wire:model="driver_id" class="input">
                            <option value="">Select…</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->name }}@if ($driver->employee_number)
                                        ({{ $driver->employee_number }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                        @error('driver_id')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="form-grid-3">
                    <div>
                        <label class="label">Frequency</label>
                        <select wire:model="frequency" class="input">
                            <option value="daily">Daily</option>
                            <option value="weekly">Weekly</option>
                            <option value="monthly">Monthly</option>
                        </select>
                    </div>
                    <div>
                        <label class="label">Departs</label>
                        <input type="time" wire:model="departure_time" class="input">
                        @error('departure_time')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Arrives</label>
                        <input type="time" wire:model="arrival_time" class="input">
                        @error('arrival_time')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
                <div class="form-grid-3">
                    <div>
                        <label class="label">Valid from</label>
                        <input type="date" wire:model="valid_from" class="input">
                        @error('valid_from')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Valid to <span
                                class="font-normal text-zinc-400 dark:text-zinc-500">(optional)</span></label>
                        <input type="date" wire:model="valid_to" class="input">
                        @error('valid_to')
                            <p class="error-text">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="label">Status</label>
                        <select wire:model="status" class="input">
                            <option value="scheduled">Scheduled</option>
                            <option value="active">Active</option>
                            <option value="cancelled">Cancelled</option>
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

{{-- Trips panel : generated trips for one schedule, with live status updates --}}
@if ($tripsSchedule)
    <div class="modal-backdrop">
        <div class="modal-panel modal-lg">
            <div class="modal-head">
                <h2 class="modal-title">Trips : {{ $tripsSchedule->route?->code }} ·
                    {{ substr($tripsSchedule->departure_time, 0, 5) }}–{{ substr($tripsSchedule->arrival_time, 0, 5) }}
                </h2>
                <button wire:click="closeTrips" class="modal-close">✕</button>
            </div>
            <div class="modal-body">
                @if (session('status'))
                    <div class="flash">{{ session('status') }}</div>
                @endif

                @forelse ($tripsSchedule->trips as $trip)
                    <div wire:key="trip-{{ $trip->id }}"
                        class="flex items-center justify-between gap-3 rounded-lg border border-zinc-100 px-4 py-2.5 dark:border-zinc-800">
                        <span class="flex items-center gap-3 text-sm">
                            <span
                                class="tabular-nums text-zinc-700 dark:text-zinc-200">{{ $trip->trip_date->format('Y-m-d') }}</span>
                            @php
                                $badge = match ($trip->status) {
                                    'on_time' => 'badge-green',
                                    'delayed' => 'badge-red',
                                    'completed' => 'badge-zinc',
                                    default => 'badge-blue',
                                };
                            @endphp
                            <span
                                class="badge {{ $badge }}">{{ str_replace('_', ' ', $trip->status) }}</span>
                        </span>
                        <select wire:change="updateTripStatus({{ $trip->id }}, $event.target.value)"
                            class="input w-40">
                            <option value="scheduled" @selected($trip->status === 'scheduled')>Scheduled</option>
                            <option value="on_time" @selected($trip->status === 'on_time')>On time</option>
                            <option value="delayed" @selected($trip->status === 'delayed')>Delayed</option>
                            <option value="completed" @selected($trip->status === 'completed')>Completed</option>
                        </select>
                    </div>
                @empty
                    <div class="callout-muted">No trips generated yet : use the button below to create them from
                        this schedule's validity window.</div>
                @endforelse
            </div>
            <div class="modal-foot justify-between">
                <button wire:click="generateTrips({{ $tripsSchedule->id }})" class="btn-ghost">Generate
                    trips</button>
                <button wire:click="closeTrips" class="btn-primary">Done</button>
            </div>
        </div>
    </div>
@endif
</div>
