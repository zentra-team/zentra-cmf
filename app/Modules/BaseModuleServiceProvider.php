<?php

namespace App\Modules;

use App\Models\Module;
use Illuminate\Support\ServiceProvider;

abstract class BaseModuleServiceProvider extends ServiceProvider
{
    private ?Module $moduleInstance = null;

    private bool $moduleResolved = false;

    protected function sysName(): string
    {
        return explode('\\', static::class)[1] ?? '';
    }

    protected function modulePath(string $append = ''): string
    {
        $base = base_path('modules/' . $this->sysName());

        return $append === '' ? $base : $base . '/' . ltrim($append, '/');
    }

    protected function module(): ?Module
    {
        if (!$this->moduleResolved) {
            $this->moduleInstance = Module::find($this->sysName());
            $this->moduleResolved = true;
        }

        return $this->moduleInstance;
    }

    protected function moduleConfig(string $key, mixed $default = null): mixed
    {
        $module = $this->module();

        return $module ? $module->getConfig($key, $default) : $default;
    }
}
