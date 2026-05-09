<?php

namespace App\Services;

use App\Fields\FieldInterface;
use App\Models\Module;

class FieldManager
{
    private array $types = [];

    private bool $initialized = false;

    public function register(string $key, string $class): void
    {
        if (!is_subclass_of($class, FieldInterface::class)) {
            throw new \InvalidArgumentException(
                "Field class {$class} must implement " . FieldInterface::class,
            );
        }

        $this->types[$key] = $class;
    }

    public function registerMany(array $map): void
    {
        foreach ($map as $key => $class) {
            $this->register($key, $class);
        }
    }

    public function all(): array
    {
        $this->ensureInitialized();

        return $this->types;
    }

    public function get(string $key): ?string
    {
        $this->ensureInitialized();

        return $this->types[$key] ?? null;
    }

    public function has(string $key): bool
    {
        $this->ensureInitialized();

        return isset($this->types[$key]);
    }

    public function instance(string $key): ?FieldInterface
    {
        $class = $this->get($key);

        return $class ? app($class) : null;
    }

    public function groups(): array
    {
        $groups = [
            'text'     => ['label' => 'Текст', 'types' => []],
            'data'     => ['label' => 'Данные', 'types' => []],
            'date'     => ['label' => 'Дата и время', 'types' => []],
            'contact'  => ['label' => 'Контакты', 'types' => []],
            'design'   => ['label' => 'Оформление', 'types' => []],
            'file'     => ['label' => 'Файлы', 'types' => []],
            'relation' => ['label' => 'Связи', 'types' => []],
            'media'    => ['label' => 'Медиа', 'types' => []],
            'other'    => ['label' => 'Прочее', 'types' => []],
        ];

        foreach ($this->all() as $key => $class) {
            $instance = app($class);
            $group = $instance->getGroup();

            if (!isset($groups[$group])) {
                $group = 'other';
            }

            $groups[$group]['types'][$key] = [
                'name' => $instance->getName(),
                'icon' => $instance->getIcon(),
            ];
        }

        return array_filter($groups, fn ($g) => !empty($g['types']));
    }

    public function reset(): void
    {
        $this->types = [];
        $this->initialized = false;
    }

    private function ensureInitialized(): void
    {
        if ($this->initialized) {
            return;
        }
        $this->initialized = true;

        $config = config('fields', []);

        if (is_array($config)) {
            $this->registerMany($config);
        }

        $this->loadFromInstalledModules();
    }

    private function loadFromInstalledModules(): void
    {
        try {
            $modules = Module::query()->get(['sys_name']);
        } catch (\Throwable $e) {
            return;
        }

        foreach ($modules as $module) {
            $sysName = $module->sys_name;
            $file = base_path("modules/{$sysName}/fields.php");

            if (!file_exists($file)) {
                continue;
            }

            try {
                $types = require $file;

                if (!is_array($types)) {
                    continue;
                }
                $this->registerMany($types);
            } catch (\Throwable $e) {
                logger()->warning(
                    "FieldManager: failed to load fields from module '{$sysName}': "
                    . $e->getMessage(),
                );
            }
        }
    }
}
