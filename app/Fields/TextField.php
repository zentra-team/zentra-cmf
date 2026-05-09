<?php

namespace App\Fields;

class TextField extends BaseField
{
    public function getType(): string
    {
        return 'text';
    }

    public function getName(): string
    {
        return 'Однострочное текстовое';
    }

    public function getIcon(): string
    {
        return 'bi-input-cursor-text';
    }

    public function getGroup(): string
    {
        return 'text';
    }

    public function getDatabaseColumn(): string
    {
        return 'varchar';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $name = e($config['name'] ?? '');
        $val = e((string) ($value ?? $config['default'] ?? ''));
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());

        $maxlength = (int) ($config['config']['maxlength'] ?? 0);
        $maxAttr = $maxlength > 0 ? " maxlength=\"{$maxlength}\"" : '';

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<input type=\"text\" id=\"{$id}\" name=\"{$name}\" class=\"form-control\" value=\"{$val}\"{$maxAttr}>";

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
