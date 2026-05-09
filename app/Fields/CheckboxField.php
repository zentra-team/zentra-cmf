<?php

namespace App\Fields;

class CheckboxField extends BaseField
{
    public function getType(): string
    {
        return 'checkbox';
    }

    public function getName(): string
    {
        return 'Чекбокс';
    }

    public function getIcon(): string
    {
        return 'bi-check2-square';
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
        $checked = $value ? 'checked' : '';
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());

        $html = "<input type=\"hidden\" name=\"{$name}\" value=\"0\">";
        $html .= '<div class="form-check">';
        $html .= "<input type=\"checkbox\" id=\"{$id}\" name=\"{$name}\" class=\"form-check-input\" value=\"1\" {$checked}>";

        if ($label) {
            $html .= "<label class=\"form-check-label\" for=\"{$id}\">{$label}</label>";
        }

        $html .= '</div>';

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        return $input ? '1' : '0';
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $str = $value ? '1' : '0';

        if ($template !== null) {
            return str_replace('[value]', $str, $template);
        }

        return $str;
    }

    public function toArray(mixed $value): array
    {
        return ['value' => (bool) $value];
    }
}
