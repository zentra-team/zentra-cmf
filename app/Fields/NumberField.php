<?php

namespace App\Fields;

class NumberField extends BaseField
{
    public function getType(): string
    {
        return 'number';
    }

    public function getName(): string
    {
        return 'Числовое';
    }

    public function getIcon(): string
    {
        return 'bi-123';
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
        $val = e((string) ($value ?? $config['default'] ?? ''));
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());
        $cfg = $config['config'] ?? [];
        $minAttr = isset($cfg['min']) && $cfg['min'] !== '' && is_numeric($cfg['min']) ? ' min="' . e((string) $cfg['min']) . '"' : '';
        $maxAttr = isset($cfg['max']) && $cfg['max'] !== '' && is_numeric($cfg['max']) ? ' max="' . e((string) $cfg['max']) . '"' : '';
        $step = $cfg['step'] ?? 'any';
        $stepAttr = ' step="' . e((string) ($step !== '' ? $step : 'any')) . '"';

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<input type=\"number\" id=\"{$id}\" name=\"{$name}\" class=\"form-control\" value=\"{$val}\"{$minAttr}{$maxAttr}{$stepAttr}>";

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        if ($input === null || $input === '') {
            return null;
        }

        return is_numeric($input) ? $input : null;
    }

    public function toArray(mixed $value): array
    {
        return ['value' => is_numeric($value) ? (float) $value : null];
    }
}
