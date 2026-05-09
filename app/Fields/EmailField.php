<?php

namespace App\Fields;

class EmailField extends BaseField
{
    public function getType(): string
    {
        return 'email';
    }

    public function getName(): string
    {
        return 'Email';
    }

    public function getIcon(): string
    {
        return 'bi-envelope';
    }

    public function getGroup(): string
    {
        return 'contact';
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
        $html .= "<input type=\"email\" id=\"{$id}\" name=\"{$name}\" class=\"form-control\""
               . " value=\"{$val}\" placeholder=\"user@example.com\" autocomplete=\"email\"{$maxAttr}>";

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        $str = trim((string) ($input ?? ''));

        if ($str === '') {
            return null;
        }

        return filter_var($str, FILTER_VALIDATE_EMAIL) ? strtolower($str) : null;
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $str = trim((string) ($value ?? ''));

        if ($str === '') {
            return '';
        }

        $safe = e($str);
        $mailto = 'mailto:' . $safe;

        if ($template !== null) {
            return str_replace(
                ['[value]', '[email]', '[href]'],
                [$safe, $safe, $mailto],
                $template,
            );
        }

        return '<a href="' . $mailto . '">' . $safe . '</a>';
    }
}
