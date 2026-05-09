<?php

namespace App\Services;

use App\Models\Module;

class TagProcessor
{
    private ?array $activeModules = null;

    private array $context = [];

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    public function process(string $content): string
    {
        if (empty($content) || !str_contains($content, '[')) {
            return $content;
        }

        foreach ($this->getActiveModules() as $module) {
            $regex = $module->getTagRegex();

            if (!$regex) {
                continue;
            }

            $content = preg_replace_callback(
                $regex,
                function (array $matches) use ($module): string {
                    $params = isset($matches[1]) ? ltrim($matches[1], ':') : '';

                    return $this->callModuleFront($module, $params);
                },
                $content,
            ) ?? $content;
        }

        return $content;
    }

    public function flushCache(): void
    {
        $this->activeModules = null;
    }

    private function getActiveModules(): array
    {
        if ($this->activeModules === null) {
            $this->activeModules = Module::where('is_active', true)
                ->whereNotNull('tag')
                ->where('has_front', true)
                ->get()
                ->all();
        }

        return $this->activeModules;
    }

    private function callModuleFront(Module $module, string $params): string
    {
        try {
            if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $module->sys_name)) {
                return '';
            }

            $controllerFile = base_path("modules/{$module->sys_name}/Controllers/FrontController.php");

            if (!file_exists($controllerFile)) {
                return '';
            }

            require_once $controllerFile;

            $className = "Modules\\{$module->sys_name}\\Controllers\\FrontController";

            if (!class_exists($className)) {
                return '';
            }

            $controller = new $className($module);
            $controller->setContext($this->context);

            return $controller->handle($params);
        } catch (\Throwable $e) {
            logger()->error("TagProcessor: ошибка в модуле {$module->sys_name}: " . $e->getMessage());

            return '';
        }
    }
}
