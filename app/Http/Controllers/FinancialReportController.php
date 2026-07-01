<?php

namespace App\Http\Controllers;

use App\Services\FinancialReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function __construct(private FinancialReportService $reportService)
    {
        $this->middleware('permission:financial report view')->only('index');
    }

    public function index(Request $request): View
    {
        $from = Carbon::parse($request->input('from', now()->startOfMonth()->toDateString()));
        $to = Carbon::parse($request->input('to', now()->toDateString()));

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        $report = $this->reportService->build($from, $to);

        return view('financial-reports.index', [
            'report' => $report,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }
}
