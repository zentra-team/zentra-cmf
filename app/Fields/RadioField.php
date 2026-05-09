<?php

namespace App\Fields;

class RadioField extends BaseField
{
    public function getType(): string
    {
        return 'radio';
    }

    public function getName(): string
    {
        return 'Радиокнопки';
    }

    public function getIcon(): string
    {
        return 'bi-record-circle';
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
        $alias = $config['alias'] ?? uniqid();
        $options = $config['config']['options'] ?? [];

        $html = $label ? "<label class=\"form-label d-block\">{$label}</label>" : '';
        $html .= '<div class="ztr-radio-field">';

        $i = 0;

        foreach ($options as $opt) {
            $opt = trim($opt);

            if ($opt === '') {
                continue;
            }
            $id = "field_{$alias}_r{$i}";
            $sel = $current === $opt ? ' checked' : '';
            $optE = e($opt);
            $html .= '<div class="form-check">'
                   . "<input type=\"radio\" id=\"{$id}\" name=\"{$name}\" class=\"form-check-input\" value=\"{$optE}\"{$sel}>"
                   . "<label class=\"form-check-label\" for=\"{$id}\">{$optE}</label>"
                   . '</div>';
            $i++;
        }

        $html .= '</div>';

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
