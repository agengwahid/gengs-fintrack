<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as SymfonyRedirectResponse;

class GoogleAuthController extends Controller
{
    /**
     * Redirect the user to the Google authentication page.
     */
    public function redirect(): SymfonyRedirectResponse|RedirectResponse
    {
        $clientId = config('services.google.client_id');
        $clientSecret = config('services.google.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            return redirect()->route('login')->with(
                'error',
                'Kredensial Google OAuth belum dikonfigurasi di file .env (GOOGLE_CLIENT_ID & GOOGLE_CLIENT_SECRET).'
            );
        }

        return Socialite::driver('google')->redirect();
    }

    /**
     * Obtain the user information from Google and log in or register.
     */
    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('login')->with(
                'status',
                'Autentikasi Google dibatalkan atau tidak diizinkan.'
            );
        }

        try {
            $googleUser = Socialite::driver('google')->user();

            $email = $googleUser->getEmail();
            if (empty($email)) {
                return redirect()->route('login')->with(
                    'error',
                    'Akun Google tidak menyediakan alamat email yang valid.'
                );
            }

            // Find existing user by google_id or by email
            $user = User::where('google_id', $googleUser->getId())
                ->orWhere('email', $email)
                ->first();

            if ($user) {
                // Link Google ID and avatar if not already set
                $shouldSave = false;

                if (empty($user->google_id)) {
                    $user->google_id = $googleUser->getId();
                    $shouldSave = true;
                }

                if (empty($user->avatar) && $googleUser->getAvatar()) {
                    $user->avatar = $googleUser->getAvatar();
                    $shouldSave = true;
                }

                if (empty($user->email_verified_at)) {
                    $user->email_verified_at = now();
                    $shouldSave = true;
                }

                if ($shouldSave) {
                    $user->save();
                }

                // Ensure starter categories & accounts exist
                if ($user->categories()->count() === 0 || $user->accounts()->count() === 0) {
                    $user->seedDefaultFinances();
                }
            } else {
                // Register new user via Google
                $user = User::create([
                    'name' => $googleUser->getName() ?: ($googleUser->getNickname() ?: 'Pengguna Google'),
                    'email' => $email,
                    'google_id' => $googleUser->getId(),
                    'avatar' => $googleUser->getAvatar(),
                    'email_verified_at' => now(),
                    'timezone' => 'Asia/Jakarta',
                    'password' => null,
                ]);

                $user->seedDefaultFinances();

                event(new Registered($user));
            }

            Auth::login($user, remember: true);

            $request->session()->regenerate();

            return redirect()->intended(route('dashboard', absolute: false));
        } catch (\Throwable $e) {
            Log::error('Google OAuth Error: '.$e->getMessage(), [
                'exception' => $e,
            ]);

            return redirect()->route('login')->with(
                'error',
                'Gagal masuk menggunakan akun Google: '.$e->getMessage()
            );
        }
    }
}
