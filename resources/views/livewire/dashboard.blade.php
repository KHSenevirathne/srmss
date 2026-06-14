<div class="page">
    <div class="page-header">
        <div class="page-heading">
            <span class="glyph">
                <flux:icon.home />
            </span>
            <div>
                <h1 class="page-title">Depot Operations</h1>
                <p class="page-sub flex items-center gap-2">
                    <span class="font-mono tabular-nums">{{ now()->format('D, d M Y · H:i') }}</span>
                </p>
            </div>
        </div>
        <button wire:click="$refresh" wire:loading.attr="disabled" class="btn-ghost flex items-center gap-1.5">
            <flux:icon.arrow-path class="size-4" wire:loading.class="animate-spin" wire:target="$refresh" />
            Refresh
        </button>
    </div>

    {{-- Summary KPIs --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $summary = [
                ['label' => 'Total Routes', 'value' => $cards['routes'], 'icon' => 'map', 'pill' => 'pill-green', 'tag' => 'Operational'],
                ['label' => 'Active Trips Today', 'value' => $cards['activeTripsToday'], 'icon' => 'play-circle', 'pill' => 'pill-blue', 'tag' => 'Running'],
                ['label' => 'Available Vehicles', 'value' => $cards['availableBuses'], 'icon' => 'truck', 'pill' => 'pill-green', 'tag' => 'Ready'],
                ['label' => 'Assigned Drivers', 'value' => $cards['assignedDrivers'], 'icon' => 'identification', 'pill' => 'pill-blue', 'tag' => 'On Duty'],
            ];
        @endphp
        @foreach ($summary as $card)
            <div class="metric">
                <div class="flex items-start justify-between">
                    <span class="glyph">
                        <flux:icon :name="$card['icon']" />
                    </span>
                    <span class="pill {{ $card['pill'] }}">{{ $card['tag'] }}</span>
                </div>
                <div class="metric-label mt-3">{{ $card['label'] }}</div>
                <div class="metric-value">{{ $card['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Vehicle highlights : busiest/idlest by trips, and odometer extremes --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        @php
            $tripSub = fn($v) => $v ? $v['trips'] . ' ' . \Illuminate\Support\Str::plural('trip', $v['trips']) : null;
            $mileSub = fn($v) => $v ? number_format($v['mileage']) . ' km' : null;
            $vehicleStats = [
                ['label' => 'Most Used Vehicle', 'reg' => $mostUsedVehicle['registration_number'] ?? null, 'sub' => $tripSub($mostUsedVehicle), 'icon' => 'arrow-trending-up'],
                ['label' => 'Least Used Vehicle', 'reg' => $leastUsedVehicle['registration_number'] ?? null, 'sub' => $tripSub($leastUsedVehicle), 'icon' => 'arrow-trending-down'],
                ['label' => 'Highest Mileage', 'reg' => $highestMileageVehicle['registration_number'] ?? null, 'sub' => $mileSub($highestMileageVehicle), 'icon' => 'arrow-up-circle'],
                ['label' => 'Lowest Mileage', 'reg' => $lowestMileageVehicle['registration_number'] ?? null, 'sub' => $mileSub($lowestMileageVehicle), 'icon' => 'arrow-down-circle'],
            ];
        @endphp
        @foreach ($vehicleStats as $s)
            <div class="metric">
                <div class="flex items-center gap-2">
                    <flux:icon :name="$s['icon']" class="size-4 text-zinc-400 dark:text-zinc-500" />
                    <span class="metric-label">{{ $s['label'] }}</span>
                </div>
                @if ($s['reg'])
                    <div class="metric-value mt-1 text-xl">{{ $s['reg'] }}</div>
                    <div class="metric-sub">{{ $s['sub'] }}</div>
                @else
                    <div class="metric-value mt-1 text-xl text-zinc-300 dark:text-zinc-600">-</div>
                    <div class="metric-sub">No vehicles yet</div>
                @endif
            </div>
        @endforeach
    </div>

    <div class="grid gap-6 lg:grid-cols-3">
        {{-- Live trip-status board --}}
        <div class="panel lg:col-span-2">
            <div class="panel-head">
                <h2 class="panel-title"><flux:icon.list-bullet />Trip Status · Today</h2>
                @can('view-trips')
                    <a href="{{ route('trips', ['dateFrom' => now()->toDateString(), 'dateTo' => now()->toDateString()]) }}"
                        wire:navigate
                        class="flex items-center gap-1 text-[11px] font-bold uppercase tracking-wider text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        View all <flux:icon.arrow-right class="size-3.5" />
                    </a>
                @endcan
            </div>
            <div class="panel-body">
                @php
                    $board = [
                        ['key' => 'on_time', 'label' => 'On Time', 'tone' => 'text-emerald-500', 'bar' => 'bg-emerald-500'],
                        ['key' => 'delayed', 'label' => 'Delayed', 'tone' => 'text-amber-500', 'bar' => 'bg-amber-500'],
                        ['key' => 'completed', 'label' => 'Completed', 'tone' => 'text-zinc-400', 'bar' => 'bg-zinc-400'],
                        ['key' => 'scheduled', 'label' => 'Scheduled', 'tone' => 'text-blue-500', 'bar' => 'bg-blue-500'],
                    ];
                    $boardTotal = collect($board)->sum(fn($b) => $tripCounts[$b['key']] ?? 0);
                @endphp
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($board as $b)
                        <div class="rounded-lg border border-zinc-200 p-3.5 dark:border-zinc-800">
                            <div class="flex items-center gap-1.5 text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                                <span class="dot {{ $b['tone'] }}"></span>{{ $b['label'] }}
                            </div>
                            <div class="mt-1.5 font-mono text-2xl font-semibold tabular-nums text-zinc-900 dark:text-zinc-50">
                                {{ $tripCounts[$b['key']] ?? 0 }}
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Proportional status bar --}}
                <div class="mt-5 flex h-2 gap-0.5 overflow-hidden rounded-full">
                    @if ($boardTotal > 0)
                        @foreach ($board as $b)
                            @php $n = $tripCounts[$b['key']] ?? 0; @endphp
                            @if ($n > 0)
                                <div class="{{ $b['bar'] }}" style="width: {{ round(($n / $boardTotal) * 100) }}%" title="{{ $b['label'] }}: {{ $n }}"></div>
                            @endif
                        @endforeach
                    @else
                        <div class="w-full bg-zinc-100 dark:bg-zinc-800"></div>
                    @endif
                </div>
                <div class="mt-2 flex justify-between text-[10px] font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500">
                    <span>{{ $boardTotal }} {{ \Illuminate\Support\Str::plural('trip', $boardTotal) }} tracked</span>
                    <span>Active tracking enabled</span>
                </div>
            </div>
        </div>

        {{-- Fleet utilisation --}}
        <div class="panel">
            <div class="panel-head">
                <h2 class="panel-title"><flux:icon.chart-pie />Fleet Utilisation</h2>
            </div>
            <div class="panel-body space-y-4">
                @forelse ($fleet as $status => $count)
                    <div>
                        <div class="mb-1.5 flex items-center justify-between text-[11px] font-semibold uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                            <span>{{ str_replace('_', ' ', $status) }}</span>
                            <span class="font-mono tabular-nums text-zinc-700 dark:text-zinc-200">{{ $count }} / {{ $fleetTotal }}</span>
                        </div>
                        <div class="h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="h-full rounded-full bg-indigo-500 transition-[width] duration-700"
                                style="width: {{ $fleetTotal ? round(($count / $fleetTotal) * 100) : 0 }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">No vehicles yet.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Today's schedule : operations table --}}
    <div class="panel overflow-hidden">
        <div class="panel-head">
            <h2 class="panel-title"><flux:icon.calendar-days />Today's Schedule</h2>
            <span class="pill pill-zinc">{{ $todaySchedules->count() }} {{ \Illuminate\Support\Str::plural('run', $todaySchedules->count()) }}</span>
        </div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Route</th>
                        <th>Driver</th>
                        <th>Vehicle</th>
                        <th class="th-num">Window</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $pillFor = fn($s) => match (strtolower((string) $s)) {
                            'active', 'on_time', 'completed' => 'pill-green',
                            'delayed' => 'pill-amber',
                            'cancelled', 'suspended' => 'pill-red',
                            'scheduled', 'pending' => 'pill-blue',
                            default => 'pill-zinc',
                        };
                    @endphp
                    @forelse ($todaySchedules as $schedule)
                        <tr>
                            <td class="td-strong">
                                <div class="flex flex-col">
                                    <span>{{ $schedule->route?->start_point }} → {{ $schedule->route?->end_point }}</span>
                                    <span class="font-mono text-xs text-zinc-400 dark:text-zinc-500">{{ $schedule->route?->code }}</span>
                                </div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2.5">
                                    <span class="flex size-7 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-[11px] font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                                        {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($schedule->driver?->name ?? '?', 0, 1)) }}
                                    </span>
                                    <span class="text-zinc-700 dark:text-zinc-200">{{ $schedule->driver?->name ?? '-' }}</span>
                                </div>
                            </td>
                            <td class="font-mono">{{ $schedule->vehicle?->registration_number ?? '-' }}</td>
                            <td class="td-num font-mono">{{ substr($schedule->departure_time, 0, 5) }}–{{ substr($schedule->arrival_time, 0, 5) }}</td>
                            <td>
                                <span class="pill {{ $pillFor($schedule->status) }}">{{ str_replace('_', ' ', $schedule->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">No schedules running today.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Alerts --}}
    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Licences expiring --}}
        <div class="panel">
            <div class="panel-head">
                <h2 class="panel-title"><flux:icon.bell-alert />Licences Expiring · 30 Days</h2>
                <span class="pill {{ $expiringLicences->isEmpty() ? 'pill-zinc' : 'pill-amber' }}">{{ $expiringLicences->count() }}</span>
            </div>
            <div class="panel-body space-y-1.5">
                @forelse ($expiringLicences as $driver)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200">
                        <span class="min-w-0 truncate">{{ $driver->name }} <span class="font-mono text-xs opacity-70">({{ $driver->license_number }})</span></span>
                        <span class="shrink-0 font-mono tabular-nums">{{ $driver->license_expiry->format('Y-m-d') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">No licences expiring in the next 30 days.</p>
                @endforelse
            </div>
        </div>

        {{-- Service due --}}
        <div class="panel">
            <div class="panel-head">
                <h2 class="panel-title"><flux:icon.wrench-screwdriver />Vehicles · Service Due</h2>
                <span class="pill {{ $serviceDueVehicles->isEmpty() ? 'pill-zinc' : 'pill-red' }}">{{ $serviceDueVehicles->count() }}</span>
            </div>
            <div class="panel-body space-y-1.5">
                @forelse ($serviceDueVehicles as $vehicle)
                    <div class="flex items-center justify-between gap-3 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200">
                        <span class="font-mono">{{ $vehicle->registration_number }}</span>
                        <span class="shrink-0 font-mono tabular-nums">due {{ $vehicle->latestMaintenance?->next_due_at?->format('Y-m-d') }}</span>
                    </div>
                @empty
                    <p class="text-sm text-zinc-400 dark:text-zinc-500">No vehicles are overdue for service.</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Network map : large map on the left, route tabs on the right. Selecting a
         route plots its stops + connecting line. Leaflet/OpenStreetMap, no key. --}}
    <div class="panel overflow-hidden">
        <div class="panel-head">
            <h2 class="panel-title"><flux:icon.map />Network Map</h2>
            <span class="pill pill-zinc">{{ count($mapRoutes) }} {{ \Illuminate\Support\Str::plural('route', count($mapRoutes)) }}</span>
        </div>

        @if (count($mapRoutes) === 0)
            <div class="panel-body">
                <p class="callout-muted">No routes have geo-located stops yet. Add latitude/longitude to a route's
                    stops to plot it here.</p>
            </div>
        @else
            <div wire:ignore x-data="networkMap(@js($mapRoutes))" class="grid lg:grid-cols-3">
                {{-- Map canvas --}}
                <div class="lg:col-span-2">
                    <div x-ref="map" class="h-96 w-full bg-zinc-100 lg:h-128 dark:bg-zinc-800"></div>
                </div>

                {{-- Route tabs --}}
                <div class="border-t border-zinc-200 lg:border-t-0 lg:border-l dark:border-zinc-800">
                    <div class="border-b border-zinc-200 px-4 py-2.5 text-[10px] font-bold uppercase tracking-widest text-zinc-400 dark:border-zinc-800 dark:text-zinc-500">
                        Current Routes
                    </div>
                    <div class="max-h-96 divide-y divide-zinc-100 overflow-y-auto lg:max-h-116 dark:divide-zinc-800">
                        <template x-for="route in routes" :key="route.id">
                            <button type="button" @click="select(route.id)"
                                class="flex w-full items-start gap-3 border-l-2 px-4 py-3 text-left transition-colors"
                                :class="activeId === route.id
                                    ? 'border-l-indigo-500 bg-indigo-50/70 dark:bg-indigo-500/10'
                                    : 'border-l-transparent hover:bg-zinc-50 dark:hover:bg-zinc-800/50'">
                                <flux:icon.map-pin class="mt-0.5 size-4 shrink-0 text-zinc-400 dark:text-zinc-500" />
                                <span class="min-w-0 flex-1">
                                    <span class="flex items-center justify-between gap-2">
                                        <span class="font-mono text-xs font-semibold text-zinc-900 dark:text-zinc-100"
                                            x-text="route.code"></span>
                                        <span class="font-mono text-[10px] tabular-nums text-zinc-400 dark:text-zinc-500"
                                            x-text="route.stops.length + ' stops'"></span>
                                    </span>
                                    <span class="mt-0.5 block truncate text-sm text-zinc-600 dark:text-zinc-300">
                                        <span x-text="route.start"></span> → <span x-text="route.end"></span>
                                    </span>
                                </span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

@script
    <script>
        // Plots a selected route's stops + connecting line on a Leaflet/OpenStreetMap
        // canvas. Mirrors the route-manager map loader (no API key, no cost); clicking
        // a route tab redraws the layer without touching the base map.
        Alpine.data('networkMap', (routes) => ({
            routes,
            activeId: routes.length ? routes[0].id : null,
            map: null,
            layer: null,

            init() {
                if (this.routes.length) this.loadLeaflet();
            },

            select(id) {
                this.activeId = id;
                this.draw();
            },

            loadLeaflet() {
                if (window.L) {
                    this.initMap();
                    return;
                }
                if (!document.getElementById('leaflet-css')) {
                    const link = document.createElement('link');
                    link.id = 'leaflet-css';
                    link.rel = 'stylesheet';
                    link.href = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
                    document.head.appendChild(link);
                }
                if (!document.getElementById('leaflet-sdk')) {
                    const s = document.createElement('script');
                    s.id = 'leaflet-sdk';
                    s.src = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
                    s.onload = () => window.dispatchEvent(new CustomEvent('leaflet-ready'));
                    document.head.appendChild(s);
                }
                window.addEventListener('leaflet-ready', () => this.initMap(), {
                    once: true
                });
            },

            initMap() {
                if (!this.$refs.map || !window.L || this.map) return;
                delete L.Icon.Default.prototype._getIconUrl;
                L.Icon.Default.mergeOptions({
                    iconRetinaUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon-2x.png',
                    iconUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-icon.png',
                    shadowUrl: 'https://unpkg.com/leaflet@1.9.4/dist/images/marker-shadow.png',
                });
                this.map = L.map(this.$refs.map);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                    attribution: '&copy; OpenStreetMap contributors',
                }).addTo(this.map);
                this.layer = L.layerGroup().addTo(this.map);
                this.draw();
                setTimeout(() => this.map.invalidateSize(), 200);
            },

            draw() {
                if (!this.map || !this.layer) return;
                this.layer.clearLayers();
                const route = this.routes.find((r) => r.id === this.activeId);
                if (!route || !route.stops.length) {
                    this.map.setView([7.8731, 80.7718], 7); // Sri Lanka fallback
                    return;
                }
                const points = [];
                route.stops.forEach((stop) => {
                    const latlng = [stop.lat, stop.lng];
                    L.marker(latlng).addTo(this.layer).bindPopup(stop.seq + '. ' + stop.name);
                    points.push(latlng);
                });
                if (points.length > 1) {
                    L.polyline(points, {
                        color: '#4f46e5',
                        weight: 3
                    }).addTo(this.layer);
                }
                this.map.fitBounds(points, {
                    padding: [40, 40]
                });
            },
        }));
    </script>
@endscript
