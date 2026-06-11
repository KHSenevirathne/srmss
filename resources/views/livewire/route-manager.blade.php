<div class="page">
    <div class="page-header">
        <div>
            <h1 class="page-title">Routes</h1>
            <p class="page-sub">Bus routes with ordered stops, distances and map preview.</p>
        </div>
        @can('manage-routes')
            <button wire:click="create" class="btn-primary">+ Add Route</button>
        @endcan
    </div>

    @if (session('status'))
        <div class="flash">{{ session('status') }}</div>
    @endif

    <div class="filter-bar">
        <div class="w-full sm:w-64">
            <label class="label">Search</label>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Code or name…" class="input">
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>From → To</th>
                        <th class="th-num">Distance</th>
                        <th>Service</th>
                        <th class="th-num">Stops</th>
                        <th>Status</th>
                        <th class="th-actions">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($routes as $route)
                        <tr wire:key="route-{{ $route->id }}">
                            <td class="td-strong">{{ $route->code }}</td>
                            <td>{{ $route->name }}</td>
                            <td>{{ $route->start_point }} → {{ $route->end_point }}</td>
                            <td class="td-num">{{ number_format($route->total_distance_km, 1) }} km</td>
                            <td class="capitalize">{{ str_replace('-', ' ', $route->service_type) }}</td>
                            <td class="td-num">{{ $route->stops_count }}</td>
                            <td>
                                <span class="badge {{ $route->status === 'active' ? 'badge-green' : 'badge-zinc' }}">{{ $route->status }}</span>
                            </td>
                            <td class="td-actions">
                                <button wire:click="manageStops({{ $route->id }})" class="link-muted">Stops</button>
                                <button wire:click="viewMap({{ $route->id }})" class="link-muted">Map</button>
                                @can('manage-routes')
                                    <button wire:click="edit({{ $route->id }})" class="link-action">Edit</button>
                                    <button wire:click="delete({{ $route->id }})"
                                            wire:confirm="Delete this route and its stops?"
                                            class="link-danger">Delete</button>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="empty">No routes yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($routes->hasPages())<div class="table-foot">{{ $routes->links() }}</div>@endif
    </div>

    {{-- Route create / edit modal --}}
    @if ($showModal)
        <div class="modal-backdrop">
            <div class="modal-panel">
                <div class="modal-head">
                    <h2 class="modal-title">{{ $editingId ? 'Edit' : 'Add' }} Route</h2>
                    <button wire:click="$set('showModal', false)" class="modal-close">✕</button>
                </div>
                <div class="modal-body">
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Code</label>
                            <input type="text" wire:model="code" class="input">
                            @error('code') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Name</label>
                            <input type="text" wire:model="name" class="input">
                            @error('name') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="form-grid-2">
                        <div>
                            <label class="label">Start Point</label>
                            <input type="text" wire:model="start_point" class="input">
                            @error('start_point') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">End Point</label>
                            <input type="text" wire:model="end_point" class="input">
                            @error('end_point') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="form-grid-3">
                        <div>
                            <label class="label">Distance (km)</label>
                            <input type="number" step="0.1" wire:model="total_distance_km" class="input">
                            @error('total_distance_km') <p class="error-text">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="label">Service</label>
                            <select wire:model="service_type" class="input">
                                <option value="normal">Normal</option>
                                <option value="semi-luxury">Semi-luxury</option>
                                <option value="luxury">Luxury</option>
                            </select>
                        </div>
                        <div>
                            <label class="label">Status</label>
                            <select wire:model="status" class="input">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-foot">
                    <button wire:click="$set('showModal', false)" class="btn-ghost">Cancel</button>
                    <button wire:click="save" class="btn-primary">Save</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Stops management modal --}}
    @if ($managingRoute)
        <div class="modal-backdrop">
            <div class="modal-panel modal-lg">
                <div class="modal-head">
                    <h2 class="modal-title">Stops — {{ $managingRoute->code }} ({{ $managingRoute->name }})</h2>
                    <button wire:click="closeStops" class="modal-close">✕</button>
                </div>
                <div class="modal-body">
                    <ol class="divide-y divide-zinc-100 rounded-lg border border-zinc-200 dark:divide-zinc-800 dark:border-zinc-700">
                        @forelse ($stops as $stop)
                            <li wire:key="stop-{{ $stop->id }}" class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm">
                                <span class="flex min-w-0 items-center gap-3 text-zinc-700 dark:text-zinc-200">
                                    <span class="flex size-6 shrink-0 items-center justify-center rounded-full bg-indigo-50 font-mono text-xs text-indigo-600 dark:bg-indigo-900/40 dark:text-indigo-300">{{ $stop->sequence }}</span>
                                    <span class="truncate">{{ $stop->name }}</span>
                                </span>
                                @can('manage-routes')
                                    <span class="flex shrink-0 items-center gap-2">
                                        <button wire:click="moveStopUp({{ $stop->id }})" @disabled($loop->first)
                                                class="link-muted disabled:cursor-default disabled:opacity-30">↑</button>
                                        <button wire:click="moveStopDown({{ $stop->id }})" @disabled($loop->last)
                                                class="link-muted disabled:cursor-default disabled:opacity-30">↓</button>
                                        <button wire:click="removeStop({{ $stop->id }})" class="link-danger text-xs">Remove</button>
                                    </span>
                                @endcan
                            </li>
                        @empty
                            <li class="px-4 py-8 text-center text-sm text-zinc-400 dark:text-zinc-500">No stops yet.</li>
                        @endforelse
                    </ol>

                    @can('manage-routes')
                        <div>
                            <label class="label">Add a stop</label>
                            <div class="flex flex-wrap items-start gap-2">
                                <div class="min-w-40 flex-1">
                                    <input type="text" wire:model="newStopName" wire:keydown.enter="addStop"
                                           placeholder="Stop name" class="input">
                                    @error('newStopName') <p class="error-text">{{ $message }}</p> @enderror
                                </div>
                                <div class="w-26">
                                    <input type="number" step="any" wire:model="newStopLat" placeholder="Lat" class="input">
                                    @error('newStopLat') <p class="error-text">{{ $message }}</p> @enderror
                                </div>
                                <div class="w-26">
                                    <input type="number" step="any" wire:model="newStopLng" placeholder="Lng" class="input">
                                    @error('newStopLng') <p class="error-text">{{ $message }}</p> @enderror
                                </div>
                                <button wire:click="addStop" class="btn-primary">Add</button>
                            </div>
                            <p class="mt-2 text-xs text-zinc-400 dark:text-zinc-500">Latitude/longitude are optional — add them to show the stop on the map.</p>
                        </div>
                    @endcan
                </div>
                <div class="modal-foot">
                    <button wire:click="closeStops" class="btn-ghost">Done</button>
                </div>
            </div>
        </div>
    @endif

    {{-- Map modal — route + stops on a Google Map (graceful fallback without a key) --}}
    @if ($mapRoute)
        <div class="modal-backdrop">
            <div class="modal-panel modal-xl">
                <div class="modal-head">
                    <h2 class="modal-title">Map — {{ $mapRoute->code }} ({{ $mapRoute->name }})</h2>
                    <button wire:click="closeMap" class="modal-close">✕</button>
                </div>
                <div class="modal-body">
                    @if (! $mapsKey)
                        <div class="callout-warn text-center">
                            Add a <code class="font-mono">GOOGLE_MAPS_API_KEY</code> to your <code class="font-mono">.env</code> to enable the map.
                        </div>
                    @elseif ($mapStops->isEmpty())
                        <div class="callout-muted">
                            No stops have coordinates yet. Add latitude/longitude to this route's stops to see them on the map.
                        </div>
                    @else
                        <div wire:ignore wire:key="map-{{ $mapRoute->id }}"
                             x-data="routeMap(@js($mapStops->map(fn ($s) => ['name' => $s->name, 'seq' => $s->sequence, 'lat' => (float) $s->latitude, 'lng' => (float) $s->longitude])->values()), @js($mapsKey))"
                             x-init="load()">
                            <div x-ref="map" class="h-80 w-full rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800"></div>
                        </div>
                    @endif
                </div>
                <div class="modal-foot">
                    <button wire:click="closeMap" class="btn-ghost">Close</button>
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
