<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureInstalled
{
    public function handle(Request $request, \Closure $next): Response
    {
        $installed = file_exists(storage_path('.installed'));
        $path = $request->path();

        if ($path === 'up' || str_starts_with($path, 'assets/') || str_starts_with($path, 'admin/assets/')) {
            return $next($request);
        }

        $isInstallPath = ($path === 'install' || str_starts_with($path, 'install/'));

        if (!$installed && !$isInstallPath) {
            return redirect('/install');
        }

        if ($installed && $isInstallPath) {
            return redirect('/');
        }

        return $next($request);
    }
}
