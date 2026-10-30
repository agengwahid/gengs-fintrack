<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

test('unauthenticated users are redirected to login from reports', function () {
    $response = $this->get(route('reports.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view reports page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('reports.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Reports/Index'));
});

test('reports calculate summary accurately for the selected date range', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $catExpense = Category::factory()->create(['user_id' => $user->id, 'type' => 'expense']);
    $catIncome = Category::factory()->create(['user_id' => $user->id, 'type' => 'income']);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $catIncome->id,
        'type' => 'income',
        'amount' => 4000000,
        'date' => '2026-10-10',
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $catExpense->id,
        'type' => 'expense',
        'amount' => 1500000,
        'date' => '2026-10-15',
    ]);

    $response = $this->actingAs($user)->get(route('reports.index', [
        'period_type' => 'monthly',
        'month' => '2026-10',
    ]));

    $response->assertOk();
    $response->assertInertia(function ($page) {
        $summary = $page->toArray()['props']['summary'];
        expect($summary['total_income'])->toEqual(4000000);
        expect($summary['total_expense'])->toEqual(1500000);
        expect($summary['net_cashflow'])->toEqual(2500000);
        expect($summary['transaction_count'])->toEqual(2);
    });
});

test('reports category breakdown calculates correct percentages', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $cat1 = Category::factory()->create(['user_id' => $user->id, 'type' => 'expense', 'name' => 'Makan']);
    $cat2 = Category::factory()->create(['user_id' => $user->id, 'type' => 'expense', 'name' => 'Transport']);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $cat1->id,
        'type' => 'expense',
        'amount' => 600000,
        'date' => '2026-10-10',
    ]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $cat2->id,
        'type' => 'expense',
        'amount' => 400000,
        'date' => '2026-10-12',
    ]);

    $response = $this->actingAs($user)->get(route('reports.index', [
        'period_type' => 'monthly',
        'month' => '2026-10',
    ]));

    $response->assertOk();
    $response->assertInertia(function ($page) {
        $categories = $page->toArray()['props']['categoryBreakdown'];
        expect($categories)->toHaveCount(2);

        $makan = collect($categories)->firstWhere('name', 'Makan');
        expect($makan['total'])->toEqual(600000);
        expect($makan['percentage'])->toEqual(60);

        $transport = collect($categories)->firstWhere('name', 'Transport');
        expect($transport['total'])->toEqual(400000);
        expect($transport['percentage'])->toEqual(40);
    });
});

test('export csv downloads formatted CSV stream with transactions', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id, 'name' => 'BCA Utama']);
    $category = Category::factory()->create(['user_id' => $user->id, 'type' => 'expense', 'name' => 'Makan Siang']);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 75000,
        'date' => '2026-10-25',
        'notes' => 'Nasi Padang Komplit',
    ]);

    $response = $this->actingAs($user)->get(route('reports.export-csv', [
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-31',
    ]));

    $response->assertOk();
    $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

    // Capture streamed content
    ob_start();
    $response->sendContent();
    $content = ob_get_clean();

    expect($content)->toContain('ID Transaksi');
    expect($content)->toContain('Tanggal');
    expect($content)->toContain('BCA Utama');
    expect($content)->toContain('Makan Siang');
    expect($content)->toContain('75000.00');
    expect($content)->toContain('Nasi Padang Komplit');
});
