<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Display the financial dashboard.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Ensure starter accounts & categories exist
        if ($user->accounts()->count() === 0) {
            $user->seedDefaultFinances();
        }

        $period = $request->input('period', Carbon::now()->format('Y-m'));
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
            $period = Carbon::now()->format('Y-m');
        }

        $startDate = Carbon::createFromFormat('Y-m', $period)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromFormat('Y-m', $period)->endOfMonth()->toDateString();

        // 1. Total Balance of all active accounts
        $accounts = $user->accounts()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        $totalBalance = (float) $accounts->sum('current_balance');

        // 2. Income & Expense this month
        $incomeThisMonth = (float) $user->transactions()
            ->where('type', 'income')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $expenseThisMonth = (float) $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->sum('amount');

        $netCashflow = $incomeThisMonth - $expenseThisMonth;

        // 3. Six-month Monthly Trends (income vs expense)
        $monthlyTrends = [];
        $targetDate = Carbon::createFromFormat('Y-m', $period)->endOfMonth();

        $bulanIndo = [
            '01' => 'Jan', '02' => 'Feb', '03' => 'Mar', '04' => 'Apr',
            '05' => 'Mei', '06' => 'Jun', '07' => 'Jul', '08' => 'Agt',
            '09' => 'Sep', '10' => 'Okt', '11' => 'Nov', '12' => 'Des',
        ];

        for ($i = 5; $i >= 0; $i--) {
            $mDate = $targetDate->copy()->subMonths($i);
            $mKey = $mDate->format('Y-m');
            $mMonthNum = $mDate->format('m');
            $mLabel = ($bulanIndo[$mMonthNum] ?? $mDate->format('M')).' '.$mDate->format('Y');

            $monthlyTrends[$mKey] = [
                'period' => $mKey,
                'label' => $mLabel,
                'income' => 0,
                'expense' => 0,
            ];
        }

        $sixMonthStart = $targetDate->copy()->subMonths(5)->startOfMonth()->toDateString();
        $sixMonthEnd = $targetDate->toDateString();

        $driver = DB::connection()->getDriverName();
        $monthExpr = $driver === 'sqlite' ? "strftime('%Y-%m', date)" : "TO_CHAR(date, 'YYYY-MM')";

        $trendRows = $user->transactions()
            ->whereIn('type', ['income', 'expense'])
            ->whereBetween('date', [$sixMonthStart, $sixMonthEnd])
            ->selectRaw("{$monthExpr} as month_period, type, SUM(amount) as total")
            ->groupBy('month_period', 'type')
            ->get();

        foreach ($trendRows as $row) {
            if (isset($monthlyTrends[$row->month_period])) {
                if ($row->type === 'income') {
                    $monthlyTrends[$row->month_period]['income'] = (float) $row->total;
                } elseif ($row->type === 'expense') {
                    $monthlyTrends[$row->month_period]['expense'] = (float) $row->total;
                }
            }
        }
        $monthlyTrendsData = array_values($monthlyTrends);

        // 4. Expenses by Category (Doughnut Chart)
        $categoryExpenses = $user->transactions()
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->with('category')
            ->selectRaw('category_id, SUM(amount) as total')
            ->groupBy('category_id')
            ->orderByDesc('total')
            ->get()
            ->map(function ($row) {
                return [
                    'category_name' => $row->category ? $row->category->name : 'Tanpa Kategori',
                    'color' => $row->category ? $row->category->color : '#94a3b8',
                    'icon' => $row->category ? $row->category->icon : 'Folder',
                    'total' => (float) $row->total,
                ];
            });

        // 5. Budget Summary for this period
        $budgets = $user->budgets()
            ->where('period', $period)
            ->with('category')
            ->get();

        $totalBudget = (float) $budgets->sum('amount');
        $budgetedCategoryIds = $budgets->pluck('category_id')->filter()->all();

        $budgetSpent = 0;
        if (! empty($budgetedCategoryIds)) {
            $budgetSpent = (float) $user->transactions()
                ->where('type', 'expense')
                ->whereBetween('date', [$startDate, $endDate])
                ->whereIn('category_id', $budgetedCategoryIds)
                ->sum('amount');
        }

        $budgetPercentage = $totalBudget > 0 ? round(($budgetSpent / $totalBudget) * 100, 1) : 0;

        // 6. Recent 5 Transactions
        $recentTransactions = $user->transactions()
            ->with(['account', 'destinationAccount', 'category'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return Inertia::render('Dashboard', [
            'period' => $period,
            'summary' => [
                'total_balance' => $totalBalance,
                'income' => $incomeThisMonth,
                'expense' => $expenseThisMonth,
                'net_cashflow' => $netCashflow,
            ],
            'budgetSummary' => [
                'total_budget' => $totalBudget,
                'total_spent' => $budgetSpent,
                'percentage' => $budgetPercentage,
                'count' => $budgets->count(),
            ],
            'monthlyTrends' => $monthlyTrendsData,
            'categoryExpenses' => $categoryExpenses,
            'recentTransactions' => $recentTransactions,
            'accounts' => $accounts,
        ]);
    }
}
