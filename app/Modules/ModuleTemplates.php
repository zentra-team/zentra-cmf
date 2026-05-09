<?php

namespace App\Modules;

class ModuleTemplates
{
    public function overridesDir(string $sysName): string
    {
        return storage_path("app/module-templates/{$sysName}/front");
    }

    public function moduleDir(string $sysName): string
    {
        return base_path("modules/{$sysName}/views/front");
    }

    public function registerFrontNamespace(string $sysName): void
    {
        view()->replaceNamespace("module.{$sysName}.front", [
            $this->overridesDir($sysName),
            $this->moduleDir($sysName),
        ]);
    }

    public function templates(string $sysName): array
    {
        $dir = $this->moduleDir($sysName);

        if (!is_dir($dir)) {
            return [];
        }

        $items = [];

        foreach (glob($dir . '/*.blade.php') ?: [] as $file) {
            $view = basename($file, '.blade.php');
            $items[] = ['view' => $view, 'overridden' => $this->isOverridden($sysName, $view)];
        }

        usort($items, fn ($a, $b) => strcmp($a['view'], $b['view']));

        return $items;
    }

    public function isOverridden(string $sysName, string $view): bool
    {
        return $this->isValidView($view) && is_file($this->overridePath($sysName, $view));
    }

    public function defaultContent(string $sysName, string $view): ?string
    {
        if (!$this->isValidView($view)) {
            return null;
        }

        $path = $this->modulePath($sysName, $view);

        return is_file($path) ? (string) file_get_contents($path) : null;
    }

    public function effectiveContent(string $sysName, string $view): ?string
    {
        if ($this->isOverridden($sysName, $view)) {
            return (string) file_get_contents($this->overridePath($sysName, $view));
        }

        return $this->defaultContent($sysName, $view);
    }

    public function saveOverride(string $sysName, string $view, string $content): void
    {
        if ($this->defaultContent($sysName, $view) === null) {
            throw new \InvalidArgumentException('Шаблон не найден в модуле');
        }

        $dir = $this->overridesDir($sysName);

        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            throw new \RuntimeException('Не удалось создать директорию переопределений');
        }

        file_put_contents($this->overridePath($sysName, $view), $content);
    }

    public function resetOverride(string $sysName, string $view): void
    {
        if ($this->isOverridden($sysName, $view)) {
            @unlink($this->overridePath($sysName, $view));
        }
    }

    private function overridePath(string $sysName, string $view): string
    {
        return $this->overridesDir($sysName) . '/' . $view . '.blade.php';
    }

    private function modulePath(string $sysName, string $view): string
    {
        return $this->moduleDir($sysName) . '/' . $view . '.blade.php';
    }

    private function isValidView(string $view): bool
    {
        return preg_match('/^[A-Za-z0-9_-]{1,64}$/', $view) === 1;
    }
}
