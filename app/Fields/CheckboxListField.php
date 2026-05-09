<?php

namespace App\Fields;

class CheckboxListField extends BaseField
{
    public function getType(): string
    {
        return 'checkbox_list';
    }

    public function getName(): string
    {
        return 'Список чекбоксов';
    }

    public function getIcon(): string
    {
        return 'bi-list-check';
    }

    public function getGroup(): string
    {
        return 'data';
    }

    public function getDatabaseColumn(): string
    {
        return 'jsonb';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $selected = $this->decodeValues($value);

        if (empty($selected)) {
            $default = (string) ($config['default'] ?? '');

            if ($default !== '') {
                $selected = array_filter(array_map('trim', explode("\n", $default)), fn ($s) => $s !== '');
            }
        }

        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $alias = $config['alias'] ?? uniqid();
        $options = $config['config']['options'] ?? [];

        $html = $label ? "<label class=\"form-label d-block\">{$label}</label>" : '';
        $html .= '<div class="ztr-checkbox-list" data-field-name="' . $name . '">';

        $i = 0;

        foreach ($options as $opt) {
            $opt = trim($opt);

            if ($opt === '') {
                continue;
            }
            $id = "field_{$alias}_c{$i}";
            $sel = in_array($opt, $selected, true) ? ' checked' : '';
            $optE = e($opt);
            $html .= '<div class="form-check">'
                   . "<input type=\"checkbox\" id=\"{$id}\" class=\"form-check-input ztr-checkbox-list-item\" value=\"{$optE}\"{$sel}>"
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
        if (!is_array($input)) {
            if (is_string($input) && $input !== '') {
                $decoded = json_decode($input, true);
                $input = is_array($decoded) ? $decoded : [];
            } else {
                $input = [];
            }
        }

        $values = [];

        foreach ($input as $v) {
            $v = trim((string) $v);

            if ($v !== '' && !in_array($v, $values, true)) {
                $values[] = $v;
            }
        }

        return json_encode($values, JSON_UNESCAPED_UNICODE);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $values = $this->decodeValues($value);

        if ($template !== null) {
            if (preg_match('/\[items\](.*?)\[\/items\]/s', $template, $m)) {
                $itemTpl = $m[1];
                $rendered = '';

                foreach ($values as $i => $v) {
                    $chunk = $itemTpl;
                    $chunk = str_replace('[value]', e((string) $v), $chunk);
                    $chunk = str_replace('[value:index]', (string) $i, $chunk);
                    $rendered .= $chunk;
                }

                return str_replace($m[0], $rendered, $template);
            }

            return $template;
        }

        return e(json_encode($values, JSON_UNESCAPED_UNICODE));
    }

    public function toArray(mixed $value): array
    {
        return $this->decodeValues($value);
    }

    public function fromArray(array $data): mixed
    {
        return json_encode(array_values($data), JSON_UNESCAPED_UNICODE);
    }

    public function getTemplateInfo(): ?array
    {
        $default = <<<TPL
[field:{alias}]
  <ul>
    [items]
      <li>[value]</li>
    [/items]
  </ul>
[/field:{alias}]
TPL;

        $hint = 'Парный тег с кастомным HTML.<br>'
              . 'Блок <code>[items]...[/items]</code> - шаблон одного выбранного значения.<br>'
              . 'Доступные токены:<br>'
              . '• <code>[value]</code> - текст варианта<br>'
              . '• <code>[value:index]</code> - порядковый номер (с 0)';

        return ['default' => $default, 'hint' => $hint];
    }

    private function decodeValues(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map(fn ($v) => trim((string) $v), $value), fn ($s) => $s !== ''));
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values(array_filter(array_map(fn ($v) => trim((string) $v), $decoded), fn ($s) => $s !== ''));
            }
        }

        return [];
    }
}
