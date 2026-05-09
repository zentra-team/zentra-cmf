<?php

namespace App\Fields;

use Carbon\Carbon;

class TimeField extends BaseField
{
    public function getType(): string
    {
        return 'time';
    }

    public function getName(): string
    {
        return 'Время';
    }

    public function getIcon(): string
    {
        return 'bi-clock';
    }

    public function getGroup(): string
    {
        return 'date';
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

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<input type=\"time\" id=\"{$id}\" name=\"{$name}\" class=\"form-control\" value=\"{$val}\">";

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

        if (!preg_match('/^([01]\d|2[0-3]):[0-5]\d(:[0-5]\d)?$/', $str)) {
            return null;
        }

        return substr($str, 0, 5);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $str = trim((string) ($value ?? ''));

        if ($str === '') {
            return '';
        }

        $format = trim((string) ($config['display_format'] ?? 'H:i'));
        $display = $str;

        if ($format !== 'H:i') {
            try {
                $display = Carbon::parse($str)->translatedFormat($format);
            } catch (\Throwable) {
                $display = $str;
            }
        }

        if ($template !== null) {
            return str_replace('[value]', e($display), $template);
        }

        return e($display);
    }
}
