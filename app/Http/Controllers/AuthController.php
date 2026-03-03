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
        $user = Socialite::driver('discord')->user();

        $objUser = User::updateOrCreate(
            [
                'auth_id' => $user->id,
            ],
            [
                'id' => Str::uuid(),
                'auth_id' => $user->id,
                'username' => $user->name,
                'avatar' => $user->avatar,
                'token' => $user->token,
                'refresh_token' => $user->refreshToken,
            ]
        );

        Auth::login($objUser);

        return redirect()->intended(route('tetris-mp.index'));
    }

    public function logout() {
        Auth::logout();
        return redirect()->route('tetris-mp.index');
    }

    public function getUser(User $user) {
        Auth::login($user);
        return redirect()->route('tetris-mp.index');
    }
}