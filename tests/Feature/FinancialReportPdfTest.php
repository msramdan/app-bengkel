<?php

namespace Tests\Feature;

use App\Pdf\SafeDompdf;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FinancialReportPdfTest extends TestCase
{
    #[Test]
    public function financial_report_pdf_loads_when_html5_parser_throws(): void
    {
        $dompdf = app('dompdf');
        $this->assertInstanceOf(SafeDompdf::class, $dompdf);

        $html = view('financial-reports.pdf', [
            'report' => $this->report(),
            'from' => '2026-10-01',
            'to' => '2026-10-15',
        ])->render();

        $loaded = Pdf::loadHTML($html)->setPaper('a4', 'portrait');
        $this->assertStringContainsString('Laporan Keuangan', $loaded->getDom()->textContent);
        $this->assertStringContainsString('Listrik & Utilitas', $loaded->getDom()->textContent);

        $fallback = app('dompdf');
        $this->assertInstanceOf(SafeDompdf::class, $fallback);
        $fallback->loadHtmlUsingLibxml($html);

        $this->assertStringContainsString('Listrik & Utilitas', $fallback->getDom()->textContent);
        $this->assertStringStartsWith('%PDF', $fallback->output());
    }

    /**
     * @return array<string, mixed>
     */
    private function report(): array
    {
        $categories = collect([
            ['category_name' => 'Listrik & Utilitas', 'entry_count' => 1, 'amount_total' => 10000],
        ]);

        return [
            'sales' => [
                'revenue' => 100000,
                'transaction_count' => 2,
                'items_revenue' => 60000,
                'services_revenue' => 50000,
                'gross' => 110000,
                'discount' => 10000,
                'technician_commission' => 15000,
                'owner_service_share' => 35000,
                'items_cost' => 40000,
                'owner_items_share' => 20000,
                'owner_share' => 55000,
            ],
            'purchases' => [
                'expense' => 20000,
                'purchase_count' => 1,
            ],
            'manual_income' => [
                'amount' => 5000,
                'entry_count' => 1,
                'by_category' => $categories,
            ],
            'manual_expense' => [
                'amount' => 3000,
                'entry_count' => 1,
                'by_category' => new Collection,
            ],
            'totals' => [
                'inflow' => 105000,
                'operating_outflow' => 38000,
            ],
            'profit' => [
                'owner_net_estimate' => 57000,
                'cash_flow_estimate' => 67000,
            ],
            'commissions' => collect([
                [
                    'technician_name' => 'Budi',
                    'transaction_count' => 2,
                    'services_total' => 50000,
                    'commission_total' => 15000,
                ],
            ]),
            'payment_sources' => [
                'inflows' => [
                    'cash' => 50000,
                    'qris' => 30000,
                    'transfer_total' => 25000,
                    'total' => 105000,
                    'labels' => ['cash' => 'Tunai', 'qris' => 'QRIS', 'transfer' => 'Transfer'],
                    'transfer' => [
                        ['bank_label' => 'BCA — Operasional', 'count' => 1, 'amount' => 25000],
                    ],
                ],
                'outflows' => [
                    'cash' => 10000,
                    'transfer_total' => 13000,
                    'total' => 23000,
                    'labels' => ['cash' => 'Tunai', 'transfer' => 'Transfer'],
                    'transfer' => [],
                ],
            ],
        ];
    }
}
