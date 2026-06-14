<?php

namespace App\Livewire;

use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\Trip;
use App\Models\Vehicle;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Central trips board — every trip across all schedules in one filterable list.
 *
 * Viewing is gated by `view-trips` (admin, supervisor and operator), so operators
 * get a read-only window. Changing a trip's live status additionally requires
 * `manage-schedules`, enforced in updateTripStatus() and reflected in the view
 * (operators see a badge, not the dropdown).
 *
 * Filters are bound to the query string (#[Url]) so the dashboard "See all" link
 * can deep-link straight to, e.g., today's trips.
 */
class TripManager extends Component
{
    use WithPagination;

    #[Url]
    public string $filterRouteId = '';

    #[Url]
    public string $filterVehicleId = '';

    #[Url]
    public string $filterDriverId = '';

    #[Url]
    public string $filterStatus = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public function updatingFilterRouteId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterVehicleId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterDriverId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['filterRouteId', 'filterVehicleId', 'filterDriverId', 'filterStatus', 'dateFrom', 'dateTo']);
        $this->resetPage();
    }

    /** Update a trip's live status — admin/supervisor only (operators are read-only). */
    public function updateTripStatus(int $tripId, string $status): void
    {
        abort_unless(auth()->user()->can('manage-schedules'), 403);
        abort_unless(in_array($status, ['scheduled', 'on_time', 'delayed', 'completed'], true), 422);

        Trip::findOrFail($tripId)->update(['status' => $status]);
    }

    public function render()
    {
        $trips = Trip::query()
            ->with(['schedule.route', 'schedule.vehicle', 'schedule.driver'])
            ->when($this->filterRouteId, fn ($q) => $q->whereHas('schedule', fn ($s) => $s->where('bus_route_id', $this->filterRouteId)))
            ->when($this->filterVehicleId, fn ($q) => $q->whereHas('schedule', fn ($s) => $s->where('vehicle_id', $this->filterVehicleId)))
            ->when($this->filterDriverId, fn ($q) => $q->whereHas('schedule', fn ($s) => $s->where('driver_id', $this->filterDriverId)))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('trip_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('trip_date', '<=', $this->dateTo))
            ->latest('trip_date')
            ->paginate(15);

        return view('livewire.trip-manager', [
            'trips'     => $trips,
            'routes'    => BusRoute::orderBy('code')->get(),
            'vehicles'  => Vehicle::orderBy('registration_number')->get(),
            'drivers'   => Driver::orderBy('name')->get(),
            'canManage' => auth()->user()->can('manage-schedules'),
        ]);
    }
}
