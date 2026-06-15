<div class="page">
    <div class="page-header">
        <div class="page-heading">
            <span class="icon-chip icon-chip-amber"><flux:icon.list-bullet /></span>
            <div>
                <h1 class="page-title">{{ $isDriver ? 'My Trips' : 'Trips' }}</h1>
                <p class="page-sub">
                    {{ $isDriver
                        ? 'Your assigned runs - request a status change for a staff member to approve.'
                        : 'Every scheduled run across the depot - filter and track live status.' }}
                </p>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <div class="filter-bar">
        @unless ($isDriver)
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
        @endunless
        <div class="w-full sm:w-40">
            <label class="label">Status</label>
            <select wire:model.live="filterStatus" class="input">
                <option value="">All statuses</option>
                <option value="scheduled">Scheduled</option>
                <option value="on_time">On time</option>
                <option value="delayed">Delayed</option>
                <option value="completed">Completed</option>
                <option value="cancelled">Cancelled</option>
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
                        @unless ($isDriver)
                            <th>Driver</th>
                        @endunless
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
                            @unless ($isDriver)
                                <td>{{ $trip->schedule?->driver?->name ?? '-' }}</td>
                            @endunless
                            <td class="tabular-nums">
                                {{ $trip->schedule ? substr($trip->schedule->departure_time, 0, 5) . '–' . substr($trip->schedule->arrival_time, 0, 5) : '-' }}
                            </td>
                            <td>
                                @php
                                    $badge = match ($trip->status) {
                                        'on_time' => 'badge-green',
                                        'delayed' => 'badge-red',
                                        'cancelled' => 'badge-red',
                                        'completed' => 'badge-zinc',
                                        default => 'badge-blue',
                                    };
                                @endphp

                                <div class="flex flex-col items-start gap-1.5">
                                    {{-- Current status control --}}
                                    @if ($canManage)
                                        <select wire:change="updateTripStatus({{ $trip->id }}, $event.target.value)"
                                            class="input w-40">
                                            <option value="scheduled" @selected($trip->status === 'scheduled')>Scheduled</option>
                                            <option value="on_time" @selected($trip->status === 'on_time')>On time</option>
                                            <option value="delayed" @selected($trip->status === 'delayed')>Delayed</option>
                                            <option value="completed" @selected($trip->status === 'completed')>Completed</option>
                                            <option value="cancelled" @selected($trip->status === 'cancelled')>Cancelled</option>
                                        </select>
                                    @elseif ($canRequest)
                                        <span class="badge {{ $badge }}">{{ str_replace('_', ' ', $trip->status) }}</span>
                                        @unless ($trip->hasPendingStatus())
                                            <select wire:change="requestTripStatus({{ $trip->id }}, $event.target.value)"
                                                class="input w-40">
                                                <option value="">Request change…</option>
                                                <option value="on_time">On time</option>
                                                <option value="delayed">Delayed</option>
                                                <option value="completed">Completed</option>
                                            </select>
                                        @endunless
                                    @else
                                        <span class="badge {{ $badge }}">{{ str_replace('_', ' ', $trip->status) }}</span>
                                    @endif

                                    {{-- Pending request : approvers act on it, the driver just waits --}}
                                    @if ($trip->hasPendingStatus())
                                        @if ($canApprove)
                                            <div class="flex flex-col gap-1 rounded-lg border border-amber-200 bg-amber-50 px-2.5 py-2 text-xs dark:border-amber-900 dark:bg-amber-900/30">
                                                <span class="text-amber-700 dark:text-amber-300">
                                                    Requested: <strong class="capitalize">{{ str_replace('_', ' ', $trip->pending_status) }}</strong>
                                                    @if ($trip->statusRequester)
                                                        by {{ $trip->statusRequester->name }}
                                                    @endif
                                                </span>
                                                <div class="flex gap-3">
                                                    <button type="button" class="link-action" wire:click="approveStatus({{ $trip->id }})">Approve</button>
                                                    <button type="button" class="link-danger" wire:click="rejectStatus({{ $trip->id }})">Reject</button>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-xs text-amber-600 dark:text-amber-400">
                                                Requested <strong class="capitalize">{{ str_replace('_', ' ', $trip->pending_status) }}</strong> - awaiting approval
                                            </span>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isDriver ? 5 : 6 }}" class="empty">No trips match these filters.</td>
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
