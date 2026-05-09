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
            [$optVal, $optLabel] = $this->splitOption($opt);
            $sel = $current === $optVal ? ' selected' : '';
            $html .= '<option value="' . e($optVal) . '"' . $sel . '>' . e($optLabel) . '</option>';
        }

        $html .= '</select>';

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $raw = (string) ($value ?? '');
        $label = $raw;

        foreach ($config['options'] ?? [] as $opt) {
            [$optVal, $optLabel] = $this->splitOption(trim($opt));

            if ($optVal === $raw) {
                $label = $optLabel;
                break;
            }
        }

        if ($template !== null) {
            return str_replace('[value]', e($label), $template);
        }

        return e($label);
    }

    public function save(mixed $input): mixed
    {
        return trim((string) ($input ?? ''));
    }
}
