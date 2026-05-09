<?php

namespace App\Fields;

class UrlField extends BaseField
{
    public function getType(): string
    {
        return 'url';
    }

    public function getName(): string
    {
        return 'URL / Ссылка';
    }

    public function getIcon(): string
    {
        return 'bi-link-45deg';
    }

    public function getGroup(): string
    {
        return 'contact';
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

        $maxlength = (int) ($config['config']['maxlength'] ?? 0);
        $maxAttr = $maxlength > 0 ? " maxlength=\"{$maxlength}\"" : '';

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<input type=\"url\" id=\"{$id}\" name=\"{$name}\" class=\"form-control\""
               . " value=\"{$val}\" placeholder=\"https://example.com\" autocomplete=\"url\"{$maxAttr}>";

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

        if (!preg_match('#^[a-z][a-z0-9+\-.]*://#i', $str) && !str_starts_with($str, '/')) {
            $str = 'https://' . $str;
        }

        if (str_starts_with($str, '/')) {
            return $str;
        }

        return filter_var($str, FILTER_VALIDATE_URL) ? $str : null;
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $str = trim((string) ($value ?? ''));

        if ($str === '') {
            return '';
        }

        $safe = e($str);

        if ($template !== null) {
            return str_replace(
                ['[value]', '[url]', '[href]'],
                [$safe, $safe, $safe],
                $template,
            );
        }

        return '<a href="' . $safe . '" target="_blank" rel="noopener noreferrer">' . $safe . '</a>';
    }
}
