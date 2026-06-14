<div class="page">
    <div class="page-header">
        <div class="page-heading">
            <span class="icon-chip icon-chip-indigo"><flux:icon.chart-bar /></span>
            <div>
                <h1 class="page-title">Reports &amp; Analytics</h1>
                <p class="page-sub">Operational reports for the selected date range.</p>
            </div>
        </div>
        <a href="{{ route('reports.pdf', ['from' => $from, 'to' => $to]) }}" class="btn-primary">Download PDF</a>
    </div>

    <div class="filter-bar">
        <div class="w-full sm:w-44">
            <label class="label">From</label>
            <input type="date" wire:model.live="from" class="input">
        </div>
        <div class="w-full sm:w-44">
            <label class="label">To</label>
            <input type="date" wire:model.live="to" class="input">
        </div>
    </div>

    {{-- Trip completion summary --}}
    <div class="card card-pad">
        <h2 class="card-title">Trip completion</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @php
                $tc = [
                    ['Total', $tripCompletion['total'], 'text-zinc-900 dark:text-zinc-100'],
                    ['Completed', $tripCompletion['completed'], 'text-green-600 dark:text-green-400'],
                    ['On time', $tripCompletion['on_time'], 'text-blue-600 dark:text-blue-400'],
                    ['Delayed', $tripCompletion['delayed'], 'text-red-600 dark:text-red-400'],
                    ['Scheduled', $tripCompletion['scheduled'], 'text-zinc-600 dark:text-zinc-300'],
                    ['Completion', $tripCompletion['completion_rate'] . '%', 'text-indigo-600 dark:text-indigo-400'],
                ];
            @endphp
            @foreach ($tc as [$label, $value, $color])
                <div class="tile bg-zinc-50 dark:bg-zinc-800">
                    <div class="tile-value {{ $color }}">{{ $value }}</div>
                    <div class="tile-label text-zinc-500 dark:text-zinc-400">{{ $label }}</div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Cost summary --}}
        <div class="card card-pad">
            <h2 class="card-title">Cost summary</h2>
            <div class="grid grid-cols-3 gap-3">
                @php
                    $cs = [
                        ['Fuel', $costSummary['fuel'], 'text-amber-600 dark:text-amber-400'],
                        ['Maintenance', $costSummary['maintenance'], 'text-purple-600 dark:text-purple-400'],
                        ['Total', $costSummary['total'], 'text-indigo-600 dark:text-indigo-400'],
                    ];
                @endphp
                @foreach ($cs as [$label, $value, $color])
                    <div class="tile bg-zinc-50 dark:bg-zinc-800">
                        <div class="tile-value {{ $color }}">{{ number_format($value, 2) }}</div>
                        <div class="tile-label text-zinc-500 dark:text-zinc-400">{{ $label }}</div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Trip-status breakdown (doughnut) --}}
        <div class="card card-pad">
            <h2 class="card-title">Trip status breakdown</h2>
            @if ($tripCompletion['total'] === 0)
                <p class="text-sm text-zinc-400 dark:text-zinc-500">No trips in this range.</p>
            @else
                <div wire:ignore wire:key="status-{{ $from }}-{{ $to }}" class="mx-auto max-w-xs"
                     x-data="srmssChart('doughnut',
                        @js(['Completed', 'On time', 'Delayed', 'Scheduled']),
                        @js([$tripCompletion['completed'], $tripCompletion['on_time'], $tripCompletion['delayed'], $tripCompletion['scheduled']]),
                        'Trips')">
                    <canvas x-ref="canvas" class="max-h-64"></canvas>
                </div>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Route performance: table + bar chart --}}
        <div class="card">
            <div class="card-pad pb-0"><h2 class="card-title">Route performance</h2></div>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Route</th>
                            <th class="th-num">Trips</th>
                            <th class="th-num">Completed</th>
                            <th class="th-num">Rate</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($routePerformance as $row)
                            <tr>
                                <td class="td-strong">{{ $row['code'] }}</td>
                                <td class="td-num">{{ $row['trips'] }}</td>
                                <td class="td-num">{{ $row['completed'] }}</td>
                                <td class="td-num">{{ $row['rate'] }}%</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="empty">No trips in this range.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($routePerformance->isNotEmpty())
                <div class="card-pad pt-0" wire:ignore wire:key="route-{{ $from }}-{{ $to }}"
                     x-data="srmssChart('bar', @js($routePerformance->pluck('code')), @js($routePerformance->pluck('trips')), 'Trips')">
                    <canvas x-ref="canvas" class="max-h-64"></canvas>
                </div>
            @endif
        </div>

        {{-- Vehicle utilisation: chart + table --}}
        <div class="card card-pad">
            <h2 class="card-title">Vehicle utilisation</h2>
            @if ($vehicleUtilization->isEmpty())
                <p class="text-sm text-zinc-400 dark:text-zinc-500">No trips in this range.</p>
            @else
                <div wire:ignore wire:key="util-{{ $from }}-{{ $to }}"
                     x-data="srmssChart('bar', @js($vehicleUtilization->pluck('vehicle')), @js($vehicleUtilization->pluck('trips')), 'Trips')">
                    <canvas x-ref="canvas" class="max-h-64"></canvas>
                </div>
                <div class="table-wrap mt-4 rounded-lg border border-zinc-100 dark:border-zinc-800">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Vehicle</th>
                                <th class="th-num">Trips</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($vehicleUtilization as $row)
                                <tr>
                                    <td class="td-strong">{{ $row['vehicle'] }}</td>
                                    <td class="td-num">{{ $row['trips'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Driver utilisation: chart + table --}}
        <div class="card card-pad">
            <h2 class="card-title">Driver utilisation</h2>
            @if ($driverUtilization->isEmpty())
                <p class="text-sm text-zinc-400 dark:text-zinc-500">No trips in this range.</p>
            @else
                <div wire:ignore wire:key="driver-{{ $from }}-{{ $to }}"
                     x-data="srmssChart('bar', @js($driverUtilization->pluck('driver')), @js($driverUtilization->pluck('trips')), 'Trips')">
                    <canvas x-ref="canvas" class="max-h-64"></canvas>
                </div>
                <div class="table-wrap mt-4 rounded-lg border border-zinc-100 dark:border-zinc-800">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Driver</th>
                                <th class="th-num">Trips</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($driverUtilization as $row)
                                <tr>
                                    <td class="td-strong">{{ $row['driver'] }}</td>
                                    <td class="td-num">{{ $row['trips'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Maintenance cost split (pie) --}}
        <div class="card card-pad">
            <h2 class="card-title">Maintenance cost — service vs repair</h2>
            @if (($maintenanceCostByType['routine'] + $maintenanceCostByType['corrective']) == 0)
                <p class="text-sm text-zinc-400 dark:text-zinc-500">No maintenance in this range.</p>
            @else
                <div wire:ignore wire:key="maint-{{ $from }}-{{ $to }}" class="mx-auto max-w-xs"
                     x-data="srmssChart('pie',
                        @js(['Scheduled service', 'Repair']),
                        @js([$maintenanceCostByType['routine'], $maintenanceCostByType['corrective']]),
                        'Cost')">
                    <canvas x-ref="canvas" class="max-h-64"></canvas>
                </div>
            @endif
        </div>
    </div>

    {{-- Fuel consumption trend --}}
    <div class="card card-pad">
        <h2 class="card-title">Fuel consumption trend</h2>
        @if ($fuelTrend->isEmpty())
            <p class="text-sm text-zinc-400 dark:text-zinc-500">No fuel logs in this range.</p>
        @else
            <div wire:ignore wire:key="fuel-{{ $from }}-{{ $to }}"
                 x-data="srmssChart('line', @js($fuelTrend->pluck('period')), @js($fuelTrend->pluck('liters')), 'Litres')">
                <canvas x-ref="canvas" class="max-h-72"></canvas>
            </div>
            <div class="table-wrap mt-5 rounded-lg border border-zinc-100 dark:border-zinc-800">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Month</th>
                            <th class="th-num">Litres</th>
                            <th class="th-num">Cost</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($fuelTrend as $row)
                            <tr>
                                <td class="tabular-nums">{{ $row['period'] }}</td>
                                <td class="td-num">{{ number_format($row['liters'], 2) }}</td>
                                <td class="td-num">{{ number_format($row['cost'], 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Maintenance summary --}}
    <div class="card">
        <div class="card-pad pb-0"><h2 class="card-title">Maintenance summary</h2></div>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th class="th-num">Service</th>
                        <th class="th-num">Repair</th>
                        <th class="th-num">Total Cost</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($maintenanceSummary as $row)
                        <tr>
                            <td class="td-strong">{{ $row['vehicle'] }}</td>
                            <td class="td-num">{{ $row['routine'] }}</td>
                            <td class="td-num">{{ $row['corrective'] }}</td>
                            <td class="td-num">{{ number_format($row['cost'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="empty">No maintenance in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @script
    <script>
        // Loads Chart.js once (CDN), then renders a chart into the element's canvas.
        // Supports bar/line (single indigo series) and pie/doughnut (multi-colour + legend).
        Alpine.data('srmssChart', (type, labels, data, label) => ({
            type, labels, data, label,
            chart: null,
            init() {
                if (window.Chart) { this.draw(); return; }
                if (! document.getElementById('chartjs-sdk')) {
                    const s = document.createElement('script');
                    s.id = 'chartjs-sdk';
                    s.src = 'https://cdn.jsdelivr.net/npm/chart.js@4';
                    s.onload = () => window.dispatchEvent(new CustomEvent('chartjs-ready'));
                    document.head.appendChild(s);
                }
                window.addEventListener('chartjs-ready', () => this.draw(), { once: true });
            },
            draw() {
                if (! this.$refs.canvas || ! window.Chart) return;
                const isPie = this.type === 'pie' || this.type === 'doughnut';
                const palette = ['#4f46e5', '#16a34a', '#dc2626', '#d97706', '#0891b2', '#7c3aed', '#db2777', '#65a30d'];
                this.chart = new Chart(this.$refs.canvas, {
                    type: this.type,
                    data: {
                        labels: this.labels,
                        datasets: [{
                            label: this.label,
                            data: this.data,
                            backgroundColor: isPie ? this.labels.map((_, i) => palette[i % palette.length]) : 'rgba(79, 70, 229, 0.5)',
                            borderColor: isPie ? '#ffffff' : 'rgb(79, 70, 229)',
                            borderWidth: isPie ? 1 : 2,
                            tension: 0.3,
                        }],
                    },
                    options: {
                        responsive: true,
                        plugins: { legend: { display: isPie, position: 'bottom' } },
                    },
                });
            },
        }));
    </script>
    @endscript
</div>
