<div class="page">
    <div class="page-header">
        <div class="page-heading">
            <span class="icon-chip icon-chip-zinc"><flux:icon.clipboard-document-list /></span>
            <div>
                <h1 class="page-title">Activity Log</h1>
                <p class="page-sub">Audit trail of critical user activity : read-only and append-only.</p>
            </div>
        </div>
    </div>

    <div class="filter-bar">
        <div class="w-full sm:w-52">
            <label class="label">User</label>
            <select wire:model.live="filterUserId" class="input">
                <option value="">All users</option>
                @foreach ($users as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="w-full sm:w-44">
            <label class="label">Event</label>
            <select wire:model.live="filterEvent" class="input">
                <option value="">All events</option>
                <option value="created">Created</option>
                <option value="updated">Updated</option>
                <option value="deleted">Deleted</option>
                <option value="login">Login</option>
                <option value="logout">Logout</option>
            </select>
        </div>
    </div>

    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th>User</th>
                        <th>Event</th>
                        <th>Subject</th>
                        <th>Description</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr wire:key="log-{{ $log->id }}">
                            <td class="tabular-nums">{{ $log->created_at->format('Y-m-d H:i') }}</td>
                            <td class="td-strong">{{ $log->user?->name ?? 'System' }}</td>
                            <td>
                                @php
                                    $badge = match ($log->event) {
                                        'created' => 'badge-green',
                                        'updated' => 'badge-blue',
                                        'deleted' => 'badge-red',
                                        default => 'badge-zinc',
                                    };
                                @endphp
                                <span class="badge {{ $badge }}">{{ $log->event }}</span>
                            </td>
                            <td>{{ $log->subject_type ? $log->subject_type . ' #' . $log->subject_id : '-' }}</td>
                            <td class="whitespace-normal">{{ $log->description }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty">No activity recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="table-foot">{{ $logs->links() }}</div>
        @endif
    </div>
</div>
