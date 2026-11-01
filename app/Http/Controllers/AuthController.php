<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class AuthController extends Controller
{
    public function login() {
        session()->put('url.intended', url()->previous());
        return Socialite::driver('discord')->redirect();
    }

    public function callback() {
        $discordUser = (array) Socialite::driver('discord')->user();

        $user = User::where('auth_id', $discordUser['id'])->first();

        if ($user) {
            $user->update([
                'username'      => $discordUser['nickname'] ?? $discordUser['name'],
                'avatar'        => $discordUser['avatar'],
                'token'         => $discordUser['token'],
                'refresh_token' => $discordUser['refreshToken'],
            ]);
        } else {
            $user = User::create([
                'id'            => Str::uuid(),
                'auth_id'       => $discordUser['id'],
                'username'      => $discordUser['nickname'] ?? $discordUser['name'],
                'avatar'        => $discordUser['avatar'],
                'token'         => $discordUser['token'],
                'refresh_token' => $discordUser['refreshToken'],
            ]);
        }

        Auth::login($user);

        return redirect()->route('auth.login-success');
    }

    public function loginSuccess() {
        return Inertia::render('Auth/LoginSuccess', [
            'intended' => session()->pull('url.intended', '/'),
        ]);
    }

    public function logout() {
        Auth::logout();
        return redirect('/');
    }

    public function getUser(User $uid) {
        Auth::login($uid);
        return redirect()->route('auth.login-success');
    }
}