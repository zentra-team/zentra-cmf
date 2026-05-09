<?php

namespace App\Providers;

use App\Models\Module;
use App\Modules\ModuleAutoloader;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        ModuleAutoloader::register();
    }

    public function boot(): void
    {
        foreach ($this->activeModuleSysNames() as $sysName) {
            $this->registerModuleProvider($sysName);
        }
    }

    private function activeModuleSysNames(): array
    {
        try {
            if (!Schema::hasTable('modules')) {
                return [];
            }

            return Module::where('is_active', true)
                ->pluck('sys_name')
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    private function registerModuleProvider(string $sysName): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $sysName)) {
            return;
        }

        $providerFile = base_path("modules/{$sysName}/Provider.php");

        if (!is_file($providerFile)) {
            return;
        }

        $providerClass = "Modules\\{$sysName}\\Provider";

        try {
            if (class_exists($providerClass) && is_subclass_of($providerClass, ServiceProvider::class)) {
                $this->app->register($providerClass);
            }
        } catch (\Throwable $e) {
            Log::error("Провайдер модуля «{$sysName}» не загружен: " . $e->getMessage());
        }
    }
}
