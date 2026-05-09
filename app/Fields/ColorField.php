<?php

namespace App\Fields;

class ColorField extends BaseField
{
    public function getType(): string
    {
        return 'color';
    }

    public function getName(): string
    {
        return 'Цвет';
    }

    public function getIcon(): string
    {
        return 'bi-palette';
    }

    public function getGroup(): string
    {
        return 'design';
    }

    public function getDatabaseColumn(): string
    {
        return 'varchar';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $name = e($config['name'] ?? '');
        $default = (string) ($config['default'] ?? '#7c3aed');
        $rawVal = (string) ($value ?? $default);
        $format = strtolower(trim((string) ($config['config']['format'] ?? 'hex')));

        if (!in_array($format, ['hex', 'rgb', 'rgba'], true)) {
            $format = 'hex';
        }

        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());
        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';

        if ($format === 'hex') {
            if (!preg_match('/^#[0-9a-fA-F]{6}$/', $rawVal)) {
                $rawVal = $default;
            }

            $valSafe = e($rawVal);
            $html .= '<div class="d-flex align-items-center gap-2">';
            $html .= "<input type=\"color\" id=\"{$id}\" name=\"{$name}\" class=\"form-control form-control-color ztr-field-color-swatch\""
                   . " value=\"{$valSafe}\">";
            $html .= "<code class=\"text-muted\">{$valSafe}</code>";
            $html .= '</div>';
        } else {
            $placeholder = $format === 'rgba' ? 'rgba(124, 58, 237, 0.85)' : 'rgb(124, 58, 237)';
            $valSafe = e($rawVal);
            $html .= "<input type=\"text\" id=\"{$id}\" name=\"{$name}\" class=\"form-control\""
                   . " value=\"{$valSafe}\" placeholder=\"{$placeholder}\" autocomplete=\"off\">";
        }

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

        return $this->normalize($str);
    }

    private function normalize(string $str): ?string
    {
        $str = ltrim(trim($str), '#');

        if (preg_match('/^[0-9a-fA-F]{3}$/', $str)) {
            $str = $str[0] . $str[0] . $str[1] . $str[1] . $str[2] . $str[2];
        }

        if (preg_match('/^[0-9a-fA-F]{6}$/', $str)) {
            return '#' . strtolower($str);
        }

        if (preg_match('/^rgba?\s*\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*(?:,\s*[0-9.]+\s*)?\)$/i', $str)) {
            return preg_replace('/\s+/', ' ', strtolower($str));
        }

        return null;
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $str = trim((string) ($value ?? ''));

        if ($str === '') {
            return '';
        }

        $safe = e($str);

        if ($template !== null) {
            return str_replace(['[value]', '[hex]'], [$safe, $safe], $template);
        }

        return $safe;
    }
}
