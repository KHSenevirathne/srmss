<?php

namespace App\Livewire;

use App\Models\BusRoute;
use App\Models\Driver;
use App\Models\Schedule;
use App\Models\Trip;
use App\Models\Vehicle;
use App\Services\ScheduleConflictService;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Schedule management (Phase 4) — the hardest module.
 *
 * Create/edit/cancel timetables (route + vehicle + driver + times + frequency +
 * validity range). On save it asks ScheduleConflictService whether the chosen
 * vehicle or driver is already booked on an overlapping date+time window and, if
 * so, blocks the save with a clear message. It can also generate Trip rows for a
 * schedule's dates. Guarded by `manage-schedules` (admin + supervisor).
 */
class ScheduleManager extends Component
{
    use WithPagination;

    // --- Filters ---
    public string $filterRouteId = '';
    public string $filterStatus = '';

    // --- Form state ---
    public bool $showModal = false;
    public ?int $editingId = null;

    public string $bus_route_id = '';
    public string $vehicle_id = '';
    public string $driver_id = '';
    public string $frequency = 'daily';
    public string $departure_time = '';
    public string $arrival_time = '';
    public string $valid_from = '';
    public string $valid_to = '';
    public string $status = 'scheduled';

    public function updatingFilterRouteId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset([
            'editingId', 'bus_route_id', 'vehicle_id', 'driver_id', 'frequency',
            'departure_time', 'arrival_time', 'valid_from', 'valid_to', 'status',
        ]);
        $this->frequency = 'daily';
        $this->status = 'scheduled';
        $this->valid_from = now()->toDateString();
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $schedule = Schedule::findOrFail($id);
        $this->editingId = $schedule->id;
        $this->bus_route_id = (string) $schedule->bus_route_id;
        $this->vehicle_id = (string) $schedule->vehicle_id;
        $this->driver_id = (string) $schedule->driver_id;
        $this->frequency = $schedule->frequency;
        $this->departure_time = substr((string) $schedule->departure_time, 0, 5);
        $this->arrival_time = substr((string) $schedule->arrival_time, 0, 5);
        $this->valid_from = $schedule->valid_from->toDateString();
        $this->valid_to = $schedule->valid_to?->toDateString() ?? '';
        $this->status = $schedule->status;
        $this->showModal = true;
    }

    public function save(ScheduleConflictService $conflicts): void
    {
        $data = $this->validate([
            'bus_route_id'   => 'required|exists:bus_routes,id',
            'vehicle_id'     => 'required|exists:vehicles,id',
            'driver_id'      => 'required|exists:drivers,id',
            'frequency'      => 'required|in:daily,weekly,monthly',
            'departure_time' => 'required|date_format:H:i',
            'arrival_time'   => 'required|date_format:H:i',
            'valid_from'     => 'required|date',
            'valid_to'       => 'nullable|date|after_or_equal:valid_from',
            'status'         => 'required|in:scheduled,active,cancelled',
        ]);

        $data['valid_to'] = $data['valid_to'] ?: null;

        if ($data['arrival_time'] <= $data['departure_time']) {
            $this->addError('arrival_time', 'Arrival time must be after the departure time.');

            return;
        }

        // The keystone rule — block an overlapping vehicle/driver booking.
        $clash = $conflicts->conflicts($data, $this->editingId)->first();
        if ($clash) {
            $who = $clash->vehicle_id == $data['vehicle_id'] ? 'vehicle' : 'driver';
            $this->addError('conflict', "That {$who} is already booked on an overlapping schedule (#{$clash->id}, {$clash->departure_time}–{$clash->arrival_time}).");

            return;
        }

        Schedule::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        session()->flash('status', $this->editingId ? 'Schedule updated.' : 'Schedule created.');
        $this->reset(['editingId']);
    }

    public function cancel(int $id): void
    {
        Schedule::whereKey($id)->update(['status' => 'cancelled']);
        session()->flash('status', 'Schedule cancelled.');
    }

    public function delete(int $id): void
    {
        // Trips cascade-delete with the schedule (FK constraint).
        Schedule::findOrFail($id)->delete();
        session()->flash('status', 'Schedule deleted.');
    }

    // --- Trips panel: view generated trips and update their live status -------

    public ?int $managingTripsFor = null;

    public function viewTrips(int $id): void
    {
        $this->managingTripsFor = $id;
    }

    public function closeTrips(): void
    {
        $this->reset(['managingTripsFor']);
    }

    /** Set one trip's live status — feeds the dashboard trip-status board. */
    public function updateTripStatus(int $tripId, string $status): void
    {
        abort_unless(in_array($status, ['scheduled', 'on_time', 'delayed', 'completed'], true), 422);

        Trip::where('schedule_id', $this->managingTripsFor)
            ->findOrFail($tripId)
            ->update(['status' => $status]);
    }

    /**
     * Generate Trip rows across the schedule's validity window, stepping by its
     * frequency. Idempotent (skips dates that already have a trip) and capped so
     * an open-ended daily schedule can't run away.
     */
    public function generateTrips(int $id): void
    {
        $schedule = Schedule::findOrFail($id);

        $start = $schedule->valid_from->isPast() ? today() : $schedule->valid_from;
        $end = $schedule->valid_to ?? today()->addDays(30);

        if ($end->lt($start)) {
            session()->flash('status', 'No upcoming dates to generate trips for.');

            return;
        }

        $step = match ($schedule->frequency) {
            'weekly'  => fn ($d) => $d->addWeek(),
            'monthly' => fn ($d) => $d->addMonth(),
            default   => fn ($d) => $d->addDay(),
        };

        $created = 0;
        $date = $start;
        for ($i = 0; $i < 366 && $date->lte($end); $i++) {
            $exists = $schedule->trips()->whereDate('trip_date', $date->toDateString())->exists();
            if (! $exists) {
                $schedule->trips()->create(['trip_date' => $date->toDateString(), 'status' => 'scheduled']);
                $created++;
            }
            $date = $step($date);
        }

        session()->flash('status', "Generated {$created} trip(s).");
    }

    public function render()
    {
        $schedules = Schedule::query()
            ->with(['route', 'vehicle', 'driver'])
            ->withCount('trips')
            ->when($this->filterRouteId, fn ($q) => $q->where('bus_route_id', $this->filterRouteId))
            ->when($this->filterStatus, fn ($q) => $q->where('status', $this->filterStatus))
            ->latest()
            ->paginate(10);

        $tripsSchedule = $this->managingTripsFor
            ? Schedule::with(['route', 'trips' => fn ($q) => $q->orderByDesc('trip_date')])->find($this->managingTripsFor)
            : null;

        return view('livewire.schedule-manager', [
            'schedules'     => $schedules,
            'routes'        => BusRoute::orderBy('code')->get(),
            'vehicles'      => Vehicle::orderBy('registration_number')->get(),
            'drivers'       => Driver::orderBy('name')->get(),
            'tripsSchedule' => $tripsSchedule,
        ]);
    }
}
