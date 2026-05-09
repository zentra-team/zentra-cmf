<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    public function handle(Request $request, \Closure $next): Response
    {
        $user = Auth::guard('admin')->user();

        if ($user) {
            $key = 'last_seen_updated_' . $user->id;

            if (!$request->session()->has($key)) {
                DB::table('users')->where('id', $user->id)->update(['last_seen_at' => now()]);
                $request->session()->put($key, true);
                $request->session()->put($key . '_ts', now()->timestamp);
            } elseif (now()->timestamp - (int) $request->session()->get($key . '_ts', 0) > 60) {
                DB::table('users')->where('id', $user->id)->update(['last_seen_at' => now()]);
                $request->session()->put($key . '_ts', now()->timestamp);
            }
        }

        return $next($request);
    }
}
