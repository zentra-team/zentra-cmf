<?php

namespace App\Services;

class PermissionRegistry
{
    private array $sections = [];

    public function loadFromConfig(): void
    {
        foreach (config('permissions.sections', []) as $key => $section) {
            if (!is_array($section)) {
                continue;
            }
            $this->sections[(string) $key] = [
                'label'       => (string) ($section['label'] ?? $key),
                'permissions' => is_array($section['permissions'] ?? null) ? $section['permissions'] : [],
            ];
        }
    }

    public function addSection(string $key, string $label, array $permissions): void
    {
        if (isset($this->sections[$key])) {
            $this->sections[$key]['permissions'] = array_merge(
                $this->sections[$key]['permissions'],
                $permissions,
            );

            return;
        }

        $this->sections[$key] = ['label' => $label, 'permissions' => $permissions];
    }

    public function addPermission(string $sectionKey, string $permKey, string $permLabel): void
    {
        if (!isset($this->sections[$sectionKey])) {
            return;
        }
        $this->sections[$sectionKey]['permissions'][$permKey] = $permLabel;
    }

    public function sections(): array
    {
        return $this->sections;
    }

    public function allPermissionKeys(): array
    {
        $keys = [];

        foreach ($this->sections as $section) {
            foreach (array_keys($section['permissions']) as $k) {
                $keys[] = (string) $k;
            }
        }

        return $keys;
    }
}
