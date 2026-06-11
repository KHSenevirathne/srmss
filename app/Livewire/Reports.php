<?php

namespace App\Livewire;

use App\Services\ReportService;
use Illuminate\Support\Carbon;
use Livewire\Component;

/**
 * Reports & analytics screen (Phase 5). Shows the four ReportService reports for
 * a chosen date range (tables + Chart.js charts) and links to the PDF export,
 * which renders the same data. Guarded by `view-reports`.
 */
class Reports extends Component
{
    public string $from = '';
    public string $to = '';

    public function mount(): void
    {
        $this->from = now()->subDays(30)->toDateString();
        $this->to = now()->toDateString();
    }

    public function render()
    {
        $from = Carbon::parse($this->from ?: now()->subDays(30)->toDateString());
        $to = Carbon::parse($this->to ?: now()->toDateString());

        $data = app(ReportService::class)->all($from, $to);

        return view('livewire.reports', array_merge($data, [
            'from' => $this->from,
            'to'   => $this->to,
        ]));
    }
}
