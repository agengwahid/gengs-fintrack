<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CategoryController extends Controller
{
    /**
     * Display a listing of user categories.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Seed default categories if user has none
        if ($user->categories()->count() === 0) {
            $user->seedDefaultFinances();
        }

        $categories = $user->categories()
            ->orderBy('name', 'asc')
            ->get();

        $expenseCategories = $categories->where('type', 'expense')->values();
        $incomeCategories = $categories->where('type', 'income')->values();

        return Inertia::render('Categories/Index', [
            'expenseCategories' => $expenseCategories,
            'incomeCategories' => $incomeCategories,
        ]);
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:expense,income'],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $validated['color'] = $validated['color'] ?? ($validated['type'] === 'expense' ? '#ef4444' : '#10b981');
        $validated['icon'] = $validated['icon'] ?? ($validated['type'] === 'expense' ? 'ShoppingBag' : 'Wallet');
        $validated['is_default'] = false;

        $request->user()->categories()->create($validated);

        return redirect()->back()->with('success', 'Kategori berhasil ditambahkan!');
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, Category $category): RedirectResponse
    {
        abort_if($category->user_id !== $request->user()->id, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', 'string', 'in:expense,income'],
            'color' => ['nullable', 'string', 'max:30'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $category->update($validated);

        return redirect()->back()->with('success', 'Kategori berhasil diperbarui!');
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy(Request $request, Category $category): RedirectResponse
    {
        abort_if($category->user_id !== $request->user()->id, 403);

        $category->delete();

        return redirect()->back()->with('success', 'Kategori berhasil dihapus!');
    }
}
