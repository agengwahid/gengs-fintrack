<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GoogleSheetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Rekening / Accounts
    Route::resource('accounts', AccountController::class)->except(['create', 'show', 'edit']);

    // Kategori / Categories
    Route::resource('categories', CategoryController::class)->except(['create', 'show', 'edit']);

    // Transaksi / Transactions
    Route::resource('transactions', TransactionController::class)->except(['create', 'show', 'edit']);

    // Anggaran / Budgets
    Route::resource('budgets', BudgetController::class)->except(['create', 'show', 'edit']);

    // Laporan / Reports
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export-csv', [ReportController::class, 'exportCsv'])->name('reports.export-csv');

    // Google Sheets Integration
    Route::get('sheets', [GoogleSheetController::class, 'index'])->name('sheets.index');
    Route::post('sheets/sync-all', [GoogleSheetController::class, 'syncAll'])->name('sheets.sync-all');
    Route::post('sheets/retry/{log}', [GoogleSheetController::class, 'retry'])->name('sheets.retry');
    Route::post('sheets/test-connection', [GoogleSheetController::class, 'testConnection'])->name('sheets.test-connection');
});

require __DIR__.'/auth.php';
