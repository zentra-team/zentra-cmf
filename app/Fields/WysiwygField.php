<?php

namespace App\Fields;

class WysiwygField extends BaseField
{
    public function getType(): string
    {
        return 'wysiwyg';
    }

    public function getName(): string
    {
        return 'WYSIWYG редактор';
    }

    public function getIcon(): string
    {
        return 'bi-textarea-t';
    }

    public function getGroup(): string
    {
        return 'text';
    }

    public function getDatabaseColumn(): string
    {
        return 'text';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $name = e($config['name'] ?? '');
        $val = (string) ($value ?? $config['default'] ?? '');
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<textarea id=\"{$id}\" name=\"{$name}\" class=\"form-control ztr-wysiwyg\">" . e($val) . '</textarea>';

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        return (string) ($input ?? '');
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $html = (string) ($value ?? '');

        if ($template !== null) {
            return str_replace('[value]', $html, $template);
        }

        return $html;
    }

    public function toArray(mixed $value): array
    {
        return ['html' => (string) ($value ?? '')];
    }

    public function fromArray(array $data): mixed
    {
        return $data['html'] ?? '';
    }
}
