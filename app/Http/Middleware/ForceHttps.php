<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ForceHttps
{
    public function handle(Request $request, \Closure $next): Response
    {
        if (!str_starts_with(config('app.url', ''), 'https://')) {
            return $next($request);
        }

        if ($request->isSecure()) {
            return $next($request);
        }

        if (strtolower((string) $request->header('X-Forwarded-Proto')) === 'https' ||
            strtolower((string) $request->header('X-Forwarded-SSL')) === 'on') {
            return $next($request);
        }

        return redirect()->secure($request->getRequestUri(), 301);
    }
}
