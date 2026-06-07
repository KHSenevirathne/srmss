<div class="space-y-4">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-semibold text-gray-800">Drivers</h1>
        @can('manage-fleet')
            <button wire:click="create"
                    class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700">
                + Add Driver
            </button>
        @endcan
    </div>

    @if (session('status'))
        <div class="rounded-lg bg-green-50 px-4 py-2 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <input type="text" wire:model.live.debounce.300ms="search" placeholder="Search name or licence no…"
           class="w-full max-w-sm rounded-lg border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">

    <div class="overflow-x-auto rounded-lg border border-gray-200 bg-white">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50 text-left text-xs uppercase tracking-wide text-gray-500">
                <tr>
                    <th class="px-4 py-3">Name</th>
                    <th class="px-4 py-3">Phone</th>
                    <th class="px-4 py-3">Licence No</th>
                    <th class="px-4 py-3">Licence Expiry</th>
                    <th class="px-4 py-3">Weekly Hrs</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($drivers as $driver)
                    <tr wire:key="driver-{{ $driver->id }}">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $driver->name }}</td>
                        <td class="px-4 py-3">{{ $driver->phone ?: '—' }}</td>
                        <td class="px-4 py-3">{{ $driver->license_number }}</td>
                        <td class="px-4 py-3">
                            {{ $driver->license_expiry?->format('Y-m-d') }}
                            @if ($driver->licenseExpiringSoon())
                                <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-700">expiring soon</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $driver->weekly_hours }}</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full bg-gray-100 px-2 py-1 text-xs capitalize">
                                {{ $driver->status }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-2">
                            @can('manage-fleet')
                                <button wire:click="edit({{ $driver->id }})" class="text-indigo-600 hover:underline">Edit</button>
                                <button wire:click="delete({{ $driver->id }})"
                                        wire:confirm="Delete this driver?"
                                        class="text-red-600 hover:underline">Delete</button>
                            @else
                                <span class="text-xs text-gray-400">View only</span>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="px-4 py-6 text-center text-gray-400">No drivers yet.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div>{{ $drivers->links() }}</div>

    {{-- Modal --}}
    @if ($showModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4">
            <div class="w-full max-w-md rounded-xl bg-white p-6 shadow-xl">
                <h2 class="mb-4 text-lg font-semibold">{{ $editingId ? 'Edit' : 'Add' }} Driver</h2>
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Name</label>
                        <input type="text" wire:model="name" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Phone</label>
                            <input type="text" wire:model="phone" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('phone') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Email</label>
                            <input type="email" wire:model="email" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Address</label>
                        <input type="text" wire:model="address" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                        @error('address') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Licence No</label>
                            <input type="text" wire:model="license_number" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('license_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Licence Expiry</label>
                            <input type="date" wire:model="license_expiry" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('license_expiry') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Weekly Hours</label>
                            <input type="number" wire:model="weekly_hours" class="mt-1 w-full rounded-lg border-gray-300 text-sm shadow-sm">
                            @error('weekly_hours') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
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
</div>
