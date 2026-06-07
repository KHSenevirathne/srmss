<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800">Vehicles</h1>
        @can('manage-fleet')
            <button wire:click="create"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                + Add Vehicle
            </button>
        @endcan
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search reg no or type…"
           class="w-full max-w-sm rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Reg No</th>
                    <th class="px-4 py-3">Type</th>
                    <th class="px-4 py-3">Capacity</th>
                    <th class="px-4 py-3">Mileage</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($vehicles as $vehicle)
                    <tr wire:key="vehicle-{{ $vehicle->id }}">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $vehicle->registration_number }}</td>
                        <td class="px-4 py-3 capitalize">{{ $vehicle->type }}</td>
                        <td class="px-4 py-3">{{ $vehicle->seating_capacity }}</td>
                        <td class="px-4 py-3">{{ number_format($vehicle->mileage) }} km</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs capitalize">
                                {{ str_replace('_', ' ', $vehicle->status) }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @can('manage-fleet')
                                <button wire:click="edit({{ $vehicle->id }})" class="text-indigo-600 hover:underline">Edit</button>
                                <button wire:click="delete({{ $vehicle->id }})"
                                        wire:confirm="Delete this vehicle?"
                                        class="text-red-600 hover:underline">Delete</button>
                            @else
                                <span class="text-xs text-gray-400">View only</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="px-4 py-6 text-center text-gray-400">No vehicles yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $vehicles->links() }}</div>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold">{{ $editingId ? 'Edit' : 'Add' }} Vehicle</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Registration No</label>
                        <input type="text" wire:model="registration_number" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        @error('registration_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select wire:model="type" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="bus">Bus</option>
                            <option value="minibus">Minibus</option>
                            <option value="coach">Coach</option>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Capacity</label>
                            <input type="number" wire:model="seating_capacity" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('seating_capacity') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Mileage (km)</label>
                            <input type="number" wire:model="mileage" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select wire:model="status" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            <option value="available">Available</option>
                            <option value="in_service">In service</option>
                            <option value="maintenance">Maintenance</option>
                        </select>
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
