<?php

namespace App\Fields;

class SliderField extends BaseField
{
    public function getType(): string
    {
        return 'slider';
    }

    public function getName(): string
    {
        return 'Ползунок';
    }

    public function getIcon(): string
    {
        return 'bi-sliders';
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
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());

        $cfg = $config['config'] ?? [];
        $min = isset($cfg['min']) && is_numeric($cfg['min']) ? (float) $cfg['min'] : 0;
        $max = isset($cfg['max']) && is_numeric($cfg['max']) ? (float) $cfg['max'] : 100;
        $step = isset($cfg['step']) && is_numeric($cfg['step']) ? (float) $cfg['step'] : 1;
        $suffix = e((string) ($cfg['suffix'] ?? ''));

        if ($max <= $min) {
            $max = $min + 1;
        }

        if ($step <= 0) {
            $step = 1;
        }

        $current = $value ?? $config['default'] ?? $min;
        $current = is_numeric($current) ? (float) $current : $min;

        if ($current < $min) {
            $current = $min;
        }

        if ($current > $max) {
            $current = $max;
        }

        $minS = $this->numFmt($min);
        $maxS = $this->numFmt($max);
        $stepS = $this->numFmt($step);
        $curS = $this->numFmt($current);

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= '<div class="ztr-slider-field" data-suffix="' . $suffix . '">';
        $html .= '<div class="d-flex align-items-center gap-3">';
        $html .= '<span class="ztr-slider-edge text-muted">' . $minS . $suffix . '</span>';
        $html .= "<input type=\"range\" id=\"{$id}\" name=\"{$name}\" class=\"form-range ztr-slider-input\" "
               . "min=\"{$minS}\" max=\"{$maxS}\" step=\"{$stepS}\" value=\"{$curS}\">";
        $html .= '<span class="ztr-slider-edge text-muted">' . $maxS . $suffix . '</span>';
        $html .= '<span class="ztr-slider-value badge bg-primary">' . $curS . $suffix . '</span>';
        $html .= '</div>';
        $html .= '</div>';

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

        return is_numeric($input) ? (string) $input : null;
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $suffix = (string) ($config['suffix'] ?? '');
        $str = is_numeric($value) ? $this->numFmt((float) $value) . $suffix : (string) $value;

        if ($template !== null) {
            return str_replace('[value]', e($str), $template);
        }

        return e($str);
    }

    public function toArray(mixed $value): array
    {
        return ['value' => is_numeric($value) ? (float) $value : null];
    }

    private function numFmt(float $n): string
    {
        if (floor($n) == $n) {
            return (string) (int) $n;
        }

        return rtrim(rtrim(number_format($n, 4, '.', ''), '0'), '.');
    }
}
