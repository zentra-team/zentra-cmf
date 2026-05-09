<?php

namespace App\Fields;

use Carbon\Carbon;

class DateField extends BaseField
{
    public function getType(): string
    {
        return 'date';
    }

    public function getName(): string
    {
        return 'Дата';
    }

    public function getIcon(): string
    {
        return 'bi-calendar-date';
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
        $html .= "<input type=\"date\" id=\"{$id}\" name=\"{$name}\" class=\"form-control\" value=\"{$val}\">";

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

        try {
            return Carbon::parse($str)->format('Y-m-d');
        } catch (\Throwable) {
            return null;
        }
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $str = trim((string) ($value ?? ''));

        if ($str === '') {
            return '';
        }

        try {
            $date = Carbon::parse($str)->locale('ru');
        } catch (\Throwable) {
            return '';
        }

        $format = trim((string) ($config['display_format'] ?? 'd.m.Y'));

        if ($format === '') {
            $format = 'd.m.Y';
        }
        $display = $date->translatedFormat($format);

        if ($template !== null) {
            return str_replace(
                ['[value]', '[iso]', '[display]'],
                [e($display), e($str), e($display)],
                $template,
            );
        }

        return e($display);
    }
}
