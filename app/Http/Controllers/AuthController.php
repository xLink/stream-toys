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

        $roomId = session()->get('tetris-mp-room', null);
        if ($roomId !== null) {
            return redirect()->route('tetris-mp.room', ['room' => $roomId]);
        }
        return redirect()->route('tetris-mp.index');
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