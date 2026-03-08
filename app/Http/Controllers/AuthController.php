<?php

namespace App\Http\Controllers;

use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\Session\Session;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function login() {
        return Socialite::driver('discord')->redirect();
    }

    public function callback() {
        $discordUser = (array) Socialite::driver('discord')->user();

        $user = User::updateOrCreate(
            [
                'auth_id' => $discordUser['id'],
            ],
            [
                'id' => Str::uuid(),
                'auth_id' => $discordUser['id'],
                'username' => $discordUser['nickname'] ?? $discordUser['name'],
                'avatar' => $discordUser['avatar'],
                'token' => $discordUser['token'],
                'refresh_token' => $discordUser['refreshToken'],
            ]
        );

        Auth::login($user);

        return redirect()->intended(route('tetris-mp.index'));
    }

    public function logout() {
        Auth::logout();
        return redirect()->route('tetris-mp.index');
    }

    public function getUser(User $uid) {
        Auth::login($uid);
        return redirect()->route('tetris-mp.index');
    }
}