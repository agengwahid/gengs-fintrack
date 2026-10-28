<?php

namespace App\Http\Controllers;

use App\Models\Account;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AccountController extends Controller
{
    /**
     * Display a listing of user accounts.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Seed default accounts if user has none
        if ($user->accounts()->count() === 0) {
            $user->seedDefaultFinances();
        }

        $accounts = $user->accounts()
            ->orderBy('is_active', 'desc')
            ->orderBy('name', 'asc')
            ->get();

        $totalBalance = (float) $accounts->where('is_active', true)->sum('current_balance');

        return Inertia::render('Accounts/Index', [
            'accounts' => $accounts,
            'totalBalance' => $totalBalance,
        ]);
    }

    /**
     * Store a newly created account in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:cash,bank,ewallet,investment,credit_card,other'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:50'],
            'initial_balance' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $validated['color'] = $validated['color'] ?? '#10b981';
        $validated['icon'] = $validated['icon'] ?? 'Wallet';
        $validated['current_balance'] = $validated['initial_balance'];
        $validated['is_active'] = $validated['is_active'] ?? true;

        $request->user()->accounts()->create($validated);

        return redirect()->back()->with('success', 'Rekening berhasil ditambahkan!');
    }

    /**
     * Update the specified account in storage.
     */
    public function update(Request $request, Account $account): RedirectResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:cash,bank,ewallet,investment,credit_card,other'],
            'account_number' => ['nullable', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:50'],
            'initial_balance' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        if (isset($validated['initial_balance'])) {
            // Adjust current balance according to difference if needed
            $diff = (float) $validated['initial_balance'] - (float) $account->initial_balance;
            $validated['current_balance'] = (float) $account->current_balance + $diff;
        }

        $account->update($validated);

        return redirect()->back()->with('success', 'Rekening berhasil diperbarui!');
    }

    /**
     * Remove the specified account from storage.
     */
    public function destroy(Request $request, Account $account): RedirectResponse
    {
        abort_if($account->user_id !== $request->user()->id, 403);

        $account->delete();

        return redirect()->back()->with('success', 'Rekening berhasil dihapus!');
    }
}
