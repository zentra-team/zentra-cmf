<?php

namespace App\Fields;

class SelectField extends BaseField
{
    public function getType(): string
    {
        return 'select';
    }

    public function getName(): string
    {
        return 'Выпадающий список';
    }

    public function getIcon(): string
    {
        return 'bi-menu-button-wide';
    }

    public function getGroup(): string
    {
        return 'data';
    }

    public function getDatabaseColumn(): string
    {
        return 'varchar';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $name = e($config['name'] ?? '');
        $current = (string) ($value ?? $config['default'] ?? '');
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());
        $options = $config['config']['options'] ?? [];

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<select id=\"{$id}\" name=\"{$name}\" class=\"form-select\">";
        $html .= '<option value="">— выберите —</option>';

        foreach ($options as $opt) {
            $opt = trim($opt);

            if ($opt === '') {
                continue;
            }
            $sel = $current === $opt ? ' selected' : '';
            $html .= '<option value="' . e($opt) . "\"{$sel}>" . e($opt) . '</option>';
        }

        $html .= '</select>';

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        return trim((string) ($input ?? ''));
    }
}
