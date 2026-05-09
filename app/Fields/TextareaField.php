<?php

namespace App\Fields;

class TextareaField extends BaseField
{
    public function getType(): string
    {
        return 'textarea';
    }

    public function getName(): string
    {
        return 'Многострочное текстовое';
    }

    public function getIcon(): string
    {
        return 'bi-text-paragraph';
    }

    public function getGroup(): string
    {
        return 'text';
    }

    public function getDatabaseColumn(): string
    {
        return 'text';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $name = e($config['name'] ?? '');
        $val = e((string) ($value ?? $config['default'] ?? ''));
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());

        $rows = (int) ($config['config']['rows'] ?? 5);

        if ($rows < 1) {
            $rows = 5;
        }
        $maxlength = (int) ($config['config']['maxlength'] ?? 0);
        $maxAttr = $maxlength > 0 ? " maxlength=\"{$maxlength}\"" : '';

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<textarea id=\"{$id}\" name=\"{$name}\" class=\"form-control\" rows=\"{$rows}\"{$maxAttr}>{$val}</textarea>";

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        return trim((string) ($input ?? ''));
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $str = nl2br(e((string) ($value ?? '')));

        if ($template !== null) {
            return str_replace('[value]', $str, $template);
        }

        return $str;
    }
}
