<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Audit-trail viewer (HR-02) : a read-only window onto the activity_logs table,
 * filterable by user and event. Admin only (guarded by `manage-users` on the
 * route). There is deliberately no create/edit/delete here: the log is an
 * append-only record written automatically elsewhere.
 */
class ActivityLogViewer extends Component
{
    use WithPagination;

    public string $filterUserId = '';
    public string $filterEvent = '';

    public function updatingFilterUserId(): void
    {
        $this->resetPage();
    }

    public function updatingFilterEvent(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $logs = ActivityLog::query()
            ->with('user')
            ->when($this->filterUserId, fn ($q) => $q->where('user_id', $this->filterUserId))
            ->when($this->filterEvent, fn ($q) => $q->where('event', $this->filterEvent))
            ->latest()
            ->paginate(15);

        return view('livewire.activity-log-viewer', [
            'logs'  => $logs,
            'users' => User::orderBy('name')->get(),
        ]);
    }
}
