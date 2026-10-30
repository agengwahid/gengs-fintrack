<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BudgetController extends Controller
{
    /**
     * Display a listing of user budgets with spent calculations.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        if ($user->categories()->count() === 0) {
            $user->seedDefaultFinances();
        }

        $period = $request->input('period', Carbon::now()->format('Y-m'));

        // Validate period format (YYYY-MM)
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period)) {
            $period = Carbon::now()->format('Y-m');
        }

        $startDate = Carbon::createFromFormat('Y-m', $period)->startOfMonth()->toDateString();
        $endDate = Carbon::createFromFormat('Y-m', $period)->endOfMonth()->toDateString();

        // Fetch user budgets for the period
        $budgets = $user->budgets()
            ->where('period', $period)
            ->with('category')
            ->get();

        // Aggregate actual expenses for all categories in this period in ONE query
        $expensesByCategory = Transaction::where('user_id', $user->id)
            ->where('type', 'expense')
            ->whereBetween('date', [$startDate, $endDate])
            ->whereNotNull('category_id')
            ->groupBy('category_id')
            ->selectRaw('category_id, SUM(amount) as total_spent')
            ->pluck('total_spent', 'category_id');

        // Transform budgets with calculation data
        $budgetsData = $budgets->map(function (Budget $budget) use ($expensesByCategory) {
            $spent = (float) ($expensesByCategory->get($budget->category_id) ?? 0);
            $amount = (float) $budget->amount;
            $remaining = $amount - $spent;
            $percentage = $amount > 0 ? round(($spent / $amount) * 100, 1) : 0;

            return [
                'id' => $budget->id,
                'category_id' => $budget->category_id,
                'category' => $budget->category,
                'period' => $budget->period,
                'amount' => $amount,
                'notes' => $budget->notes,
                'spent' => $spent,
                'remaining' => $remaining,
                'percentage' => $percentage,
                'is_over_budget' => $spent > $amount,
                'created_at' => $budget->created_at,
            ];
        })->sortByDesc('percentage')->values();

        $totalBudget = (float) $budgetsData->sum('amount');
        $totalSpent = (float) $budgetsData->sum('spent');
        $totalRemaining = $totalBudget - $totalSpent;
        $overallPercentage = $totalBudget > 0 ? round(($totalSpent / $totalBudget) * 100, 1) : 0;

        // Categories available for new budgets
        $categories = $user->categories()
            ->where('type', 'expense')
            ->orderBy('name')
            ->get();

        return Inertia::render('Budgets/Index', [
            'budgets' => $budgetsData,
            'period' => $period,
            'summary' => [
                'total_budget' => $totalBudget,
                'total_spent' => $totalSpent,
                'total_remaining' => $totalRemaining,
                'overall_percentage' => $overallPercentage,
            ],
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created budget.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'category_id' => [
                'required',
                'uuid',
                Rule::exists('categories', 'id')->where('user_id', $user->id)->where('type', 'expense'),
                Rule::unique('budgets', 'category_id')
                    ->where('user_id', $user->id)
                    ->where('period', $request->input('period')),
            ],
            'period' => ['required', 'regex:/^\d{4}-(0[1-9]|1[0-2])$/'],
            'amount' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'category_id.unique' => 'Anggaran untuk kategori ini pada periode tersebut sudah ada.',
            'category_id.exists' => 'Kategori tidak valid atau bukan kategori pengeluaran.',
            'amount.min' => 'Nominal anggaran harus lebih besar dari 0.',
        ]);

        $user->budgets()->create($validated);

        return back()->with('success', 'Anggaran berhasil dibuat.');
    }

    /**
     * Update the specified budget.
     */
    public function update(Request $request, Budget $budget): RedirectResponse
    {
        $user = $request->user();

        abort_if($budget->user_id !== $user->id, 403, 'Akses ditolak.');

        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:1'],
            'notes' => ['nullable', 'string', 'max:500'],
        ], [
            'amount.min' => 'Nominal anggaran harus lebih besar dari 0.',
        ]);

        $budget->update($validated);

        return back()->with('success', 'Anggaran berhasil diperbarui.');
    }

    /**
     * Remove the specified budget.
     */
    public function destroy(Request $request, Budget $budget): RedirectResponse
    {
        abort_if($budget->user_id !== $request->user()->id, 403, 'Akses ditolak.');

        $budget->delete();

        return back()->with('success', 'Anggaran berhasil dihapus.');
    }
}
