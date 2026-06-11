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
 * Depot dashboard (Phase 5) — the operational overview shown after login.
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

        return view('livewire.dashboard', [
            'cards'              => $cards,
            'tripCounts'         => $tripCounts,
            'todaySchedules'     => $todaySchedules,
            'expiringLicences'   => $expiringLicences,
            'serviceDueVehicles' => $serviceDueVehicles,
            'fleet'              => $fleet,
            'fleetTotal'        => $fleet->sum(),
        ]);
    }
}
