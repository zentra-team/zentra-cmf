<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminActive
{
    public function handle(Request $request, \Closure $next): Response
    {
        $user = Auth::guard('admin')->user();

        if ($user === null) {
            return $next($request);
        }

        if (!$user->is_active) {
            Auth::guard('admin')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('admin.login')
                ->with('toast_error', 'Учётная запись заблокирована');
        }

        return $next($request);
    }
}
