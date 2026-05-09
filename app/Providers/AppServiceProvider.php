<?php

namespace App\Providers;

use App\Models\Setting;
use App\Services\FieldManager;
use App\Services\PermissionRegistry;
use App\Services\PublicCacheManager;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(FieldManager::class);

        $this->app->singleton(PublicCacheManager::class, function () {
            $manager = new PublicCacheManager();
            $manager->addAuthDetector(fn (Request $r) => auth('admin')->check());

            return $manager;
        });

        $this->app->singleton(PermissionRegistry::class, function () {
            $registry = new PermissionRegistry();
            $registry->loadFromConfig();

            return $registry;
        });
    }

    public function boot(): void
    {
        $isHttps = !$this->app->runningInConsole() && (
            request()->isSecure() ||
            strtolower((string) request()->header('X-Forwarded-Proto')) === 'https' ||
            strtolower((string) request()->header('X-Forwarded-SSL')) === 'on'
        );

        if ($isHttps || str_starts_with(config('app.url', ''), 'https://')) {
            URL::forceScheme('https');
        }

        Paginator::useBootstrapFive();

        ResetPassword::createUrlUsing(function ($user, string $token) {
            return route('admin.password.reset', [
                'token' => $token,
                'email' => $user->email,
            ]);
        });

        $this->applyTimezone();
        $this->shareFormatSettings();
    }

    private function applyTimezone(): void
    {
        try {
            $tz = Setting::getValue('timezone', '');

            if ($tz === '' || !in_array($tz, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL), true)) {
                return;
            }

            config(['app.timezone' => $tz]);
            date_default_timezone_set($tz);

            config(['database.connections.pgsql.timezone' => $tz]);
            DB::statement("SET TIME ZONE '" . str_replace("'", "''", $tz) . "'");
        } catch (\Throwable) {
        }
    }

    private function shareFormatSettings(): void
    {
        try {
            $dateFormat = Setting::getValue('date_format', 'd.m.Y');
            $timeFormat = Setting::getValue('time_format', 'H:i');
        } catch (\Throwable) {
            $dateFormat = 'd.m.Y';
            $timeFormat = 'H:i';
        }

        View::share('dateFormat', $dateFormat);
        View::share('timeFormat', $timeFormat);
    }
}
