<?php

namespace App\Services;

use App\Models\AdminLog;
use App\Models\ErrorLog404;
use App\Models\ErrorLogDb;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class Logger
{
    public static function adminAction(
        string $action,
        string $actionType = 'other',
        ?string $objectType = null,
        ?int $objectId = null,
        ?string $objectTitle = null,
    ): void {
        try {
            $user = auth('admin')->user();

            AdminLog::create([
                'user_id'      => $user?->id,
                'user_name'    => $user?->name ?? 'Система',
                'ip'           => request()->ip(),
                'action'       => $action,
                'action_type'  => $actionType,
                'object_type'  => $objectType,
                'object_id'    => $objectId,
                'object_title' => $objectTitle,
            ]);
        } catch (\Throwable $e) {
            Log::warning("Admin action log failed [{$actionType}]: {$action}", ['exception' => $e]);
        }
    }

    public static function error404(Request $request): void
    {
        $ext = strtolower(pathinfo($request->path(), PATHINFO_EXTENSION));

        if ($ext !== '' && in_array($ext, [
            'ico', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'svg', 'avif',
            'css', 'js', 'map',
            'woff', 'woff2', 'ttf', 'eot', 'otf',
            'txt', 'xml', 'json',
        ], true)) {
            return;
        }

        try {
            $key = '404:' . $request->ip();

            if (RateLimiter::tooManyAttempts($key, 1)) {
                return;
            }

            RateLimiter::hit($key, 86400);

            ErrorLog404::create([
                'ip'         => $request->ip(),
                'url'        => mb_substr($request->fullUrl(), 0, 1000),
                'referer'    => mb_substr($request->header('referer', ''), 0, 1000) ?: null,
                'user_agent' => mb_substr($request->userAgent() ?? '', 0, 500) ?: null,
            ]);
        } catch (\Throwable) {
        }
    }

    public static function dbError(
        string $message,
        string $level = 'ERROR',
        ?string $query = null,
        ?string $context = null,
    ): void {
        try {
            ErrorLogDb::create([
                'level'   => $level,
                'message' => $message,
                'query'   => $query,
                'context' => $context,
            ]);
        } catch (\Throwable) {
        }
    }
}
