<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;

/** Read-only, filterable view of the activity log. Admin only; no create/edit/delete. */
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
