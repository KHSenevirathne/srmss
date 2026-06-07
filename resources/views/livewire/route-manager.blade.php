<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800">Routes</h1>
        @can('manage-routes')
            <button wire:click="create"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                + Add Route
            </button>
        @endcan
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search code or name…"
           class="w-full max-w-sm rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Code</th>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">From → To</th>
                    <th class="px-4 py-3">Distance</th>
                    <th class="px-4 py-3">Service</th>
                    <th class="px-4 py-3">Stops</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($routes as $route)
                    <tr wire:key="route-{{ $route->id }}">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $route->code }}</td>
                        <td class="px-4 py-3">{{ $route->name }}</td>
                        <td class="px-4 py-3">{{ $route->start_point }} → {{ $route->end_point }}</td>
                        <td class="px-4 py-3">{{ number_format($route->total_distance_km, 1) }} km</td>
                        <td class="px-4 py-3 capitalize">{{ str_replace('-', ' ', $route->service_type) }}</td>
                        <td class="px-4 py-3">{{ $route->stops_count }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs capitalize">{{ $route->status }}</span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                            <button wire:click="manageStops({{ $route->id }})" class="text-gray-600 hover:underline">Stops</button>
                            <button wire:click="viewMap({{ $route->id }})" class="text-gray-600 hover:underline">Map</button>
                            @can('manage-routes')
                                <button wire:click="edit({{ $route->id }})" class="text-indigo-600 hover:underline">Edit</button>
                                <button wire:click="delete({{ $route->id }})"
                                        wire:confirm="Delete this route and its stops?"
                                        class="text-red-600 hover:underline">Delete</button>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="px-4 py-6 text-center text-gray-400">No routes yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $routes->links() }}</div>

    {{-- Route create/edit modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold">{{ $editingId ? 'Edit' : 'Add' }} Route</h2>
                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Code</label>
                            <input type="text" wire:model="code" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('code') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Name</label>
                            <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Start Point</label>
                            <input type="text" wire:model="start_point" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('start_point') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">End Point</label>
                            <input type="text" wire:model="end_point" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('end_point') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-3 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Distance (km)</label>
                            <input type="number" step="0.1" wire:model="total_distance_km" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('total_distance_km') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Service</label>
                            <select wire:model="service_type" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                                <option value="normal">Normal</option>
                                <option value="semi-luxury">Semi-luxury</option>
                                <option value="luxury">Luxury</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select wire:model="status" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="mt-6 flex justify-end gap-2">
                    <button wire:click="$set('showModal', false)" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">Cancel</button>
                    <button wire:click="save" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Save</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Stops management panel --}}
    @if ($managingRoute)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-lg rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Stops — {{ $managingRoute->code }} ({{ $managingRoute->name }})</h2>
                    <button wire:click="closeStops" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <ol class="divide-y divide-gray-100 rounded-lg border border-gray-200">
                    @forelse ($stops as $i => $stop)
                        <li wire:key="stop-{{ $stop->id }}" class="flex items-center justify-between px-3 py-2 text-sm">
                            <span><span class="mr-2 font-mono text-gray-400">{{ $stop->sequence }}.</span>{{ $stop->name }}</span>
                            @can('manage-routes')
                                <span class="space-x-2">
                                    <button wire:click="moveStopUp({{ $stop->id }})" @disabled($loop->first)
                                            class="text-gray-500 hover:text-gray-800 disabled:opacity-30">↑</button>
                                    <button wire:click="moveStopDown({{ $stop->id }})" @disabled($loop->last)
                                            class="text-gray-500 hover:text-gray-800 disabled:opacity-30">↓</button>
                                    <button wire:click="removeStop({{ $stop->id }})" class="text-red-600 hover:underline">Remove</button>
                                </span>
                            @endcan
                        </li>
                    @empty
                        <li class="px-3 py-4 text-center text-sm text-gray-400">No stops yet.</li>
                    @endforelse
                </ol>

                @can('manage-routes')
                    <div class="mt-4 space-y-2">
                        <label class="block text-sm font-medium text-gray-700">Add a stop</label>
                        <div class="flex flex-wrap items-start gap-2">
                            <div class="flex-1 min-w-40">
                                <input type="text" wire:model="newStopName" wire:keydown.enter="addStop"
                                       placeholder="Stop name" class="w-full rounded-lg border-gray-300 text-sm shadow-sm">
                                @error('newStopName') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="w-28">
                                <input type="number" step="any" wire:model="newStopLat"
                                       placeholder="Lat" class="w-full rounded-lg border-gray-300 text-sm shadow-sm">
                                @error('newStopLat') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <div class="w-28">
                                <input type="number" step="any" wire:model="newStopLng"
                                       placeholder="Lng" class="w-full rounded-lg border-gray-300 text-sm shadow-sm">
                                @error('newStopLng') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                            </div>
                            <button wire:click="addStop" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">Add</button>
                        </div>
                        <p class="text-xs text-gray-400">Latitude/longitude are optional — add them to show the stop on the map.</p>
                    </div>
                @endcan

                <div class="mt-6 flex justify-end">
                    <button wire:click="closeStops" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">Done</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Map panel — route + stops on a Google Map (graceful fallback without a key) --}}
    @if ($mapRoute)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-2xl rounded-xl bg-white p-6 shadow-xl">
                <div class="mb-4 flex items-center justify-between">
                    <h2 class="text-lg font-semibold">Map — {{ $mapRoute->code }} ({{ $mapRoute->name }})</h2>
                    <button wire:click="closeMap" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                @if (! $mapsKey)
                    <div class="rounded-lg bg-amber-50 px-4 py-6 text-center text-sm text-amber-700">
                        Add a <code class="font-mono">GOOGLE_MAPS_API_KEY</code> to your <code class="font-mono">.env</code> to enable the map.
                    </div>
                @elseif ($mapStops->isEmpty())
                    <div class="rounded-lg bg-gray-50 px-4 py-6 text-center text-sm text-gray-500">
                        No stops have coordinates yet. Add latitude/longitude to this route's stops to see them on the map.
                    </div>
                @else
                    <div wire:ignore wire:key="map-{{ $mapRoute->id }}"
                         x-data="routeMap(@js($mapStops->map(fn ($s) => ['name' => $s->name, 'seq' => $s->sequence, 'lat' => (float) $s->latitude, 'lng' => (float) $s->longitude])->values()), @js($mapsKey))"
                         x-init="load()">
                        <div x-ref="map" class="h-80 w-full rounded-lg border border-gray-200 bg-gray-50"></div>
                    </div>
                @endif

                <div class="mt-6 flex justify-end">
                    <button wire:click="closeMap" class="rounded-lg px-4 py-2 text-sm text-gray-600 hover:bg-gray-100">Close</button>
                </div>
            </div>
        </div>
    @endif

    @script
    <script>
        Alpine.data('routeMap', (stops, apiKey) => ({
            stops,
            apiKey,
            load() {
                if (window.google && window.google.maps) { this.init(); return; }
                if (! document.getElementById('gmaps-sdk')) {
                    const s = document.createElement('script');
                    s.id = 'gmaps-sdk';
                    s.src = `https://maps.googleapis.com/maps/api/js?key=${this.apiKey}`;
                    s.async = true;
                    s.onload = () => window.dispatchEvent(new CustomEvent('gmaps-ready'));
                    document.head.appendChild(s);
                }
                window.addEventListener('gmaps-ready', () => this.init(), { once: true });
            },
            init() {
                if (! this.$refs.map || ! window.google) return;
                const map = new google.maps.Map(this.$refs.map, { zoom: 8, mapTypeControl: false });
                const bounds = new google.maps.LatLngBounds();
                const path = [];
                this.stops.forEach((stop) => {
                    const pos = { lat: stop.lat, lng: stop.lng };
                    new google.maps.Marker({ position: pos, map, label: String(stop.seq), title: stop.name });
                    path.push(pos);
                    bounds.extend(pos);
                });
                new google.maps.Polyline({ path, map, strokeColor: '#4f46e5', strokeWeight: 3 });
                map.fitBounds(bounds);
            },
        }));
    </script>
    @endscript
</div>
