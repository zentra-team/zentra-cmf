<?php

namespace App\Fields;

class KeyValueField extends BaseField
{
    public function getType(): string
    {
        return 'keyvalue';
    }

    public function getName(): string
    {
        return 'Ключ-значение (произвольные пары)';
    }

    public function getIcon(): string
    {
        return 'bi-table';
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
        $pairs = $this->decodePairs($value);
        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $hint = $config['description'] ?? '';
        $maxItems = (int) ($config['config']['max_items'] ?? 0);
        $maxAttr = $maxItems > 0 ? ' data-max-items="' . $maxItems . '"' : '';

        $html = $label ? "<label class=\"form-label\">{$label}</label>" : '';
        $html .= '<div class="ztr-keyvalue-field" data-field-name="' . $name . '"' . $maxAttr . '>';
        $html .= '<div class="ztr-keyvalue-items">';

        foreach ($pairs as $pair) {
            if (!is_array($pair)) {
                continue;
            }
            $html .= $this->renderPair($pair['key'] ?? '', $pair['value'] ?? '');
        }

        $html .= '</div>';
        $html .= '<div class="d-flex gap-2 mt-2 align-items-center">';
        $html .= '<button type="button" class="btn btn-sm btn-outline-primary ztr-keyvalue-add">'
               . '<i class="bi bi-plus-circle me-1"></i>Добавить пару</button>';

        if ($maxItems > 0) {
            $html .= '<span class="text-muted small">максимум ' . $maxItems . ' пар</span>';
        }

        $html .= '</div>';
        $html .= '</div>';

        if ($hint) {
            $html .= '<div class="form-text">' . e($hint) . '</div>';
        }

        return $html;
    }

    private function renderPair(string $key, string $val): string
    {
        $keyE = e($key);
        $valE = e($val);

        return <<<HTML
<div class="ztr-keyvalue-item">
    <button type="button" class="ztr-keyvalue-drag btn btn-sm btn-link text-muted p-0" title="Перетащить"><i class="bi bi-grip-vertical"></i></button>
    <input type="text" class="form-control form-control-sm ztr-keyvalue-key" value="{$keyE}" placeholder="Ключ">
    <input type="text" class="form-control form-control-sm ztr-keyvalue-value" value="{$valE}" placeholder="Значение">
    <button type="button" class="ztr-keyvalue-del btn btn-sm btn-link text-danger p-0" title="Удалить"><i class="bi bi-x-circle"></i></button>
</div>
HTML;
    }

    public function save(mixed $input): mixed
    {
        if (!is_array($input)) {
            return json_encode([]);
        }

        $pairs = [];

        foreach ($input as $pair) {
            if (!is_array($pair)) {
                continue;
            }
            $key = trim((string) ($pair['key'] ?? ''));
            $val = (string) ($pair['value'] ?? '');

            if ($key === '') {
                continue;
            }
            $pairs[] = ['key' => $key, 'value' => $val];
        }

        return json_encode($pairs, JSON_UNESCAPED_UNICODE);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $pairs = $this->decodePairs($value);

        if (empty($pairs)) {
            return '';
        }

        if ($template !== null) {
            if (preg_match('/\[items\](.*?)\[\/items\]/s', $template, $m)) {
                $itemTpl = $m[1];
                $rendered = '';

                foreach ($pairs as $i => $pair) {
                    if (!is_array($pair)) {
                        continue;
                    }
                    $chunk = $itemTpl;
                    $chunk = str_replace('[value:key]', e((string) ($pair['key'] ?? '')), $chunk);
                    $chunk = str_replace('[value:value]', e((string) ($pair['value'] ?? '')), $chunk);
                    $chunk = str_replace('[value:index]', (string) $i, $chunk);
                    $rendered .= $chunk;
                }

                return str_replace($m[0], $rendered, $template);
            }

            return $template;
        }

        return e(json_encode($pairs, JSON_UNESCAPED_UNICODE));
    }

    public function getTemplateInfo(): ?array
    {
        $default = <<<TPL
[field:{alias}]
  <dl>
    [items]
      <dt>[value:key]</dt>
      <dd>[value:value]</dd>
    [/items]
  </dl>
[/field:{alias}]
TPL;

        $hint = 'Парный тег с кастомным HTML.<br>'
              . 'Блок <code>[items]...[/items]</code> - шаблон одной пары.<br>'
              . 'Доступные токены:<br>'
              . '• <code>[value:key]</code> - ключ<br>'
              . '• <code>[value:value]</code> - значение<br>'
              . '• <code>[value:index]</code> - порядковый номер (с 0)';

        return ['default' => $default, 'hint' => $hint];
    }

    public function toArray(mixed $value): array
    {
        return $this->decodePairs($value);
    }

    public function fromArray(array $data): mixed
    {
        return json_encode(array_values($data), JSON_UNESCAPED_UNICODE);
    }

    private function decodePairs(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }
}
