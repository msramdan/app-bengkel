<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\Purchase;
use App\Models\Technician;
use App\Models\Transaction;
use App\Support\PaymentMethodResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
    /**
     * @return array{
     *   period: array{from: string, to: string},
     *   sales: array<string, float|int>,
     *   commissions: Collection,
     *   purchases: array<string, float|int>,
     *   profit: array<string, float>,
     *   payment_sources: array{
     *     inflows: array<string, mixed>,
     *     outflows: array<string, mixed>
     *   }
     * }
     */
    public function build(Carbon $from, Carbon $to): array
    {
        $fromStart = $from->copy()->startOfDay();
        $toEnd = $to->copy()->endOfDay();

        $salesQuery = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$fromStart, $toEnd]);

        $sales = [
            'transaction_count' => (int) (clone $salesQuery)->count(),
            'gross' => (float) (clone $salesQuery)->selectRaw('COALESCE(SUM(subtotal_items + subtotal_services), 0) as v')->value('v'),
            'discount' => (float) (clone $salesQuery)->sum('discount'),
            'revenue' => (float) (clone $salesQuery)->sum('total'),
            'items_revenue' => (float) (clone $salesQuery)->sum('subtotal_items'),
            'services_revenue' => (float) (clone $salesQuery)->sum('subtotal_services'),
            'technician_commission' => (float) (clone $salesQuery)->sum('technician_commission'),
            'owner_share' => (float) (clone $salesQuery)->sum('owner_total_share'),
            'owner_items_share' => (float) (clone $salesQuery)->sum('owner_items_share'),
            'owner_service_share' => (float) (clone $salesQuery)->sum('owner_service_share'),
        ];

        $commissions = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$fromStart, $toEnd])
            ->where('technician_commission', '>', 0)
            ->select([
                'technician_id',
                DB::raw('COUNT(*) as transaction_count'),
                DB::raw('COALESCE(SUM(subtotal_services), 0) as services_total'),
                DB::raw('COALESCE(SUM(technician_commission), 0) as commission_total'),
            ])
            ->groupBy('technician_id')
            ->get()
            ->map(function ($row) {
                $technician = Technician::find($row->technician_id);

                return [
                    'technician_id' => $row->technician_id,
                    'technician_name' => $technician?->name ?? '-',
                    'transaction_count' => (int) $row->transaction_count,
                    'services_total' => (float) $row->services_total,
                    'commission_total' => (float) $row->commission_total,
                ];
            });

        $purchaseQuery = Purchase::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$fromStart, $toEnd]);

        $purchases = [
            'purchase_count' => (int) (clone $purchaseQuery)->count(),
            'expense' => (float) (clone $purchaseQuery)->sum('total'),
            'discount' => (float) (clone $purchaseQuery)->sum('discount'),
        ];

        $ownerNetEstimate = round($sales['owner_share'] - $purchases['expense'], 2);
        $cashFlowEstimate = round($sales['revenue'] - $purchases['expense'] - $sales['technician_commission'], 2);

        return [
            'period' => [
                'from' => $fromStart->toDateString(),
                'to' => $toEnd->toDateString(),
            ],
            'sales' => $sales,
            'commissions' => $commissions,
            'purchases' => $purchases,
            'profit' => [
                'owner_net_estimate' => $ownerNetEstimate,
                'cash_flow_estimate' => $cashFlowEstimate,
            ],
            'payment_sources' => [
                'inflows' => $this->buildPaymentBreakdown(Transaction::class, $fromStart, $toEnd, true),
                'outflows' => $this->buildPaymentBreakdown(Purchase::class, $fromStart, $toEnd, false),
            ],
        ];
    }

    /**
     * @param  class-string<Transaction|Purchase>  $modelClass
     * @return array{cash: float, qris: float, transfer: array<int, array<string, mixed>>, total: float}
     */
    private function buildPaymentBreakdown(string $modelClass, Carbon $fromStart, Carbon $toEnd, bool $includeQris = true): array
    {
        $baseQuery = $modelClass::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$fromStart, $toEnd]);

        $cash = (float) (clone $baseQuery)->where('payment_method', 'cash')->sum('total');
        $qris = $includeQris
            ? (float) (clone $baseQuery)->where('payment_method', 'qris')->sum('total')
            : 0.0;

        $transferRows = (clone $baseQuery)
            ->where('payment_method', 'transfer')
            ->select([
                'bank_account_id',
                DB::raw('COALESCE(SUM(total), 0) as amount'),
                DB::raw('COUNT(*) as transaction_count'),
            ])
            ->groupBy('bank_account_id')
            ->get();

        $bankIds = $transferRows->pluck('bank_account_id')->filter()->unique();
        $banks = BankAccount::query()->whereIn('id', $bankIds)->get()->keyBy('id');

        $transfer = $transferRows->map(function ($row) use ($banks) {
            $bank = $banks->get($row->bank_account_id);

            return [
                'bank_account_id' => $row->bank_account_id,
                'bank_label' => $bank?->displayLabel() ?? 'Akun tidak ditemukan',
                'bank_name' => $bank?->bank_name ?? '-',
                'amount' => (float) $row->amount,
                'count' => (int) $row->transaction_count,
            ];
        })->values()->all();

        $transferTotal = array_sum(array_column($transfer, 'amount'));

        return [
            'cash' => $cash,
            'qris' => $qris,
            'transfer' => $transfer,
            'transfer_total' => $transferTotal,
            'total' => round($cash + $qris + $transferTotal, 2),
            'include_qris' => $includeQris,
            'labels' => [
                'cash' => PaymentMethodResolver::label('cash'),
                'qris' => PaymentMethodResolver::label('qris'),
                'transfer' => PaymentMethodResolver::label('transfer'),
            ],
        ];
    }
}
