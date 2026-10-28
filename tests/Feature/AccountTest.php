<?php

use App\Models\Account;
use App\Models\User;

test('unauthenticated users are redirected to login', function () {
    $response = $this->get(route('accounts.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view accounts page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('accounts.index'));

    $response->assertOk();
});

test('user can create a new account', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('accounts.store'), [
        'name' => 'Bank Mandiri Utama',
        'type' => 'bank',
        'account_number' => '1234567890',
        'initial_balance' => 1500000,
        'color' => '#2563eb',
        'icon' => 'Building2',
        'description' => 'Rekening operasional bulanan',
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('accounts', [
        'user_id' => $user->id,
        'name' => 'Bank Mandiri Utama',
        'type' => 'bank',
        'current_balance' => 1500000,
    ]);
});

test('user can update their own account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Name',
        'initial_balance' => 100000,
        'current_balance' => 100000,
    ]);

    $response = $this->actingAs($user)->put(route('accounts.update', $account), [
        'name' => 'New Name',
        'type' => 'bank',
        'initial_balance' => 200000,
        'is_active' => true,
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
        'name' => 'New Name',
        'initial_balance' => 200000,
        'current_balance' => 200000,
    ]);
});

test('user cannot update another users account', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user2->id, 'name' => 'Secret Account']);

    $response = $this->actingAs($user1)->put(route('accounts.update', $account), [
        'name' => 'Hacked Name',
        'type' => 'bank',
        'is_active' => true,
    ]);

    $response->assertForbidden();
});

test('user can delete their own account', function () {
    $user = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('accounts.destroy', $account));

    $response->assertRedirect();
    $this->assertDatabaseMissing('accounts', [
        'id' => $account->id,
    ]);
});

test('user cannot delete another users account', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $account = Account::factory()->create(['user_id' => $user2->id]);

    $response = $this->actingAs($user1)->delete(route('accounts.destroy', $account));

    $response->assertForbidden();
    $this->assertDatabaseHas('accounts', [
        'id' => $account->id,
    ]);
});
