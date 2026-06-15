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
 * Central trips board with a three-way status model:
 *
 *   - Staff with manage-schedules (admin/supervisor) set any status directly,
 *     including the staff-only 'cancelled'.
 *   - A driver (view-own-trips + request-trip-status) sees only their own trips
 *     and *requests* a non-cancelled status; the request is parked in
 *     pending_status until approved, so a driver cannot self-certify a trip.
 *   - Anyone with approve-trip-status (admin/supervisor/operator) approves or
 *     rejects a pending request.
 *
 * Filters bind to the query string (#[Url]) so the dashboard "See all" link can
 * deep-link straight to, e.g., today's trips. For a driver the board is locked
 * to their own driver_id and the route/vehicle/driver filters are hidden.
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

    /** The driver this user is, when logged in as a driver (null for staff). */
    public ?int $ownDriverId = null;

    public function mount(): void
    {
        $this->ownDriverId = auth()->user()->driver?->id;
    }

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

    /** Set a trip's status directly - admin/supervisor only. Allows 'cancelled'. */
    public function updateTripStatus(int $tripId, string $status): void
    {
        abort_unless(auth()->user()->can('manage-schedules'), 403);
        abort_unless(in_array($status, Trip::STATUSES, true), 422);

        Trip::findOrFail($tripId)->update([
            'status' => $status,
            // A direct staff change supersedes any pending driver request.
            'pending_status' => null,
            'status_requested_by' => null,
            'status_requested_at' => null,
        ]);

        session()->flash('status', 'Trip status updated.');
    }

    /** A driver requests a (non-cancelled) status for one of their own trips. */
    public function requestTripStatus(int $tripId, string $status): void
    {
        abort_unless(auth()->user()->can('request-trip-status'), 403);
        abort_unless(in_array($status, Trip::DRIVER_REQUESTABLE, true), 422);

        $trip = Trip::with('schedule')->findOrFail($tripId);
        abort_unless($trip->schedule?->driver_id === $this->ownDriverId, 403);

        $trip->update([
            'pending_status' => $status,
            'status_requested_by' => auth()->id(),
            'status_requested_at' => now(),
        ]);

        session()->flash('status', 'Status change requested - awaiting approval.');
    }

    /** Approve a driver's pending status request - applies it to the trip. */
    public function approveStatus(int $tripId): void
    {
        abort_unless(auth()->user()->can('approve-trip-status'), 403);

        $trip = Trip::findOrFail($tripId);
        if (! $trip->hasPendingStatus()) {
            return;
        }

        $trip->update([
            'status' => $trip->pending_status,
            'pending_status' => null,
            'status_requested_by' => null,
            'status_requested_at' => null,
        ]);

        session()->flash('status', 'Status change approved.');
    }

    /** Reject a driver's pending status request - leaves the live status unchanged. */
    public function rejectStatus(int $tripId): void
    {
        abort_unless(auth()->user()->can('approve-trip-status'), 403);

        Trip::findOrFail($tripId)->update([
            'pending_status' => null,
            'status_requested_by' => null,
            'status_requested_at' => null,
        ]);

        session()->flash('status', 'Status change rejected.');
    }

    public function render()
    {
        $isDriver = $this->ownDriverId !== null;

        $trips = Trip::query()
            ->with(['schedule.route', 'schedule.vehicle', 'schedule.driver', 'statusRequester'])
            // A driver only ever sees their own trips, whatever the URL says.
            ->when($isDriver, fn ($q) => $q->whereHas('schedule', fn ($s) => $s->where('driver_id', $this->ownDriverId)))
            ->when($this->filterRouteId, fn ($q) => $q->whereHas('schedule', fn ($s) => $s->where('bus_route_id', $this->filterRouteId)))
            ->when($this->filterVehicleId, fn ($q) => $q->whereHas('schedule', fn ($s) => $s->where('vehicle_id', $this->filterVehicleId)))
            ->when($this->filterDriverId, fn ($q) => $q->whereHas('schedule', fn ($s) => $s->where('driver_id', $this->filterDriverId)))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('trip_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('trip_date', '<=', $this->dateTo))
            ->latest('trip_date')
            ->paginate(15);

        return view('livewire.trip-manager', [
            'trips' => $trips,
            'routes' => BusRoute::orderBy('code')->get(),
            'vehicles' => Vehicle::orderBy('registration_number')->get(),
            'drivers' => Driver::orderBy('name')->get(),
            'isDriver' => $isDriver,
            'canManage' => auth()->user()->can('manage-schedules'),
            'canRequest' => $isDriver && auth()->user()->can('request-trip-status'),
            'canApprove' => auth()->user()->can('approve-trip-status'),
        ]);
    }
}
