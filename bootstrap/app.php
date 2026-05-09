<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

// Generate a minimal .env with APP_KEY if none exists (first-run installer bootstrap).
// This must happen before Laravel boots its encryption/session layer.
(static function (): void {
    $base = dirname(__DIR__);
    $envPath = $base . '/.env';

    if (file_exists($envPath)) {
        return;
    }

    $example = $base . '/.env.example';
    $content = file_exists($example) ? (file_get_contents($example) ?: '') : '';
    $key = 'base64:' . base64_encode(random_bytes(32));

    if (preg_match('/^APP_KEY=/m', $content)) {
        $content = preg_replace('/^APP_KEY=.*/m', 'APP_KEY=' . $key, $content);
    } else {
        $content .= "\nAPP_KEY={$key}\n";
    }

    $tmp = $envPath . '.tmp.' . getmypid();

    if (file_put_contents($tmp, $content) !== false) {
        rename($tmp, $envPath);
    }
})();

$basePath = dirname(__DIR__);

$publicPath = (static function (string $base): string {
    $envFile = $base . '/.env';
    if (file_exists($envFile)) {
        foreach (file($envFile) as $line) {
            if (preg_match('/^APP_PUBLIC_PATH=(.+)/', trim($line), $m)) {
                $path = trim($m[1], '"\'');
                if ($path !== '') {
                    return rtrim($path, '/');
                }
            }
        }
    }

    return $base . '/public';
})($basePath);

$app = Application::configure(basePath: $basePath)
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->prepend(\App\Http\Middleware\ForceHttps::class);
        $middleware->append(\App\Http\Middleware\EnsureInstalled::class);

        $middleware->alias([
            'auth.admin'   => \App\Http\Middleware\AdminAuthenticate::class,
            'admin.active' => \App\Http\Middleware\EnsureAdminActive::class,
            'perm'         => \App\Http\Middleware\CheckAdminPermission::class,
            'last.seen'    => \App\Http\Middleware\UpdateLastSeen::class,
            'api.token'    => \App\Http\Middleware\AuthenticateApiToken::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\NotFoundHttpException $e, $request) {
            if (\App\Support\SystemPaths::isSystem($request->path())) {
                return null;
            }
            \App\Services\Logger::error404($request);

            return null;
        });

        $exceptions->render(function (\Illuminate\Database\QueryException $e, $request) {
            \App\Services\Logger::dbError(
                message: $e->getMessage(),
                level:   'ERROR',
                query:   $e->getSql(),
                context: 'File: ' . $e->getFile() . ':' . $e->getLine(),
            );

            return null;
        });
    })->create();

$app->usePublicPath($publicPath);

return $app;
