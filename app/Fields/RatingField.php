<?php

namespace App\Fields;

class RatingField extends BaseField
{
    public function getType(): string
    {
        return 'rating';
    }

    public function getName(): string
    {
        return 'Рейтинг (лайки)';
    }

    public function getIcon(): string
    {
        return 'bi-hand-thumbs-up';
    }

    public function getGroup(): string
    {
        return 'data';
    }

    public function getDatabaseColumn(): string
    {
        return 'integer';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());
        $current = $value ?? $config['default'] ?? '';
        $current = is_numeric($current) ? (int) $current : 0;

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= '<div class="input-group ztr-rating-field">';
        $html .= '<span class="input-group-text"><i class="bi bi-hand-thumbs-up-fill"></i></span>';
        $html .= "<input type=\"number\" id=\"{$id}\" name=\"{$name}\" class=\"form-control\" value=\"{$current}\" min=\"0\" step=\"1\">";
        $html .= '</div>';

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        if ($input === null || $input === '') {
            return 0;
        }

        $n = (int) $input;

        return $n < 0 ? 0 : $n;
    }

    public function toArray(mixed $value): array
    {
        return ['value' => is_numeric($value) ? (int) $value : 0];
    }
}
