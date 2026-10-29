<?php

use App\Models\Account;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

test('unauthenticated users are redirected to login when visiting transactions', function () {
    $response = $this->get(route('transactions.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view transactions page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('transactions.index'));

    $response->assertOk();
});

test('user can create an expense transaction and account balance decreases', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'user_id' => $user->id,
        'initial_balance' => 1000000,
        'current_balance' => 1000000,
    ]);
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => 'expense',
    ]);

    $response = $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'expense',
        'amount' => 150000,
        'date' => '2026-10-29',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'notes' => 'Makan siang & kopi',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'type' => 'expense',
        'amount' => 150000,
        'account_id' => $account->id,
    ]);

    // Check account balance decreased
    expect((float) $account->fresh()->current_balance)->toBe(850000.0);
});

test('user can create an income transaction and account balance increases', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'user_id' => $user->id,
        'initial_balance' => 500000,
        'current_balance' => 500000,
    ]);
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => 'income',
    ]);

    $response = $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'income',
        'amount' => 2000000,
        'date' => '2026-10-29',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'notes' => 'Gaji freelance',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'type' => 'income',
        'amount' => 2000000,
        'account_id' => $account->id,
    ]);

    // Check account balance increased
    expect((float) $account->fresh()->current_balance)->toBe(2500000.0);
});

test('user can create a transfer transaction and both account balances adjust', function () {
    $user = User::factory()->create();
    $sourceAccount = Account::factory()->create([
        'user_id' => $user->id,
        'initial_balance' => 1000000,
        'current_balance' => 1000000,
    ]);
    $destAccount = Account::factory()->create([
        'user_id' => $user->id,
        'initial_balance' => 200000,
        'current_balance' => 200000,
    ]);

    $response = $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'transfer',
        'amount' => 300000,
        'date' => '2026-10-29',
        'account_id' => $sourceAccount->id,
        'destination_account_id' => $destAccount->id,
        'notes' => 'Top up e-wallet dari bank',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('transactions', [
        'user_id' => $user->id,
        'type' => 'transfer',
        'amount' => 300000,
        'account_id' => $sourceAccount->id,
        'destination_account_id' => $destAccount->id,
    ]);

    // Check balances
    expect((float) $sourceAccount->fresh()->current_balance)->toBe(700000.0);
    expect((float) $destAccount->fresh()->current_balance)->toBe(500000.0);
});

test('user can update a transaction and balance is recalculated correctly', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'user_id' => $user->id,
        'initial_balance' => 1000000,
        'current_balance' => 900000,
    ]);
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => 'expense',
    ]);
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 100000,
        'date' => '2026-10-29',
    ]);

    // Update expense from 100k to 250k
    $response = $this->actingAs($user)->put(route('transactions.update', $transaction), [
        'type' => 'expense',
        'amount' => 250000,
        'date' => '2026-10-29',
        'account_id' => $account->id,
        'category_id' => $category->id,
        'notes' => 'Revisi nota pengeluaran',
    ]);

    $response->assertRedirect();
    expect((float) $account->fresh()->current_balance)->toBe(750000.0);
});

test('user can delete a transaction and balance is reverted', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'user_id' => $user->id,
        'initial_balance' => 1000000,
        'current_balance' => 900000,
    ]);
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => 'expense',
    ]);
    $transaction = Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 100000,
        'date' => '2026-10-29',
    ]);

    $response = $this->actingAs($user)->delete(route('transactions.destroy', $transaction));

    $response->assertRedirect();
    $this->assertDatabaseMissing('transactions', [
        'id' => $transaction->id,
    ]);

    // Balance should revert back from 900,000 to 1,000,000
    expect((float) $account->fresh()->current_balance)->toBe(1000000.0);
});

test('user cannot access or modify another users transaction', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $account2 = Account::factory()->create(['user_id' => $user2->id]);
    $category2 = Category::factory()->create(['user_id' => $user2->id]);
    $tx2 = Transaction::factory()->create([
        'user_id' => $user2->id,
        'account_id' => $account2->id,
        'category_id' => $category2->id,
    ]);

    $response = $this->actingAs($user1)->delete(route('transactions.destroy', $tx2));

    $response->assertForbidden();
    $this->assertDatabaseHas('transactions', ['id' => $tx2->id]);
});
