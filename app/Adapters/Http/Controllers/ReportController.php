<?php

namespace App\Adapters\Http\Controllers;

use App\Application\Services\MostBorrowedReportService;
use Illuminate\Http\Request;

final class ReportController
{
    /** GET /reports/most-borrowed?from=YYYY-MM-DD&to=YYYY-MM-DD (admin) */
    public function mostBorrowed(Request $request, MostBorrowedReportService $report)
    {
        $data = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d'],
        ]);

        return response()->json($report->execute($data['from'], $data['to']));
    }
}
