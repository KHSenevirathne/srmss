<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/** Streams the operational report as a PDF, using the same data as the on-screen Reports view. */
class ReportPdfController extends Controller
{
    public function download(Request $request, ReportService $reports)
    {
        $from = Carbon::parse($request->query('from', now()->subDays(30)->toDateString()))->startOfDay();
        $to = Carbon::parse($request->query('to', now()->toDateString()))->endOfDay();

        $data = array_merge($reports->all($from, $to), [
            'from'        => $from->toDateString(),
            'to'          => $to->toDateString(),
            'generatedAt' => now()->format('Y-m-d H:i'),
        ]);

        $pdf = Pdf::loadView('reports.pdf', $data)->setPaper('a4');

        return $pdf->download('srmss-report-' . $from->toDateString() . '-to-' . $to->toDateString() . '.pdf');
    }
}
