<?php

namespace App\Livewire;

use App\Models\BusRoute;
use App\Models\RouteStop;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Validate;

/**
 * Routes module (Phase 3) : CRUD for bus routes plus ordered-stop management.
 *
 * Follows the VehicleManager/DriverManager pattern (list + search + modal form)
 * and adds a second panel for managing a route's stops: add, remove, and reorder
 * (move up/down). Guarded by the `manage-routes` permission.
 *
 * Deferred (separate follow-ups): the Google Map render (needs a Maps API key)
 * and vehicle/driver assignment, which belongs with Phase 4 scheduling so it can
 * reuse the conflict/availability checks.
 */
class RouteManager extends Component
{
    use WithPagination;

    public string $search = '';

    // --- Route form state ---
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Validate('required|string|max:30')]
    public string $code = '';

    #[Validate('required|string|max:120')]
    public string $name = '';

    #[Validate('required|string|max:120')]
    public string $start_point = '';

    #[Validate('required|string|max:120')]
    public string $end_point = '';

    #[Validate('required|numeric|min:0')]
    public float $total_distance_km = 0;

    #[Validate('required|string|max:30')]
    public string $service_type = 'normal';

    #[Validate('required|in:active,inactive')]
    public string $status = 'active';

    // --- Stops panel state ---
    public ?int $managingStopsFor = null;

    #[Validate('nullable|string|max:120')]
    public string $newStopName = '';

    #[Validate('nullable|numeric|between:-90,90')]
    public string $newStopLat = '';

    #[Validate('nullable|numeric|between:-180,180')]
    public string $newStopLng = '';

    // --- Map panel state ---
    public ?int $showMapFor = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->reset([
            'editingId', 'code', 'name', 'start_point', 'end_point',
            'total_distance_km', 'service_type', 'status',
        ]);
        $this->total_distance_km = 0;
        $this->service_type = 'normal';
        $this->status = 'active';
        $this->showModal = true;
    }

    public function edit(int $id): void
    {
        $route = BusRoute::findOrFail($id);
        $this->editingId = $route->id;
        $this->code = $route->code;
        $this->name = $route->name;
        $this->start_point = $route->start_point;
        $this->end_point = $route->end_point;
        $this->total_distance_km = (float) $route->total_distance_km;
        $this->service_type = $route->service_type;
        $this->status = $route->status;
        $this->showModal = true;
    }

    public function save(): void
    {
        $data = $this->validate([
            'code'              => 'required|string|max:30|unique:bus_routes,code,' . $this->editingId,
            'name'              => 'required|string|max:120',
            'start_point'       => 'required|string|max:120',
            'end_point'         => 'required|string|max:120',
            'total_distance_km' => 'required|numeric|min:0',
            'service_type'      => 'required|string|max:30',
            'status'            => 'required|in:active,inactive',
        ]);

        BusRoute::updateOrCreate(['id' => $this->editingId], $data);

        $this->showModal = false;
        session()->flash('status', $this->editingId ? 'Route updated.' : 'Route added.');
        $this->reset(['editingId']);
    }

    public function delete(int $id): void
    {
        // Stops cascade-delete with the route (FK constraint).
        BusRoute::findOrFail($id)->delete();
        session()->flash('status', 'Route deleted.');
    }

    // --- Stops management -----------------------------------------------------

    public function manageStops(int $routeId): void
    {
        $this->managingStopsFor = $routeId;
        $this->newStopName = '';
    }

    public function closeStops(): void
    {
        $this->reset(['managingStopsFor', 'newStopName', 'newStopLat', 'newStopLng']);
    }

    public function addStop(): void
    {
        $this->validate([
            'newStopName' => 'required|string|max:120',
            'newStopLat'  => 'nullable|numeric|between:-90,90',
            'newStopLng'  => 'nullable|numeric|between:-180,180',
        ]);

        $route = BusRoute::findOrFail($this->managingStopsFor);
        $nextSequence = ((int) $route->stops()->max('sequence')) + 1;

        $route->stops()->create([
            'name'      => $this->newStopName,
            'sequence'  => $nextSequence,
            'latitude'  => $this->newStopLat !== '' ? $this->newStopLat : null,
            'longitude' => $this->newStopLng !== '' ? $this->newStopLng : null,
        ]);

        $this->reset(['newStopName', 'newStopLat', 'newStopLng']);
    }

    // --- Map ------------------------------------------------------------------

    public function viewMap(int $routeId): void
    {
        $this->showMapFor = $routeId;
    }

    public function closeMap(): void
    {
        $this->reset(['showMapFor']);
    }

    public function removeStop(int $stopId): void
    {
        $stop = RouteStop::where('bus_route_id', $this->managingStopsFor)->findOrFail($stopId);
        $stop->delete();
        $this->resequence();
    }

    public function moveStopUp(int $stopId): void
    {
        $this->swapWithNeighbour($stopId, -1);
    }

    public function moveStopDown(int $stopId): void
    {
        $this->swapWithNeighbour($stopId, +1);
    }

    /** Swap a stop's sequence with the one above (-1) or below (+1) it. */
    private function swapWithNeighbour(int $stopId, int $direction): void
    {
        $stops = $this->currentStops();
        $index = $stops->search(fn ($s) => $s->id === $stopId);

        if ($index === false) {
            return;
        }

        $target = $stops->get($index + $direction);
        if (! $target) {
            return; // already at the top/bottom
        }

        $current = $stops->get($index);
        [$current->sequence, $target->sequence] = [$target->sequence, $current->sequence];
        $current->save();
        $target->save();
    }

    /** Renumber the remaining stops 1..n so sequences stay gap-free. */
    private function resequence(): void
    {
        $this->currentStops()->values()->each(function ($stop, $i) {
            $stop->update(['sequence' => $i + 1]);
        });
    }

    /** @return \Illuminate\Support\Collection<int, RouteStop> */
    private function currentStops()
    {
        return RouteStop::where('bus_route_id', $this->managingStopsFor)
            ->orderBy('sequence')
            ->get();
    }

    public function render()
    {
        $routes = BusRoute::query()
            ->withCount('stops')
            ->when($this->search, fn ($q) => $q
                ->where('code', 'like', "%{$this->search}%")
                ->orWhere('name', 'like', "%{$this->search}%"))
            ->latest()
            ->paginate(10);

        $stops = $this->managingStopsFor ? $this->currentStops() : collect();
        $managingRoute = $this->managingStopsFor ? BusRoute::find($this->managingStopsFor) : null;

        // Map panel: the route being shown, plus only the stops that have coords.
        $mapRoute = $this->showMapFor ? BusRoute::with('stops')->find($this->showMapFor) : null;
        $mapStops = $mapRoute
            ? $mapRoute->stops->whereNotNull('latitude')->whereNotNull('longitude')->values()
            : collect();

        return view('livewire.route-manager', [
            'routes'        => $routes,
            'stops'         => $stops,
            'managingRoute' => $managingRoute,
            'mapRoute'      => $mapRoute,
            'mapStops'      => $mapStops,
            'mapsKey'       => config('services.google_maps.key'),
        ]);
    }
}
