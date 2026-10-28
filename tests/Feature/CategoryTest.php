<?php

use App\Models\Category;
use App\Models\User;

test('unauthenticated users are redirected to login when visiting categories', function () {
    $response = $this->get(route('categories.index'));

    $response->assertRedirect(route('login'));
});

test('authenticated user can view categories page', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('categories.index'));

    $response->assertOk();
});

test('user can create a new category', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('categories.store'), [
        'name' => 'Langganan Software',
        'type' => 'expense',
        'color' => '#8b5cf6',
        'icon' => 'Laptop',
        'description' => 'Biaya langganan SaaS, hosting, dll',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('categories', [
        'user_id' => $user->id,
        'name' => 'Langganan Software',
        'type' => 'expense',
    ]);
});

test('user can update their own category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create([
        'user_id' => $user->id,
        'name' => 'Old Category',
        'type' => 'expense',
    ]);

    $response = $this->actingAs($user)->put(route('categories.update', $category), [
        'name' => 'Updated Category',
        'type' => 'expense',
        'color' => '#10b981',
        'icon' => 'Zap',
        'description' => 'Updated note',
    ]);

    $response->assertRedirect();
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
        'name' => 'Updated Category',
        'color' => '#10b981',
    ]);
});

test('user cannot update another users category', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user2->id, 'name' => 'Secret Category']);

    $response = $this->actingAs($user1)->put(route('categories.update', $category), [
        'name' => 'Hacked Category',
        'type' => 'expense',
    ]);

    $response->assertForbidden();
});

test('user can delete their own category', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user->id]);

    $response = $this->actingAs($user)->delete(route('categories.destroy', $category));

    $response->assertRedirect();
    $this->assertDatabaseMissing('categories', [
        'id' => $category->id,
    ]);
});

test('user cannot delete another users category', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $category = Category::factory()->create(['user_id' => $user2->id]);

    $response = $this->actingAs($user1)->delete(route('categories.destroy', $category));

    $response->assertForbidden();
    $this->assertDatabaseHas('categories', [
        'id' => $category->id,
    ]);
});
