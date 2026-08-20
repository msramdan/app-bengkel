<?php

namespace App\Http\Controllers;

use App\Models\Technician;
use App\Services\FinancialReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class FinancialReportController extends Controller
{
    public function __construct(private FinancialReportService $reportService)
    {
        $this->middleware('permission:financial report view')->only([
            'index',
            'exportPdf',
            'technicianCommissions',
            'exportTechnicianCommissionsPdf',
        ]);
    }

    public function index(Request $request): View
    {
        [$from, $to] = $this->resolvePeriod($request);
        $report = $this->reportService->build($from, $to);

        return view('financial-reports.index', [
            'report' => $report,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ]);
    }

    public function technicianCommissions(Request $request, Technician $technician): JsonResponse
    {
        [$from, $to] = $this->resolvePeriod($request);

        return response()->json([
            'data' => $this->reportService->technicianCommissionDetails($technician->id, $from, $to),
        ]);
    }

    public function exportTechnicianCommissionsPdf(Request $request, Technician $technician): Response
    {
        [$from, $to] = $this->resolvePeriod($request);
        $detail = $this->reportService->technicianCommissionDetails($technician->id, $from, $to);

        $pdf = Pdf::loadView('financial-reports.technician-commissions-pdf', [
            'detail' => $detail,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ])->setPaper('a4', 'portrait');

        $slug = str($detail['technician_name'])->slug('-')->toString() ?: 'teknisi';
        $filename = 'rincian-komisi-'.$slug.'-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf';

        return $pdf->download($filename);
    }

    public function exportPdf(Request $request): Response
    {
        [$from, $to] = $this->resolvePeriod($request);
        $report = $this->reportService->build($from, $to);

        $pdf = Pdf::loadView('financial-reports.pdf', [
            'report' => $report,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
        ])->setPaper('a4', 'portrait');

        $filename = 'laporan-keuangan-'.$from->format('Ymd').'-'.$to->format('Ymd').'.pdf';

        return $pdf->download($filename);
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function resolvePeriod(Request $request): array
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $from = Carbon::parse($validated['from'] ?? now()->startOfMonth()->toDateString());
        $to = Carbon::parse($validated['to'] ?? now()->toDateString());

        if ($from->gt($to)) {
            [$from, $to] = [$to, $from];
        }

        return [$from, $to];
    }
}
