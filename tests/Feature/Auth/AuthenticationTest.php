<?php

use App\Models\User;

test('login screen can be rendered', function () {
    $response = $this->get('/login');

    $response->assertStatus(200);
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post('/logout');

    $this->assertGuest();
    $response->assertRedirect('/');
});

test('user registered via google without password gets helpful error message when attempting manual login', function () {
    $user = User::factory()->create([
        'email' => 'google.user@example.com',
        'password' => null,
        'google_id' => 'google-999',
    ]);

    $response = $this->post('/login', [
        'email' => 'google.user@example.com',
        'password' => 'some-attempted-password',
    ]);

    $this->assertGuest();
    $response->assertSessionHasErrors([
        'email' => 'Akun ini terdaftar menggunakan Google. Silakan klik tombol "Masuk dengan Google" di atas, atau gunakan "Lupa sandi?" untuk membuat kata sandi baru.',
    ]);
});
