<?php

namespace App\Services;

use App\Models\BankAccount;
use App\Models\ManualCashEntry;
use App\Models\Purchase;
use App\Models\Technician;
use App\Models\Transaction;
use App\Support\PaymentMethodResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FinancialReportService
{
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
      'items_cost' => (float) DB::table('transaction_items')
        ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
        ->where('transactions.status', 'completed')
        ->whereBetween('transactions.created_at', [$fromStart, $toEnd])
        ->selectRaw('COALESCE(SUM(transaction_items.unit_cost * transaction_items.quantity), 0) as v')
        ->value('v'),
    ];

    $sales['items_margin'] = round($sales['items_revenue'] - $sales['items_cost'], 2);

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
          'technician_id' => $row->technician_id ? (int) $row->technician_id : null,
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

    $manualIncome = $this->buildManualSummary('income', $fromStart, $toEnd);
    $manualExpense = $this->buildManualSummary('expense', $fromStart, $toEnd);

    $totalInflow = round($sales['revenue'] + $manualIncome['amount'], 2);
    $totalOperatingOutflow = round($purchases['expense'] + $manualExpense['amount'] + $sales['technician_commission'], 2);

    // Arus kas kasar (tetap ditampilkan sebagai info).
    $cashFlowEstimate = round($totalInflow - $totalOperatingOutflow, 2);

    // Laba owner: margin sparepart (jual−beli) + bagian jasa + manual − pengeluaran manual.
    // Pembelian stok tidak dikurangi lagi di sini agar tidak double-count dengan HPP.
    $ownerNetEstimate = round(
      $sales['owner_share'] + $manualIncome['amount'] - $manualExpense['amount'],
      2
    );

    $transactionInflows = $this->buildPaymentBreakdown(Transaction::class, $fromStart, $toEnd, true, 'total', 'created_at');
    $manualInflows = $this->buildManualPaymentBreakdown('income', $fromStart, $toEnd, true);
    $purchaseOutflows = $this->buildPaymentBreakdown(Purchase::class, $fromStart, $toEnd, false, 'total', 'created_at');
    $manualOutflows = $this->buildManualPaymentBreakdown('expense', $fromStart, $toEnd, false);

    return [
      'period' => [
        'from' => $fromStart->toDateString(),
        'to' => $toEnd->toDateString(),
      ],
      'sales' => $sales,
      'manual_income' => $manualIncome,
      'manual_expense' => $manualExpense,
      'commissions' => $commissions,
      'purchases' => $purchases,
      'totals' => [
        'inflow' => $totalInflow,
        'operating_outflow' => $totalOperatingOutflow,
      ],
      'profit' => [
        'owner_net_estimate' => $ownerNetEstimate,
        'cash_flow_estimate' => $cashFlowEstimate,
      ],
      'payment_sources' => [
        'inflows' => $this->mergePaymentBreakdowns($transactionInflows, $manualInflows),
        'outflows' => $this->mergePaymentBreakdowns($purchaseOutflows, $manualOutflows),
      ],
    ];
  }

  /**
   * @return array{
   *   technician_id: int,
   *   technician_name: string,
   *   period: array{from: string, to: string},
   *   transaction_count: int,
   *   services_total: float,
   *   commission_total: float,
   *   transactions: list<array<string, mixed>>
   * }
   */
  public function technicianCommissionDetails(int $technicianId, Carbon $from, Carbon $to): array
  {
    $fromStart = $from->copy()->startOfDay();
    $toEnd = $to->copy()->endOfDay();

    $technician = Technician::query()->findOrFail($technicianId);

    $transactions = Transaction::query()
      ->with(['customer:id,name', 'serviceLines:id,transaction_id,service_name,quantity,subtotal'])
      ->where('status', 'completed')
      ->where('technician_id', $technicianId)
      ->where('technician_commission', '>', 0)
      ->whereBetween('created_at', [$fromStart, $toEnd])
      ->orderByDesc('created_at')
      ->get();

    $rows = $transactions->map(function (Transaction $tx) {
      $services = $tx->serviceLines
        ->map(function ($line) {
          $qty = (int) $line->quantity;

          return $qty > 1
            ? $line->service_name.' × '.$qty
            : $line->service_name;
        })
        ->filter()
        ->values()
        ->all();

      return [
        'id' => $tx->id,
        'transaction_no' => $tx->transaction_no,
        'created_at' => $tx->created_at?->timezone(config('app.timezone'))->format('d/m/Y H:i'),
        'customer_name' => $tx->displayCustomerName(),
        'services' => $services,
        'services_label' => $services !== [] ? implode(', ', $services) : '-',
        'services_total' => (float) $tx->subtotal_services,
        'commission' => (float) $tx->technician_commission,
      ];
    })->values()->all();

    return [
      'technician_id' => $technician->id,
      'technician_name' => $technician->name,
      'period' => [
        'from' => $fromStart->toDateString(),
        'to' => $toEnd->toDateString(),
      ],
      'transaction_count' => count($rows),
      'services_total' => round(array_sum(array_column($rows, 'services_total')), 2),
      'commission_total' => round(array_sum(array_column($rows, 'commission')), 2),
      'transactions' => $rows,
    ];
  }

  /**
   * @return array{entry_count: int, amount: float, by_category: Collection<int, array<string, mixed>>}
   */
  private function buildManualSummary(string $type, Carbon $fromStart, Carbon $toEnd): array
  {
    $baseQuery = ManualCashEntry::query()
      ->where('type', $type)
      ->completed()
      ->whereBetween('occurred_at', [$fromStart, $toEnd]);

    $byCategory = ManualCashEntry::query()
      ->from('manual_cash_entries')
      ->join('financial_categories', 'financial_categories.id', '=', 'manual_cash_entries.category_id')
      ->where('manual_cash_entries.type', $type)
      ->where('manual_cash_entries.status', 'completed')
      ->whereBetween('manual_cash_entries.occurred_at', [$fromStart, $toEnd])
      ->select([
        'financial_categories.id as category_id',
        'financial_categories.name as category_name',
        DB::raw('COUNT(*) as entry_count'),
        DB::raw('COALESCE(SUM(manual_cash_entries.amount), 0) as amount_total'),
      ])
      ->groupBy('financial_categories.id', 'financial_categories.name')
      ->orderByDesc('amount_total')
      ->get()
      ->map(fn ($row) => [
        'category_id' => (int) $row->category_id,
        'category_name' => $row->category_name,
        'entry_count' => (int) $row->entry_count,
        'amount_total' => (float) $row->amount_total,
      ]);

    return [
      'entry_count' => (int) (clone $baseQuery)->count(),
      'amount' => (float) (clone $baseQuery)->sum('amount'),
      'by_category' => $byCategory,
    ];
  }

  /**
   * @param  class-string<Transaction|Purchase>  $modelClass
   * @return array<string, mixed>
   */
  private function buildPaymentBreakdown(
    string $modelClass,
    Carbon $fromStart,
    Carbon $toEnd,
    bool $includeQris = true,
    string $amountColumn = 'total',
    string $dateColumn = 'created_at',
  ): array {
    $baseQuery = $modelClass::query()
      ->where('status', 'completed')
      ->whereBetween($dateColumn, [$fromStart, $toEnd]);

    $cash = (float) (clone $baseQuery)->where('payment_method', 'cash')->sum($amountColumn);
    $qris = $includeQris
      ? (float) (clone $baseQuery)->where('payment_method', 'qris')->sum($amountColumn)
      : 0.0;

    $transferRows = (clone $baseQuery)
      ->where('payment_method', 'transfer')
      ->select([
        'bank_account_id',
        DB::raw("COALESCE(SUM({$amountColumn}), 0) as amount"),
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

  /**
   * @return array<string, mixed>
   */
  private function buildManualPaymentBreakdown(string $type, Carbon $fromStart, Carbon $toEnd, bool $includeQris): array
  {
    $baseQuery = ManualCashEntry::query()
      ->where('type', $type)
      ->completed()
      ->whereBetween('occurred_at', [$fromStart, $toEnd]);

    $cash = (float) (clone $baseQuery)->where('payment_method', 'cash')->sum('amount');
    $qris = $includeQris
      ? (float) (clone $baseQuery)->where('payment_method', 'qris')->sum('amount')
      : 0.0;

    $transferRows = (clone $baseQuery)
      ->where('payment_method', 'transfer')
      ->select([
        'bank_account_id',
        DB::raw('COALESCE(SUM(amount), 0) as amount'),
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

  /**
   * @param  array<string, mixed>  $primary
   * @param  array<string, mixed>  $secondary
   * @return array<string, mixed>
   */
  private function mergePaymentBreakdowns(array $primary, array $secondary): array
  {
    $transferMap = [];

    foreach (array_merge($primary['transfer'], $secondary['transfer']) as $row) {
      $bankId = $row['bank_account_id'] ?? 0;

      if (! isset($transferMap[$bankId])) {
        $transferMap[$bankId] = $row;
        continue;
      }

      $transferMap[$bankId]['amount'] += $row['amount'];
      $transferMap[$bankId]['count'] += $row['count'];
    }

    $cash = round($primary['cash'] + $secondary['cash'], 2);
    $qris = round($primary['qris'] + $secondary['qris'], 2);
    $transfer = array_values($transferMap);
    $transferTotal = round(array_sum(array_column($transfer, 'amount')), 2);

    return [
      'cash' => $cash,
      'qris' => $qris,
      'transfer' => $transfer,
      'transfer_total' => $transferTotal,
      'total' => round($cash + $qris + $transferTotal, 2),
      'include_qris' => $primary['include_qris'] ?? true,
      'labels' => $primary['labels'] ?? $secondary['labels'],
    ];
  }
}
