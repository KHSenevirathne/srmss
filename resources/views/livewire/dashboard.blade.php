<div class="page" wire:poll.15s>
    <div class="page-header">
        <div>
            <h1 class="page-title">Depot Dashboard</h1>
            <p class="page-sub">Live operational overview · {{ now()->format('D, d M Y H:i') }}</p>
        </div>
        <span class="flex items-center gap-1.5 text-xs font-medium text-zinc-400 dark:text-zinc-500">
            <span class="inline-block size-2 animate-pulse rounded-full bg-green-500"></span> auto-refreshing
        </span>
    </div>

    {{-- Summary cards --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $summary = [
                ['label' => 'Total routes', 'value' => $cards['routes'], 'color' => 'text-indigo-600 dark:text-indigo-400'],
                ['label' => 'Active trips today', 'value' => $cards['activeTripsToday'], 'color' => 'text-blue-600 dark:text-blue-400'],
                ['label' => 'Available buses', 'value' => $cards['availableBuses'], 'color' => 'text-green-600 dark:text-green-400'],
                ['label' => 'Assigned drivers', 'value' => $cards['assignedDrivers'], 'color' => 'text-amber-600 dark:text-amber-400'],
            ];
        @endphp
        @foreach ($summary as $card)
            <div class="stat">
                <div class="stat-label">{{ $card['label'] }}</div>
                <div class="stat-value {{ $card['color'] }}">{{ $card['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Live trip-status board --}}
        <div class="card card-pad lg:col-span-2">
            <h2 class="card-title">Today's trips</h2>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                @php
                    $board = [
                        ['key' => 'on_time', 'label' => 'On time', 'class' => 'bg-green-50 text-green-700 dark:bg-green-900/30 dark:text-green-300'],
                        ['key' => 'delayed', 'label' => 'Delayed', 'class' => 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300'],
                        ['key' => 'completed', 'label' => 'Completed', 'class' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300'],
                        ['key' => 'scheduled', 'label' => 'Scheduled', 'class' => 'bg-blue-50 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300'],
                    ];
                @endphp
                @foreach ($board as $b)
                    <div class="tile {{ $b['class'] }}">
                        <div class="tile-value">{{ $tripCounts[$b['key']] ?? 0 }}</div>
                        <div class="tile-label">{{ $b['label'] }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Fleet utilisation --}}
        <div class="card card-pad">
            <h2 class="card-title">Fleet utilisation</h2>
            <div class="space-y-3">
                @forelse ($fleet as $status => $count)
                    <div>
                        <div class="mb-1 flex justify-between text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="capitalize">{{ str_replace('_', ' ', $status) }}</span>
                            <span class="tabular-nums">{{ $count }} / {{ $fleetTotal }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-2 rounded-full bg-indigo-500" style="width: {{ $fleetTotal ? round($count / $fleetTotal * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">No vehicles yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Today's schedule overview --}}
        <div class="card card-pad">
            <h2 class="card-title">Today's schedule</h2>
            <div class="space-y-2">
                @forelse ($todaySchedules as $schedule)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-zinc-100 px-3 py-2 text-sm dark:border-zinc-800">
                        <div class="min-w-0 truncate">
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $schedule->route?->code }}</span>
                            <span class="text-zinc-500 dark:text-zinc-400">· {{ $schedule->vehicle?->registration_number }} · {{ $schedule->driver?->name }}</span>
                        </div>
                        <span class="shrink-0 tabular-nums text-zinc-500 dark:text-zinc-400">{{ substr($schedule->departure_time, 0, 5) }}–{{ substr($schedule->arrival_time, 0, 5) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">No schedules running today.</p>
                @endforelse
            </div>
        </div>

        {{-- Alerts --}}
        <div class="card card-pad">
            <h2 class="card-title">Alerts</h2>

            <h3 class="section-label">Licences expiring soon</h3>
            <div class="mt-2 mb-5 space-y-1.5">
                @forelse ($expiringLicences as $driver)
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:bg-amber-900/30 dark:text-amber-200">
                        <span class="min-w-0 truncate">{{ $driver->name }} ({{ $driver->license_number }})</span>
                        <span class="shrink-0 tabular-nums">{{ $driver->license_expiry->format('Y-m-d') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">No licences expiring in the next 30 days.</p>
                @endforelse
            </div>

            <h3 class="section-label">Vehicles — service due</h3>
            <div class="mt-2 space-y-1.5">
                @forelse ($serviceDueVehicles as $vehicle)
                    <div class="flex items-center justify-between gap-3 rounded-lg bg-red-50 px-3 py-2 text-sm text-red-800 dark:bg-red-900/30 dark:text-red-200">
                        <span>{{ $vehicle->registration_number }}</span>
                        <span class="shrink-0 tabular-nums">due {{ $vehicle->latestMaintenance?->next_due_at?->format('Y-m-d') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">No vehicles are overdue for service.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
