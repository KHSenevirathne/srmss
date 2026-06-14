<?php

namespace App\Livewire;

use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\Vehicle;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * Depot dashboard (Phase 5) : the operational overview shown after login.
 *
 * Summary cards, a live trip-status board (refreshed by Livewire polling, no
 * full page reload), today's schedule overview, and an alerts panel (licences
 * expiring soon, vehicles with service due). All figures come from real data.
 */
class Dashboard extends Component
{
    public function render()
    {
        $today = Carbon::today();

        // Today's trips, grouped by status for the live board.
        $tripCounts = Trip::query()
            ->whereDate('trip_date', $today)
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $cards = [
            'routes'          => BusRoute::count(),
            'activeTripsToday' => Trip::whereDate('trip_date', $today)
                ->whereIn('status', ['scheduled', 'on_time', 'delayed'])
                ->count(),
            'availableBuses'  => Vehicle::where('status', 'available')->count(),
            'assignedDrivers' => Schedule::where('status', '!=', 'cancelled')
                ->distinct('driver_id')
                ->count('driver_id'),
        ];

        // Schedules valid today (date range covers today) and not cancelled.
        $todaySchedules = Schedule::query()
            ->with(['route', 'vehicle', 'driver'])
            ->where('status', '!=', 'cancelled')
            ->whereDate('valid_from', '<=', $today)
            ->where(fn ($q) => $q->whereNull('valid_to')->orWhereDate('valid_to', '>=', $today))
            ->orderBy('departure_time')
            ->get();

        // Alerts.
        $expiringLicences = Driver::query()
            ->whereDate('license_expiry', '>=', $today)
            ->whereDate('license_expiry', '<=', $today->copy()->addDays(30))
            ->orderBy('license_expiry')
            ->get();

        $serviceDueVehicles = Vehicle::with('latestMaintenance')
            ->get()
            ->filter(fn (Vehicle $v) => $v->serviceDue())
            ->values();

        // Fleet utilisation breakdown by status.
        $fleet = Vehicle::query()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        // Vehicle usage : total trips per vehicle (all-time), including vehicles
        // with none so "least used" can surface an idle bus. Ordered most → least
        // with registration_number as a stable tie-break.
        $vehicleUsage = Vehicle::query()
            ->leftJoin('schedules', 'schedules.vehicle_id', '=', 'vehicles.id')
            ->leftJoin('trips', 'trips.schedule_id', '=', 'schedules.id')
            ->groupBy('vehicles.id', 'vehicles.registration_number')
            ->selectRaw('vehicles.registration_number as registration_number, count(trips.id) as trips')
            ->orderByDesc('trips')
            ->orderBy('vehicles.registration_number')
            ->get()
            ->map(fn ($row) => [
                'registration_number' => $row->registration_number,
                'trips'               => (int) $row->trips,
            ]);

        // Network map : every route that has at least one geo-located stop, with
        // its ordered stops so the dashboard can plot any route on demand. Routes
        // without coordinates are dropped (nothing to draw).
        $mapRoutes = BusRoute::query()
            ->with(['stops' => fn ($q) => $q->whereNotNull('latitude')->whereNotNull('longitude')])
            ->orderBy('code')
            ->get()
            ->map(fn (BusRoute $route) => [
                'id'    => $route->id,
                'code'  => $route->code,
                'name'  => $route->name,
                'start' => $route->start_point,
                'end'   => $route->end_point,
                'stops' => $route->stops->map(fn ($s) => [
                    'name' => $s->name,
                    'seq'  => (int) $s->sequence,
                    'lat'  => (float) $s->latitude,
                    'lng'  => (float) $s->longitude,
                ])->values(),
            ])
            ->filter(fn ($r) => $r['stops']->isNotEmpty())
            ->values();

        // Mileage extremes : highest/lowest odometer across the fleet, registration
        // as a stable tie-break (mirrors the usage shape: ordered collection, first/last).
        $vehicleMileage = Vehicle::query()
            ->orderByDesc('mileage')
            ->orderBy('registration_number')
            ->get(['registration_number', 'mileage'])
            ->map(fn ($v) => [
                'registration_number' => $v->registration_number,
                'mileage'             => (int) $v->mileage,
            ]);

        return view('livewire.dashboard', [
            'cards'              => $cards,
            'tripCounts'         => $tripCounts,
            'todaySchedules'     => $todaySchedules,
            'expiringLicences'   => $expiringLicences,
            'serviceDueVehicles' => $serviceDueVehicles,
            'fleet'              => $fleet,
            'fleetTotal'        => $fleet->sum(),
            'mostUsedVehicle'    => $vehicleUsage->first(),
            'leastUsedVehicle'   => $vehicleUsage->last(),
            'highestMileageVehicle' => $vehicleMileage->first(),
            'lowestMileageVehicle'  => $vehicleMileage->last(),
            'mapRoutes'          => $mapRoutes,
        ]);
    }
}
