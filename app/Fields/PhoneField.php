<?php

namespace App\Fields;

class PhoneField extends BaseField
{
    public function getType(): string
    {
        return 'phone';
    }

    public function getName(): string
    {
        return 'Телефон';
    }

    public function getIcon(): string
    {
        return 'bi-telephone';
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

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<input type=\"tel\" id=\"{$id}\" name=\"{$name}\" class=\"form-control\""
               . " value=\"{$val}\" placeholder=\"+7 (999) 123-45-67\" autocomplete=\"tel\">";

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

        return $str;
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $str = trim((string) ($value ?? ''));

        if ($str === '') {
            return '';
        }

        $telHref = preg_replace('/[^\d+]/', '', $str);

        if ($telHref === '' || $telHref === '+') {
            return '';
        }

        $telHref = 'tel:' . $telHref;
        $safeDisplay = e($str);
        $safeHref = e($telHref);

        if ($template !== null) {
            return str_replace(
                ['[value]', '[phone]', '[href]'],
                [$safeDisplay, $safeDisplay, $safeHref],
                $template,
            );
        }

        return '<a href="' . $safeHref . '">' . $safeDisplay . '</a>';
    }
}
