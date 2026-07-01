<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Item;
use App\Models\Purchase;
use App\Models\Technician;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WorkshopService;
use App\Support\PaymentMethodResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $today = now()->startOfDay();
        $monthStart = now()->copy()->startOfMonth();
        $monthEnd = now()->copy()->endOfMonth();

        $txToday = Transaction::query()
            ->where('status', 'completed')
            ->whereDate('created_at', $today);

        $txMonth = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        $purchaseMonth = Purchase::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        $revenueToday = (float) (clone $txToday)->sum('total');
        $revenueMonth = (float) (clone $txMonth)->sum('total');
        $expenseMonth = (float) (clone $purchaseMonth)->sum('total');
        $commissionMonth = (float) (clone $txMonth)->sum('technician_commission');

        $lowStockCount = Item::lowStock()->count();

        return [
            'kpis' => [
                'transactions_today' => (int) (clone $txToday)->count(),
                'revenue_today' => $revenueToday,
                'revenue_month' => $revenueMonth,
                'expense_month' => $expenseMonth,
                'commission_month' => $commissionMonth,
                'profit_estimate_month' => round($revenueMonth - $expenseMonth - $commissionMonth, 2),
                'customers' => Customer::count(),
                'items_active' => Item::where('is_active', true)->count(),
                'low_stock' => $lowStockCount,
                'technicians_active' => Technician::where('is_active', true)->count(),
                'services_active' => WorkshopService::where('is_active', true)->count(),
                'users' => User::count(),
                'purchases_month' => (int) (clone $purchaseMonth)->count(),
            ],
            'charts' => [
                'daily' => $this->dailyTrend(7),
                'payment_methods' => $this->paymentMethodBreakdown($monthStart, $monthEnd),
                'transaction_types' => $this->transactionTypeBreakdown($monthStart, $monthEnd),
            ],
            'recent_transactions' => $this->recentTransactions(),
            'low_stock_items' => Item::query()
                ->lowStock()
                ->with(['category:id,name', 'unit:id,name,abbreviation'])
                ->orderBy('stock')
                ->orderBy('name')
                ->limit(8)
                ->get(['id', 'code', 'name', 'stock', 'stock_opname', 'category_id', 'unit_id']),
        ];
    }

    /**
     * @return array{labels: list<string>, revenue: list<float>, expense: list<float>}
     */
    private function dailyTrend(int $days): array
    {
        $labels = [];
        $revenue = [];
        $expense = [];

        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $start = $date->copy()->startOfDay();
            $end = $date->copy()->endOfDay();

            $labels[] = $date->format('d/m');

            $revenue[] = (float) Transaction::query()
                ->where('status', 'completed')
                ->whereBetween('created_at', [$start, $end])
                ->sum('total');

            $expense[] = (float) Purchase::query()
                ->where('status', 'completed')
                ->whereBetween('created_at', [$start, $end])
                ->sum('total');
        }

        return compact('labels', 'revenue', 'expense');
    }

    /**
     * @return array{labels: list<string>, values: list<float>, colors: list<string>}
     */
    private function paymentMethodBreakdown(Carbon $from, Carbon $to): array
    {
        $rows = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->select('payment_method', DB::raw('COALESCE(SUM(total), 0) as amount'))
            ->groupBy('payment_method')
            ->get();

        $colorMap = [
            'cash' => '#22c55e',
            'qris' => '#3b82f6',
            'transfer' => '#f59e0b',
        ];

        $labels = [];
        $values = [];
        $colors = [];

        foreach ($rows as $row) {
            if ((float) $row->amount <= 0) {
                continue;
            }
            $labels[] = PaymentMethodResolver::label($row->payment_method);
            $values[] = (float) $row->amount;
            $colors[] = $colorMap[$row->payment_method] ?? '#94a3b8';
        }

        if (empty($labels)) {
            return ['labels' => ['Belum ada'], 'values' => [1], 'colors' => ['#e2e8f0']];
        }

        return compact('labels', 'values', 'colors');
    }

    /**
     * @return array{labels: list<string>, values: list<int>, colors: list<string>}
     */
    private function transactionTypeBreakdown(Carbon $from, Carbon $to): array
    {
        $typeLabels = [
            'sale' => 'Penjualan',
            'service' => 'Servis',
            'combined' => 'Gabungan',
        ];
        $colorMap = [
            'sale' => '#6366f1',
            'service' => '#06b6d4',
            'combined' => '#f97316',
        ];

        $rows = Transaction::query()
            ->where('status', 'completed')
            ->whereBetween('created_at', [$from, $to])
            ->select('type', DB::raw('COUNT(*) as total'))
            ->groupBy('type')
            ->get()
            ->keyBy('type');

        $labels = [];
        $values = [];
        $colors = [];

        foreach ($typeLabels as $key => $label) {
            $count = (int) ($rows->get($key)?->total ?? 0);
            if ($count === 0) {
                continue;
            }
            $labels[] = $label;
            $values[] = $count;
            $colors[] = $colorMap[$key];
        }

        if (empty($labels)) {
            return ['labels' => ['Belum ada'], 'values' => [0], 'colors' => ['#e2e8f0']];
        }

        return compact('labels', 'values', 'colors');
    }

    /**
     * @return Collection<int, Transaction>
     */
    private function recentTransactions(): Collection
    {
        return Transaction::query()
            ->with(['customer:id,name', 'user:id,name'])
            ->where('status', 'completed')
            ->latest()
            ->limit(6)
            ->get(['id', 'transaction_no', 'type', 'customer_id', 'user_id', 'total', 'created_at']);
    }
}
