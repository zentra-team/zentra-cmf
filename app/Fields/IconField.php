<?php

namespace App\Fields;

class IconField extends BaseField
{
    public function getType(): string
    {
        return 'icon';
    }

    public function getName(): string
    {
        return 'Иконка (Bootstrap Icons)';
    }

    public function getIcon(): string
    {
        return 'bi-emoji-smile';
    }

    public function getGroup(): string
    {
        return 'media';
    }

    public function getDatabaseColumn(): string
    {
        return 'varchar';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $name = e($config['name'] ?? '');
        $current = trim((string) ($value ?? $config['default'] ?? ''));
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());
        $filter = trim((string) ($config['config']['category_filter'] ?? ''));
        $filterAttr = $filter !== '' ? ' data-category-filter="' . e($filter) . '"' : '';
        $currentE = e($current);
        $previewClass = $current !== '' ? 'bi ' . $currentE : '';

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<div class=\"ztr-icon-field\"{$filterAttr}>";
        $html .= '<div class="input-group">';
        $html .= "<span class=\"input-group-text ztr-icon-preview\"><i class=\"{$previewClass}\"></i></span>";
        $html .= "<input type=\"text\" id=\"{$id}\" name=\"{$name}\" class=\"form-control ztr-icon-input\" "
               . "value=\"{$currentE}\" placeholder=\"bi-heart\" autocomplete=\"off\">";
        $html .= '<button type="button" class="btn btn-outline-primary ztr-icon-pick">'
               . '<i class="bi bi-grid-3x3-gap"></i> Выбрать</button>';

        if ($current !== '') {
            $html .= '<button type="button" class="btn btn-outline-secondary ztr-icon-clear" title="Очистить">'
                   . '<i class="bi bi-x-lg"></i></button>';
        }

        $html .= '</div>';
        $html .= '</div>';

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        $v = trim((string) ($input ?? ''));

        if ($v === '') {
            return null;
        }

        if (!preg_match('/^[a-z0-9-]+$/i', $v)) {
            return null;
        }

        return $v;
    }
}
