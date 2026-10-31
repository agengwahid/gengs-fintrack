<?php

namespace App\Http\Controllers;

use App\Jobs\SyncTransactionToGoogleSheet;
use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionController extends Controller
{
    /**
     * Display a listing of user transactions.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Seed default finances if user has none
        if ($user->accounts()->count() === 0) {
            $user->seedDefaultFinances();
        }

        $query = $user->transactions()
            ->with(['account', 'destinationAccount', 'category']);

        // Filter: Search (notes, reference number)
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('notes', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%");
            });
        }

        // Filter: Type
        if ($type = $request->input('type')) {
            if (in_array($type, ['expense', 'income', 'transfer'])) {
                $query->where('type', $type);
            }
        }

        // Filter: Account (source or destination)
        if ($accountId = $request->input('account_id')) {
            $query->where(function ($q) use ($accountId) {
                $q->where('account_id', $accountId)
                    ->orWhere('destination_account_id', $accountId);
            });
        }

        // Filter: Category
        if ($categoryId = $request->input('category_id')) {
            $query->where('category_id', $categoryId);
        }

        // Filter: Date range / Month
        $month = $request->input('month', Carbon::now()->format('Y-m'));
        if ($month) {
            $startDate = Carbon::createFromFormat('Y-m', $month)->startOfMonth()->toDateString();
            $endDate = Carbon::createFromFormat('Y-m', $month)->endOfMonth()->toDateString();
            $query->whereBetween('date', [$startDate, $endDate]);
        } elseif ($startDate = $request->input('start_date')) {
            $query->where('date', '>=', $startDate);
            if ($endDate = $request->input('end_date')) {
                $query->where('date', '<=', $endDate);
            }
        }

        // Calculate summary for the filtered transactions
        $summaryQuery = clone $query;
        $totalIncome = (float) (clone $summaryQuery)->where('type', 'income')->sum('amount');
        $totalExpense = (float) (clone $summaryQuery)->where('type', 'expense')->sum('amount');
        $netCashflow = $totalIncome - $totalExpense;

        // Paginate results
        $transactions = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(15)
            ->withQueryString();

        // Fetch accounts and categories for filter & modal forms
        $accounts = $user->accounts()
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $categories = $user->categories()
            ->orderBy('name', 'asc')
            ->get();

        return Inertia::render('Transactions/Index', [
            'transactions' => $transactions,
            'filters' => [
                'search' => $request->input('search', ''),
                'type' => $request->input('type', ''),
                'account_id' => $request->input('account_id', ''),
                'category_id' => $request->input('category_id', ''),
                'month' => $month,
            ],
            'summary' => [
                'total_income' => $totalIncome,
                'total_expense' => $totalExpense,
                'net_cashflow' => $netCashflow,
            ],
            'accounts' => $accounts,
            'expenseCategories' => $categories->where('type', 'expense')->values(),
            'incomeCategories' => $categories->where('type', 'income')->values(),
        ]);
    }

    /**
     * Store a newly created transaction in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:expense,income,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'destination_account_id' => ['nullable', 'uuid', 'exists:accounts,id', 'different:account_id'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        // Validate account ownership
        $account = Account::findOrFail($validated['account_id']);
        if ($account->user_id !== $user->id) {
            throw ValidationException::withMessages(['account_id' => 'Rekening tidak valid.']);
        }

        if ($validated['type'] === 'transfer') {
            if (empty($validated['destination_account_id'])) {
                throw ValidationException::withMessages(['destination_account_id' => 'Rekening tujuan transfer wajib dipilih.']);
            }
            $destAccount = Account::findOrFail($validated['destination_account_id']);
            if ($destAccount->user_id !== $user->id) {
                throw ValidationException::withMessages(['destination_account_id' => 'Rekening tujuan tidak valid.']);
            }
            $validated['category_id'] = null;
        } else {
            $validated['destination_account_id'] = null;
            if (empty($validated['category_id'])) {
                throw ValidationException::withMessages(['category_id' => 'Kategori wajib dipilih.']);
            }
            $category = Category::findOrFail($validated['category_id']);
            if ($category->user_id !== $user->id) {
                throw ValidationException::withMessages(['category_id' => 'Kategori tidak valid.']);
            }
        }

        $transaction = DB::transaction(function () use ($user, $validated) {
            $transaction = $user->transactions()->create($validated);
            $this->applyBalance($transaction);

            return $transaction;
        });

        // Dispatch background sync to Google Sheets queue
        SyncTransactionToGoogleSheet::dispatch($user->id, $transaction->id, 'create');

        return redirect()->back()->with('success', 'Transaksi berhasil disimpan!');
    }

    /**
     * Update the specified transaction in storage.
     */
    public function update(Request $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();
        abort_if($transaction->user_id !== $user->id, 403);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:expense,income,transfer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'account_id' => ['required', 'uuid', 'exists:accounts,id'],
            'destination_account_id' => ['nullable', 'uuid', 'exists:accounts,id', 'different:account_id'],
            'category_id' => ['nullable', 'uuid', 'exists:categories,id'],
            'notes' => ['nullable', 'string', 'max:500'],
            'reference_number' => ['nullable', 'string', 'max:100'],
        ]);

        // Validate account ownership
        $account = Account::findOrFail($validated['account_id']);
        if ($account->user_id !== $user->id) {
            throw ValidationException::withMessages(['account_id' => 'Rekening tidak valid.']);
        }

        if ($validated['type'] === 'transfer') {
            if (empty($validated['destination_account_id'])) {
                throw ValidationException::withMessages(['destination_account_id' => 'Rekening tujuan transfer wajib dipilih.']);
            }
            $destAccount = Account::findOrFail($validated['destination_account_id']);
            if ($destAccount->user_id !== $user->id) {
                throw ValidationException::withMessages(['destination_account_id' => 'Rekening tujuan tidak valid.']);
            }
            $validated['category_id'] = null;
        } else {
            $validated['destination_account_id'] = null;
            if (empty($validated['category_id'])) {
                throw ValidationException::withMessages(['category_id' => 'Kategori wajib dipilih.']);
            }
            $category = Category::findOrFail($validated['category_id']);
            if ($category->user_id !== $user->id) {
                throw ValidationException::withMessages(['category_id' => 'Kategori tidak valid.']);
            }
        }

        DB::transaction(function () use ($transaction, $validated) {
            // Revert old impact on balance
            $this->revertBalance($transaction);

            // Update transaction
            $transaction->update($validated);

            // Apply new impact on balance
            $this->applyBalance($transaction->fresh());
        });

        // Dispatch background sync to Google Sheets queue
        SyncTransactionToGoogleSheet::dispatch($user->id, $transaction->id, 'update');

        return redirect()->back()->with('success', 'Transaksi berhasil diperbarui!');
    }

    /**
     * Remove the specified transaction from storage.
     */
    public function destroy(Request $request, Transaction $transaction): RedirectResponse
    {
        $user = $request->user();
        abort_if($transaction->user_id !== $user->id, 403);

        $transactionId = $transaction->id;

        DB::transaction(function () use ($transaction) {
            $this->revertBalance($transaction);
            $transaction->delete();
        });

        // Dispatch background sync to Google Sheets queue
        SyncTransactionToGoogleSheet::dispatch($user->id, $transactionId, 'delete');

        return redirect()->back()->with('success', 'Transaksi berhasil dihapus!');
    }

    /**
     * Apply transaction amount to account balances.
     */
    protected function applyBalance(Transaction $transaction): void
    {
        $amount = (float) $transaction->amount;

        if ($transaction->type === 'expense') {
            $transaction->account()->decrement('current_balance', $amount);
        } elseif ($transaction->type === 'income') {
            $transaction->account()->increment('current_balance', $amount);
        } elseif ($transaction->type === 'transfer') {
            $transaction->account()->decrement('current_balance', $amount);
            if ($transaction->destination_account_id) {
                $transaction->destinationAccount()->increment('current_balance', $amount);
            }
        }
    }

    /**
     * Revert transaction amount from account balances.
     */
    protected function revertBalance(Transaction $transaction): void
    {
        $amount = (float) $transaction->amount;

        if ($transaction->type === 'expense') {
            $transaction->account()->increment('current_balance', $amount);
        } elseif ($transaction->type === 'income') {
            $transaction->account()->decrement('current_balance', $amount);
        } elseif ($transaction->type === 'transfer') {
            $transaction->account()->increment('current_balance', $amount);
            if ($transaction->destination_account_id) {
                $transaction->destinationAccount()->decrement('current_balance', $amount);
            }
        }
    }
}
