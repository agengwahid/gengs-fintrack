<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    /**
     * Display financial reports with breakdown and filtering.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->accounts()->count() === 0) {
            $user->seedDefaultFinances();
        }

        // 1. Determine Date Range based on period type
        $periodType = $request->input('period_type', 'monthly'); // daily, monthly, yearly, custom
        $today = Carbon::now();

        if ($periodType === 'daily') {
            $startDate = $request->input('date', $today->toDateString());
            $endDate = $startDate;
        } elseif ($periodType === 'yearly') {
            $year = $request->input('year', $today->format('Y'));
            $startDate = Carbon::createFromFormat('Y-m-d', "{$year}-01-01")->toDateString();
            $endDate = Carbon::createFromFormat('Y-m-d', "{$year}-12-31")->toDateString();
        } elseif ($periodType === 'custom') {
            $startDate = $request->input('start_date', $today->copy()->startOfMonth()->toDateString());
            $endDate = $request->input('end_date', $today->copy()->endOfMonth()->toDateString());
        } else { // monthly (default)
            $month = $request->input('month', $today->format('Y-m'));
            if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
                $month = $today->format('Y-m');
            }
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
        }

        // 2. Base Query with Date Range
        $query = $user->transactions()
            ->whereBetween('date', [$startDate, $endDate]);

        // Filters: Category, Account, Type
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($accountId = $request->input('account_id')) {
            $query->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)
                    ->orWhere('destination_account_id', $accountId);
            });
        }

        if ($type = $request->input('type')) {
            if (in_array($type, ['income', 'expense', 'transfer'])) {
                $query->where('type', $type);
            }
        }

        // 3. Totals & Net Cashflow Calculation
        $totalsQuery = clone $query;
        $totalIncome = (float) (clone $totalsQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (float) (clone $totalsQuery)->where('type', 'expense')->sum('amount');
        $netCashflow = $totalIncome - $totalExpense;
        $transactionCount = (int) (clone $totalsQuery)->count();

        // 4. Breakdown by Expense Category
        $categoryBreakdown = (clone $query)
            ->where('type', 'expense')
            ->whereNotNull('category_id')
            ->with('category')
            ->selectRaw('category_id, SUM(amount) as total_amount, COUNT(id) as count')
            ->groupBy('category_id')
            ->orderByDesc('total_amount')
            ->get()
            ->map(function ($row) use ($totalExpense) {
                $total = (float) $row->total_amount;
                $percent = $totalExpense > 0 ? round(($total / $totalExpense) * 100, 1) : 0;

                return [
                    'category_id' => $row->category_id,
                    'name' => $row->category ? $row->category->name : 'Tanpa Kategori',
                    'color' => $row->category ? $row->category->color : '#64748b',
                    'icon' => $row->category ? $row->category->icon : 'Folder',
                    'total' => $total,
                    'count' => (int) $row->count,
                    'percentage' => $percent,
                ];
            });

        // 5. Breakdown by Account
        $accounts = $user->accounts()->where('is_active', true)->orderBy('name')->get();
        $accountBreakdown = $accounts->map(function ($acc) use ($startDate, $endDate, $user) {
            $incoming = (float) $user->transactions()
                ->whereBetween('date', [$startDate, $endDate])
                ->where(function ($q) use ($acc) {
                    $q->where(function ($sq) use ($acc) {
                        $sq->where('type', 'income')->where('account_id', $acc->id);
                    })->orWhere(function ($sq) use ($acc) {
                        $sq->where('type', 'transfer')->where('destination_account_id', $acc->id);
                    });
                })->sum('amount');

            $outgoing = (float) $user->transactions()
                ->whereBetween('date', [$startDate, $endDate])
                ->where(function ($q) use ($acc) {
                    $q->where(function ($sq) use ($acc) {
                        $sq->where('type', 'expense')->where('account_id', $acc->id);
                    })->orWhere(function ($sq) use ($acc) {
                        $sq->where('type', 'transfer')->where('account_id', $acc->id);
                    });
                })->sum('amount');

            return [
                'id' => $acc->id,
                'name' => $acc->name,
                'type' => $acc->type,
                'color' => $acc->color,
                'icon' => $acc->icon,
                'incoming' => $incoming,
                'outgoing' => $outgoing,
                'net' => $incoming - $outgoing,
                'current_balance' => (float) $acc->current_balance,
            ];
        });

        // 6. Trend Timeline (Grouped by Date)
        $dailyTrends = (clone $query)
            ->whereIn('type', ['income', 'expense'])
            ->selectRaw('date, type, SUM(amount) as total')
            ->groupBy('date', 'type')
            ->orderBy('date', 'asc')
            ->get();

        $trendMap = [];
        foreach ($dailyTrends as $trend) {
            $d = is_string($trend->date) ? $trend->date : $trend->date->format('Y-m-d');
            if (! isset($trendMap[$d])) {
                $trendMap[$d] = ['date' => $d, 'income' => 0, 'expense' => 0];
            }
            if ($trend->type === 'income') {
                $trendMap[$d]['income'] = (float) $trend->total;
            } elseif ($trend->type === 'expense') {
                $trendMap[$d]['expense'] = (float) $trend->total;
            }
        }
        $trendData = array_values($trendMap);

        // 7. Paginated Transaction Records
        $transactions = (clone $query)
            ->with(['account', 'destinationAccount', 'category'])
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20)
            ->withQueryString();

        // 8. Categories for filter
        $categories = $user->categories()->orderBy('name')->get();

        return Inertia::render('Reports/Index', [
            'filters' => [
                'period_type' => $periodType,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'category_id' => $request->input('category_id', ''),
                'account_id' => $request->input('account_id', ''),
                'type' => $request->input('type', ''),
                'month' => $request->input('month', $today->format('Y-m')),
                'year' => $request->input('year', $today->format('Y')),
                'date' => $request->input('date', $today->toDateString()),
            ],
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_cashflow' => $netCashflow,
                'transaction_count' => $transactionCount,
            ],
            'categoryBreakdown' => $categoryBreakdown,
            'accountBreakdown' => $accountBreakdown,
            'trendData' => $trendData,
            'transactions' => $transactions,
            'accounts' => $accounts,
            'categories' => $categories,
        ]);
    }

    /**
     * Export report data as CSV file.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();

        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        if (! $startDate || ! $endDate) {
            $startDate = Carbon::now()->startOfMonth()->toDateString();
            $endDate = Carbon::now()->endOfMonth()->toDateString();
        }

        $query = $user->transactions()
            ->with(['account', 'destinationAccount', 'category'])
            ->whereBetween('date', [$startDate, $endDate]);

        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($accountId = $request->input('account_id')) {
            $query->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)
                    ->orWhere('destination_account_id', $accountId);
            });
        }

        if ($type = $request->input('type')) {
            if (in_array($type, ['income', 'expense', 'transfer'])) {
                $query->where('type', $type);
            }
        }

        $transactions = $query->orderBy('date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $filename = "laporan-keuangan-{$startDate}-sd-{$endDate}.csv";

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->stream(function () use ($transactions) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 BOM for Microsoft Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // CSV Header Row
            fputcsv($handle, [
                'ID Transaksi',
                'Tanggal',
                'Tipe Transaksi',
                'Rekening Asal',
                'Rekening Tujuan',
                'Kategori',
                'Nominal (IDR)',
                'Catatan / Deskripsi',
                'Nomor Referensi',
                'Waktu Dibuat',
            ]);

            foreach ($transactions as $t) {
                $typeLabel = match ($t->type) {
                    'income' => 'Pemasukan',
                    'expense' => 'Pengeluaran',
                    'transfer' => 'Transfer',
                    default => $t->type,
                };

                fputcsv($handle, [
                    $t->id,
                    $t->date,
                    $typeLabel,
                    $t->account ? $t->account->name : '-',
                    $t->destinationAccount ? $t->destinationAccount->name : '-',
                    $t->category ? $t->category->name : '-',
                    number_format((float) $t->amount, 2, '.', ''),
                    $t->notes ?? '-',
                    $t->reference_number ?? '-',
                    $t->created_at ? $t->created_at->toDateTimeString() : '-',
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }
}
