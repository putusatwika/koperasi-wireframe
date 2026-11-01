<?php

namespace App\Http\Controllers\Reports;

use App\Exports\LabaRugiExport;
use App\Http\Controllers\Controller;
use App\Services\Accounting\AccountingReportService;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class LabaRugiExcelController extends Controller
{
    public function __invoke(Request $request, AccountingReportService $service): BinaryFileResponse
    {
        abort_unless($request->user()?->can('report.laba_rugi.view') ?? false, 403);

        $validated = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after_or_equal:from'],
        ]);

        $from = CarbonImmutable::parse($validated['from']);
        $to = CarbonImmutable::parse($validated['to']);

        return Excel::download(
            new LabaRugiExport($service->labaRugi($from, $to)),
            "laporan-laba-rugi-{$from->toDateString()}_{$to->toDateString()}.xlsx",
        );
    }
}
