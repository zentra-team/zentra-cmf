<?php

namespace App\Fields;

use Carbon\Carbon;

class DateTimeField extends BaseField
{
    public function getType(): string
    {
        return 'datetime';
    }

    public function getName(): string
    {
        return 'Дата и время';
    }

    public function getIcon(): string
    {
        return 'bi-calendar-event';
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
        $raw = (string) ($value ?? $config['default'] ?? '');

        $val = '';

        if ($raw !== '') {
            try {
                $val = e(Carbon::parse($raw)->format('Y-m-d\TH:i'));
            } catch (\Throwable) {
                $val = '';
            }
        }

        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<input type=\"datetime-local\" id=\"{$id}\" name=\"{$name}\" class=\"form-control\" value=\"{$val}\">";

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
            return Carbon::parse($str)->format('Y-m-d H:i:s');
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
            $dt = Carbon::parse($str)->locale('ru');
        } catch (\Throwable) {
            return '';
        }

        $format = trim((string) ($config['display_format'] ?? 'd.m.Y H:i'));

        if ($format === '') {
            $format = 'd.m.Y H:i';
        }
        $display = $dt->translatedFormat($format);
        $iso = $dt->format('Y-m-d H:i:s');

        if ($template !== null) {
            return str_replace(
                ['[value]', '[iso]', '[display]', '[date]', '[time]'],
                [
                    e($display), e($iso), e($display),
                    e($dt->translatedFormat('d.m.Y')),
                    e($dt->format('H:i')),
                ],
                $template,
            );
        }

        return e($display);
    }
}
