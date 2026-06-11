<?php

namespace App\Services;

use App\Models\FuelLog;
use App\Models\MaintenanceLog;
use App\Models\Trip;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Reporting & analytics aggregations (Phase 5).
 *
 * Each method takes an inclusive date range and returns plain arrays/collections
 * that both the Reports screen and the PDF export render. Kept framework-light
 * and database-agnostic (period grouping is done in PHP, not with vendor SQL
 * date functions) so it behaves the same on MySQL and the sqlite test database.
 */
class ReportService
{
    /** Trip-completion summary: counts by status + completion rate. */
    public function tripCompletion(Carbon $from, Carbon $to): array
    {
        $counts = Trip::query()
            ->whereBetween('trip_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $total = (int) $counts->sum();
        $completed = (int) ($counts['completed'] ?? 0);

        return [
            'total'           => $total,
            'completed'       => $completed,
            'on_time'         => (int) ($counts['on_time'] ?? 0),
            'delayed'         => (int) ($counts['delayed'] ?? 0),
            'scheduled'       => (int) ($counts['scheduled'] ?? 0),
            'completion_rate' => $total ? round($completed / $total * 100, 1) : 0.0,
        ];
    }

    /**
     * Route performance: trips and completion rate per route.
     *
     * @return Collection<int, array{code:string, trips:int, completed:int, rate:float}>
     */
    public function routePerformance(Carbon $from, Carbon $to): Collection
    {
        return Trip::query()
            ->whereBetween('trip_date', [$from->toDateString(), $to->toDateString()])
            ->join('schedules', 'trips.schedule_id', '=', 'schedules.id')
            ->join('bus_routes', 'schedules.bus_route_id', '=', 'bus_routes.id')
            ->selectRaw("bus_routes.code as code, count(*) as trips, sum(case when trips.status = 'completed' then 1 else 0 end) as completed")
            ->groupBy('bus_routes.code')
            ->orderBy('bus_routes.code')
            ->get()
            ->map(fn ($row) => [
                'code'      => $row->code,
                'trips'     => (int) $row->trips,
                'completed' => (int) $row->completed,
                'rate'      => $row->trips ? round($row->completed / $row->trips * 100, 1) : 0.0,
            ]);
    }

    /**
     * Fuel-consumption trend grouped by month (Y-m).
     *
     * @return Collection<int, array{period:string, liters:float, cost:float}>
     */
    public function fuelTrend(Carbon $from, Carbon $to): Collection
    {
        return FuelLog::query()
            ->whereBetween('logged_at', [$from->toDateString(), $to->toDateString()])
            ->get()
            ->groupBy(fn (FuelLog $log) => $log->logged_at->format('Y-m'))
            ->map(fn (Collection $group, string $period) => [
                'period' => $period,
                'liters' => round((float) $group->sum('liters'), 2),
                'cost'   => round((float) $group->sum('cost'), 2),
            ])
            ->sortKeys()
            ->values();
    }

    /**
     * Vehicle utilisation: trip count per vehicle within the range.
     *
     * @return Collection<int, array{vehicle:string, trips:int}>
     */
    public function vehicleUtilization(Carbon $from, Carbon $to): Collection
    {
        return Trip::query()
            ->whereBetween('trip_date', [$from->toDateString(), $to->toDateString()])
            ->join('schedules', 'trips.schedule_id', '=', 'schedules.id')
            ->join('vehicles', 'schedules.vehicle_id', '=', 'vehicles.id')
            ->selectRaw('vehicles.registration_number as vehicle, count(*) as trips')
            ->groupBy('vehicles.registration_number')
            ->orderByDesc('trips')
            ->get()
            ->map(fn ($row) => [
                'vehicle' => $row->vehicle,
                'trips'   => (int) $row->trips,
            ]);
    }

    /**
     * Maintenance summary per vehicle: routine/corrective counts + total cost.
     *
     * @return Collection<int, array{vehicle:string, routine:int, corrective:int, cost:float}>
     */
    public function maintenanceSummary(Carbon $from, Carbon $to): Collection
    {
        return MaintenanceLog::query()
            ->whereBetween('serviced_at', [$from->toDateString(), $to->toDateString()])
            ->join('vehicles', 'maintenance_logs.vehicle_id', '=', 'vehicles.id')
            ->selectRaw("vehicles.registration_number as vehicle, sum(case when maintenance_logs.type = 'routine' then 1 else 0 end) as routine, sum(case when maintenance_logs.type = 'corrective' then 1 else 0 end) as corrective, sum(maintenance_logs.cost) as cost")
            ->groupBy('vehicles.registration_number')
            ->orderBy('vehicles.registration_number')
            ->get()
            ->map(fn ($row) => [
                'vehicle'    => $row->vehicle,
                'routine'    => (int) $row->routine,
                'corrective' => (int) $row->corrective,
                'cost'       => round((float) $row->cost, 2),
            ]);
    }

    /** All five reports in one call, for the screen and the PDF. */
    public function all(Carbon $from, Carbon $to): array
    {
        return [
            'tripCompletion'     => $this->tripCompletion($from, $to),
            'routePerformance'   => $this->routePerformance($from, $to),
            'fuelTrend'          => $this->fuelTrend($from, $to),
            'vehicleUtilization' => $this->vehicleUtilization($from, $to),
            'maintenanceSummary' => $this->maintenanceSummary($from, $to),
        ];
    }
}
