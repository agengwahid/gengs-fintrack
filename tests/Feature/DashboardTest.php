<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

test('unauthenticated users are redirected to login from dashboard', function () {
    $response = $this->get(route('dashboard'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Dashboard'));
});

test('dashboard calculates total balance from active accounts accurately', function () {
    $user = User::factory()->create();

    Account::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
        'current_balance' => 1500000,
    ]);

    Account::factory()->create([
        'user_id' => $user->id,
        'is_active' => true,
        'current_balance' => 2500000,
    ]);

    // Inactive account should NOT be counted in active total
    Account::factory()->create([
        'user_id' => $user->id,
        'is_active' => false,
        'current_balance' => 9999999,
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertOk();
    $response->assertInertia(function ($page) {
        $summary = $page->toArray()['props']['summary'];
        expect($summary['total_balance'])->toEqual(4000000);
    });
});

test('dashboard calculates income, expense, and net cashflow accurately for the period', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $catExpense = Category::factory()->create(['user_id' => $user->id, 'type' => 'expense']);
    $catIncome = Category::factory()->create(['user_id' => $user->id, 'type' => 'income']);

    // October income
    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $catIncome->id,
        'type' => 'income',
        'amount' => 5000000,
        'date' => '2026-10-05',
    ]);

    // October expense
    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $catExpense->id,
        'type' => 'expense',
        'amount' => 1800000,
        'date' => '2026-10-18',
    ]);

    // Transaction in September (should NOT count for October summary)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $catExpense->id,
        'type' => 'expense',
        'amount' => 700000,
        'date' => '2026-09-20',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard', ['period' => '2026-10']));

    $response->assertOk();
    $response->assertInertia(function ($page) {
        $summary = $page->toArray()['props']['summary'];
        expect($summary['income'])->toEqual(5000000);
        expect($summary['expense'])->toEqual(1800000);
        expect($summary['net_cashflow'])->toEqual(3200000);
    });
});

test('dashboard does not include transactions from other users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    $account = Account::factory()->create(['user_id' => $user->id]);
    $otherAccount = Account::factory()->create(['user_id' => $otherUser->id]);

    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'type' => 'income',
        'amount' => 1000000,
        'date' => '2026-10-10',
    ]);

    Transaction::factory()->create([
        'user_id' => $otherUser->id,
        'account_id' => $otherAccount->id,
        'type' => 'income',
        'amount' => 99000000,
        'date' => '2026-10-10',
    ]);

    $response = $this->actingAs($user)->get(route('dashboard', ['period' => '2026-10']));

    $response->assertOk();
    $response->assertInertia(function ($page) {
        $summary = $page->toArray()['props']['summary'];
        expect($summary['income'])->toEqual(1000000);
    });
});
