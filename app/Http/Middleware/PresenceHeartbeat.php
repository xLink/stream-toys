<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Session;

class PresenceHeartbeat
{
    public function handle($request, Closure $next)
    {
        if ($user = $request->user()) {
            Session::put('last_seen_'.$user->id, now()->timestamp);
        }

        return $next($request);
    }
}