<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800">Fuel Logs</h1>
        <button wire:click="create"
                class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
            + Add Fuel Log
        </button>
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    {{-- Filters: vehicle + date range --}}
    <div class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs font-medium text-gray-500">Vehicle</label>
            <select wire:model.live="filterVehicleId" class="mt-1 rounded-lg border-gray-300 text-sm shadow-sm">
                <option value="">All vehicles</option>
                @foreach ($vehicles as $vehicle)
                    <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500">From</label>
            <input type="date" wire:model.live="dateFrom" class="mt-1 rounded-lg border-gray-300 text-sm shadow-sm">
        </div>
        <div>
            <label class="block text-xs font-medium text-gray-500">To</label>
            <input type="date" wire:model.live="dateTo" class="mt-1 rounded-lg border-gray-300 text-sm shadow-sm">
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Date</th>
                    <th class="px-4 py-3">Vehicle</th>
                    <th class="px-4 py-3">Litres</th>
                    <th class="px-4 py-3">Cost</th>
                    <th class="px-4 py-3">Odometer</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($logs as $log)
                    <tr wire:key="fuel-{{ $log->id }}">
                        <td class="px-4 py-3">{{ $log->logged_at->format('Y-m-d') }}</td>
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $log->vehicle?->registration_number ?? '—' }}</td>
                        <td class="px-4 py-3">{{ number_format($log->liters, 2) }} L</td>
                        <td class="px-4 py-3">{{ number_format($log->cost, 2) }}</td>
                        <td class="px-4 py-3">{{ $log->odometer !== null ? number_format($log->odometer) . ' km' : '—' }}</td>
                        <td class="px-4 py-3 text-right space-x-2">
                            <button wire:click="edit({{ $log->id }})" class="text-indigo-600 hover:underline">Edit</button>
                            <button wire:click="delete({{ $log->id }})"
                                    wire:confirm="Delete this fuel log?"
                                    class="text-red-600 hover:underline">Delete</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No fuel logs found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $logs->links() }}</div>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold">{{ $editingId ? 'Edit' : 'Add' }} Fuel Log</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Vehicle</label>
                        <select wire:model="vehicle_id" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="">Select a vehicle…</option>
                            @foreach ($vehicles as $vehicle)
                                <option value="{{ $vehicle->id }}">{{ $vehicle->registration_number }}</option>
                            @endforeach
                        </select>
                        @error('vehicle_id') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Litres</label>
                            <input type="number" step="0.01" wire:model="liters" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('liters') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Cost</label>
                            <input type="number" step="0.01" wire:model="cost" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('cost') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Odometer (km)</label>
                            <input type="number" wire:model="odometer" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('odometer') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Date</label>
                            <input type="date" wire:model="logged_at" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('logged_at') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
</div>
