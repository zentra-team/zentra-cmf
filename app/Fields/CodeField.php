<?php

namespace App\Fields;

class CodeField extends BaseField
{
    public function getType(): string
    {
        return 'code';
    }

    public function getName(): string
    {
        return 'Редактор кода';
    }

    public function getIcon(): string
    {
        return 'bi-code-slash';
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
        $val = e((string) ($value ?? $config['default'] ?? ''));
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());

        $mode = trim((string) ($config['config']['mode'] ?? 'text'));

        if ($mode === '') {
            $mode = 'text';
        }
        $modeAttr = ' data-ace-mode="' . e($mode) . '"';

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<textarea id=\"{$id}\" name=\"{$name}\" class=\"form-control font-monospace ztr-codefield ztr-field-code\""
               . " spellcheck=\"false\"{$modeAttr}>{$val}</textarea>";

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
        $code = (string) ($value ?? '');
        $escaped = e($code);

        if ($template !== null) {
            return str_replace(['[value]', '[raw]'], [$escaped, $code], $template);
        }

        return "<pre><code>{$escaped}</code></pre>";
    }
}
