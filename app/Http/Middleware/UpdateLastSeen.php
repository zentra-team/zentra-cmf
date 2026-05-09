<?php

namespace App\Http\Middleware;

use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class UpdateLastSeen
{
    public function __construct(private readonly UserService $userService)
    {
    }

    public function handle(Request $request, \Closure $next): Response
    {
        $user = Auth::guard('admin')->user();

        if ($user) {
            $key = 'last_seen_updated_' . $user->id;

            if (!$request->session()->has($key)) {
                $this->userService->touchLastSeen($user->id);
                $request->session()->put($key, true);
                $request->session()->put($key . '_ts', now()->timestamp);
            } elseif (now()->timestamp - (int) $request->session()->get($key . '_ts', 0) > 60) {
                $this->userService->touchLastSeen($user->id);
                $request->session()->put($key . '_ts', now()->timestamp);
            }
        }

        return $next($request);
    }
}
