<div class="page">
    <div class="page-header">
        <div class="page-heading">
            <span class="icon-chip icon-chip-amber"><flux:icon.list-bullet /></span>
            <div>
                <h1 class="page-title">Trips</h1>
                <p class="page-sub">Every scheduled run across the depot - filter and track live status.</p>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <div class="filter-bar">
        <div class="w-full sm:w-40">
            <label class="label">Route</label>
            <select wire:model.live="filterRouteId" class="input">
                <option value="">All routes</option>
                @foreach ($routes as $route)
                    <option value="{{ $route->id }}">{{ $route->code }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-40">
            <label class="label">Vehicle</label>
            <select wire:model.live="filterVehicleId" class="input">
                <option value="">All vehicles</option>
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-40">
            <label class="label">Driver</label>
            <select wire:model.live="filterDriverId" class="input">
                <option value="">All drivers</option>
                @foreach ($drivers as $driver)
                    <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-40">
            <label class="label">Status</label>
            <select wire:model.live="filterStatus" class="input">
                <option value="">All statuses</option>
                <option value="scheduled">Scheduled</option>
                <option value="on_time">On time</option>
                <option value="delayed">Delayed</option>
                <option value="completed">Completed</option>
            </select>
        </div>
        <div class="w-full sm:w-40">
            <label class="label">From</label>
            <input type="date" wire:model.live="dateFrom" class="input">
        </div>
        <div class="w-full sm:w-40">
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
                        <th>Route</th>
                        <th>Vehicle</th>
                        <th>Driver</th>
                        <th>Window</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($trips as $trip)
                        <tr wire:key="trip-{{ $trip->id }}">
                            <td class="td-strong tabular-nums">{{ $trip->trip_date->format('Y-m-d') }}</td>
                            <td>{{ $trip->schedule?->route?->code ?? '-' }}</td>
                            <td>{{ $trip->schedule?->vehicle?->registration_number ?? '-' }}</td>
                            <td>{{ $trip->schedule?->driver?->name ?? '-' }}</td>
                            <td class="tabular-nums">
                                {{ $trip->schedule ? substr($trip->schedule->departure_time, 0, 5) . '–' . substr($trip->schedule->arrival_time, 0, 5) : '-' }}
                            </td>
                            <td>
                                @php
                                    $badge = match ($trip->status) {
                                        'on_time' => 'badge-green',
                                        'delayed' => 'badge-red',
                                        'completed' => 'badge-zinc',
                                        default => 'badge-blue',
                                    };
                                @endphp
                                @if ($canManage)
                                    <select wire:change="updateTripStatus({{ $trip->id }}, $event.target.value)"
                                        class="input w-36">
                                        <option value="scheduled" @selected($trip->status === 'scheduled')>Scheduled</option>
                                        <option value="on_time" @selected($trip->status === 'on_time')>On time</option>
                                        <option value="delayed" @selected($trip->status === 'delayed')>Delayed</option>
                                        <option value="completed" @selected($trip->status === 'completed')>Completed</option>
                                    </select>
                                @else
                                    <span class="badge {{ $badge }}">{{ str_replace('_', ' ', $trip->status) }}</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="empty">No trips match these filters.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($trips->hasPages())
            <div class="table-foot">{{ $trips->links() }}</div>
        @endif
    </div>
</div>
