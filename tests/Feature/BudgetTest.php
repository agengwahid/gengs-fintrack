<?php

use App\Models\Account;
use App\Models\Budget;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;

test('unauthenticated users are redirected to login when visiting budgets', function () {
    $response = $this->get(route('budgets.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view budgets page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('budgets.index'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page->component('Budgets/Index'));
});

test('user can create a budget for an expense category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => 'expense',
        'name' => 'Belanja Bulanan',
    ]);

    $response = $this->actingAs($user)->post(route('budgets.store'), [
        'category_id' => $category->id,
        'period' => '2026-10',
        'amount' => 1500000,
        'notes' => 'Batas belanja bulanan',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('budgets', [
        'user_id' => $user->id,
        'category_id' => $category->id,
        'period' => '2026-10',
        'amount' => 1500000,
    ]);
});

test('user cannot create a budget with zero or negative amount', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => 'expense',
    ]);

    $response = $this->actingAs($user)->post(route('budgets.store'), [
        'category_id' => $category->id,
        'period' => '2026-10',
        'amount' => 0,
    ]);

    $response->assertSessionHasErrors(['amount']);
});

test('user cannot create duplicate budget for same category and period', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => 'expense',
    ]);

    Budget::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'period' => '2026-10',
        'amount' => 1000000,
    ]);

    $response = $this->actingAs($user)->post(route('budgets.store'), [
        'category_id' => $category->id,
        'period' => '2026-10',
        'amount' => 2000000,
    ]);

    $response->assertSessionHasErrors(['category_id']);
});

test('user cannot create budget for income category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => 'income',
    ]);

    $response = $this->actingAs($user)->post(route('budgets.store'), [
        'category_id' => $category->id,
        'period' => '2026-10',
        'amount' => 1000000,
    ]);

    $response->assertSessionHasErrors(['category_id']);
});

test('user cannot create budget for another user category', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherCategory = Category::factory()->create([
        'user_id' => $otherUser->id,
        'type' => 'expense',
    ]);

    $response = $this->actingAs($user)->post(route('budgets.store'), [
        'category_id' => $otherCategory->id,
        'period' => '2026-10',
        'amount' => 1000000,
    ]);

    $response->assertSessionHasErrors(['category_id']);
});

test('user can update their budget amount', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->create([
        'user_id' => $user->id,
        'amount' => 1000000,
        'notes' => 'Awal',
    ]);

    $response = $this->actingAs($user)->put(route('budgets.update', $budget), [
        'amount' => 2500000,
        'notes' => 'Revisi anggaran',
    ]);

    $response->assertRedirect();
    expect((float) $budget->fresh()->amount)->toBe(2500000.0);
    expect($budget->fresh()->notes)->toBe('Revisi anggaran');
});

test('user cannot update another user budget', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherBudget = Budget::factory()->create([
        'user_id' => $otherUser->id,
        'amount' => 1000000,
    ]);

    $response = $this->actingAs($user)->put(route('budgets.update', $otherBudget), [
        'amount' => 2500000,
    ]);

    $response->assertForbidden();
});

test('user can delete their budget', function () {
    $user = User::factory()->create();
    $budget = Budget::factory()->create([
        'user_id' => $user->id,
    ]);

    $response = $this->actingAs($user)->delete(route('budgets.destroy', $budget));

    $response->assertRedirect();
    $this->assertDatabaseMissing('budgets', ['id' => $budget->id]);
});

test('user cannot delete another user budget', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $otherBudget = Budget::factory()->create([
        'user_id' => $otherUser->id,
    ]);

    $response = $this->actingAs($user)->delete(route('budgets.destroy', $otherBudget));

    $response->assertForbidden();
    $this->assertDatabaseHas('budgets', ['id' => $otherBudget->id]);
});

test('budget calculates spent correctly from expenses in period and ignores income', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'type' => 'expense',
    ]);

    $budget = Budget::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'period' => '2026-10',
        'amount' => 1000000,
    ]);

    // Expense transaction in October 2026
    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 300000,
        'date' => '2026-10-15',
    ]);

    // Another expense in October 2026
    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 200000,
        'date' => '2026-10-20',
    ]);

    // Transaction in September (should NOT count)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => 400000,
        'date' => '2026-09-25',
    ]);

    // Income transaction in October (should NOT count for expense budget)
    Transaction::factory()->create([
        'user_id' => $user->id,
        'account_id' => $account->id,
        'category_id' => $category->id,
        'type' => 'income',
        'amount' => 500000,
        'date' => '2026-10-10',
    ]);

    $response = $this->actingAs($user)->get(route('budgets.index', ['period' => '2026-10']));

    $response->assertOk();
    $response->assertInertia(function ($page) {
        $budgets = $page->toArray()['props']['budgets'];
        expect($budgets)->toHaveCount(1);
        expect($budgets[0]['spent'])->toEqual(500000);
        expect($budgets[0]['remaining'])->toEqual(500000);
        expect($budgets[0]['percentage'])->toEqual(50);
        expect($budgets[0]['is_over_budget'])->toBeFalse();
    });
});
