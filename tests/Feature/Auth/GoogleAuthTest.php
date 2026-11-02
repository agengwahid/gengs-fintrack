<?php

use App\Models\User;
use Illuminate\Support\Facades\Config;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Contracts\User as SocialiteUserContract;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

test('google redirect warns when credentials are not configured', function () {
    Config::set('services.google.client_id', null);
    Config::set('services.google.client_secret', null);

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
});

test('google redirect redirects to google oauth when configured', function () {
    Config::set('services.google.client_id', 'dummy-client-id');
    Config::set('services.google.client_secret', 'dummy-client-secret');

    $providerMock = Mockery::mock(Provider::class);
    $providerMock->shouldReceive('redirect')
        ->once()
        ->andReturn(new SymfonyRedirectResponse('https://accounts.google.com/o/oauth2/auth'));

    Socialite::shouldReceive('driver')
        ->with('google')
        ->once()
        ->andReturn($providerMock);

    $response = $this->get(route('auth.google.redirect'));

    $response->assertRedirect('https://accounts.google.com/o/oauth2/auth');
});

test('new user can register and log in via google callback', function () {
    Config::set('services.google.client_id', 'dummy-client-id');
    Config::set('services.google.client_secret', 'dummy-client-secret');

    $googleUserMock = Mockery::mock(SocialiteUserContract::class);
    $googleUserMock->shouldReceive('getId')->andReturn('google-unique-id-123');
    $googleUserMock->shouldReceive('getName')->andReturn('Muhammad Ageng Google');
    $googleUserMock->shouldReceive('getNickname')->andReturn('ageng');
    $googleUserMock->shouldReceive('getEmail')->andReturn('ageng.google@gmail.com');
    $googleUserMock->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/photo.jpg');

    $providerMock = Mockery::mock(Provider::class);
    $providerMock->shouldReceive('user')
        ->once()
        ->andReturn($googleUserMock);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->once()
        ->andReturn($providerMock);

    $response = $this->get(route('auth.google.callback'));

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $this->assertDatabaseHas('users', [
        'email' => 'ageng.google@gmail.com',
        'google_id' => 'google-unique-id-123',
        'name' => 'Muhammad Ageng Google',
    ]);

    $user = User::where('email', 'ageng.google@gmail.com')->first();
    expect($user->categories()->count())->toBeGreaterThan(0);
    expect($user->accounts()->count())->toBeGreaterThan(0);
});

test('existing user by email is linked with google id upon callback', function () {
    Config::set('services.google.client_id', 'dummy-client-id');
    Config::set('services.google.client_secret', 'dummy-client-secret');

    $existingUser = User::factory()->create([
        'email' => 'existing.user@gmail.com',
        'google_id' => null,
        'avatar' => null,
    ]);

    $googleUserMock = Mockery::mock(SocialiteUserContract::class);
    $googleUserMock->shouldReceive('getId')->andReturn('google-id-456');
    $googleUserMock->shouldReceive('getName')->andReturn('Existing User');
    $googleUserMock->shouldReceive('getNickname')->andReturn(null);
    $googleUserMock->shouldReceive('getEmail')->andReturn('existing.user@gmail.com');
    $googleUserMock->shouldReceive('getAvatar')->andReturn('https://lh3.googleusercontent.com/avatar.jpg');

    $providerMock = Mockery::mock(Provider::class);
    $providerMock->shouldReceive('user')
        ->once()
        ->andReturn($googleUserMock);

    Socialite::shouldReceive('driver')
        ->with('google')
        ->once()
        ->andReturn($providerMock);

    $response = $this->get(route('auth.google.callback'));

    $this->assertAuthenticatedAs($existingUser);
    $response->assertRedirect(route('dashboard', absolute: false));

    $existingUser->refresh();
    expect($existingUser->google_id)->toBe('google-id-456');
    expect($existingUser->avatar)->toBe('https://lh3.googleusercontent.com/avatar.jpg');
});

test('google callback handles cancellation', function () {
    $response = $this->get(route('auth.google.callback', ['error' => 'access_denied']));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('status');
});

test('google callback handles socialite exceptions gracefully', function () {
    $providerMock = Mockery::mock(Provider::class);
    $providerMock->shouldReceive('user')
        ->once()
        ->andThrow(new Exception('Invalid OAuth token'));

    Socialite::shouldReceive('driver')
        ->with('google')
        ->once()
        ->andReturn($providerMock);

    $response = $this->get(route('auth.google.callback'));

    $this->assertGuest();
    $response->assertRedirect(route('login'));
    $response->assertSessionHas('error');
});

