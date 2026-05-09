<?php

namespace App\Modules;

class ModuleAutoloader
{
    private const PREFIX = 'Modules\\';

    private static bool $registered = false;

    public static function register(): void
    {
        if (self::$registered) {
            return;
        }

        spl_autoload_register([self::class, 'load']);
        self::$registered = true;
    }

    public static function load(string $class): void
    {
        if (!str_starts_with($class, self::PREFIX)) {
            return;
        }

        $relative = substr($class, strlen(self::PREFIX));
        $path = base_path('modules/' . str_replace('\\', '/', $relative) . '.php');

        if (is_file($path)) {
            require $path;
        }
    }
}
