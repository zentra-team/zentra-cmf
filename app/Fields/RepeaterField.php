<?php

namespace App\Fields;

use App\Services\FieldManager;

class RepeaterField extends BaseField
{
    public const ALLOWED_SUBTYPES = [
        'text', 'textarea', 'number',
        'checkbox', 'select', 'radio', 'icon', 'slider', 'rating',
        'image', 'file', 'url', 'email', 'phone',
        'date', 'datetime', 'time', 'color',
    ];

    public function getType(): string
    {
        return 'repeater';
    }

    public function getName(): string
    {
        return 'Репитер (повторяющиеся группы)';
    }

    public function getIcon(): string
    {
        return 'bi-collection';
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
        $groups = $this->decodeGroups($value);
        $cfg = $config['config'] ?? [];
        $subfields = is_array($cfg['subfields'] ?? null) ? $cfg['subfields'] : [];
        $maxItems = (int) ($cfg['max_items'] ?? 0);
        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $hint = $config['description'] ?? '';

        $html = $label ? "<label class=\"form-label\">{$label}</label>" : '';

        if (empty($subfields)) {
            $html .= '<div class="alert alert-warning py-2 mb-2 small">'
                   . '<i class="bi bi-exclamation-triangle me-1"></i>'
                   . 'У репитера нет настроенных подполей. Откройте «Параметры поля» → «Настроить подполя».'
                   . '</div>';

            return $html;
        }

        $html .= '<div class="ztr-repeater-field" data-field-name="' . $name . '" data-max-items="' . $maxItems . '">';
        $html .= '<div class="ztr-repeater-groups">';

        foreach ($groups as $idx => $groupData) {
            $html .= $this->renderGroup($name, $idx, $subfields, $groupData);
        }

        $html .= '</div>';
        $html .= '<template class="ztr-repeater-template">';
        $html .= $this->renderGroup($name, '__INDEX__', $subfields, []);
        $html .= '</template>';
        $html .= '<div class="d-flex gap-2 mt-2 align-items-center">';
        $html .= '<button type="button" class="btn btn-sm btn-outline-primary ztr-repeater-add">'
               . '<i class="bi bi-plus-circle me-1"></i>Добавить группу</button>';

        if ($maxItems > 0) {
            $html .= '<span class="text-muted ztr-repeater-hint">Максимум: ' . $maxItems . '</span>';
        }

        $html .= '</div>';
        $html .= '</div>';

        if ($hint) {
            $html .= '<div class="form-text">' . e($hint) . '</div>';
        }

        return $html;
    }

    private function renderGroup(string $namePrefix, int|string $idx, array $subfields, array $groupData): string
    {
        $idxSafe = $idx === '__INDEX__' ? '__INDEX__' : (string) $idx;
        $html = '<div class="ztr-repeater-group" data-idx="' . e($idxSafe) . '">';
        $html .= '<div class="ztr-repeater-group-header">';
        $html .= '<button type="button" class="ztr-repeater-drag btn btn-sm btn-link text-muted p-0" title="Перетащить"><i class="bi bi-grip-vertical"></i></button>';
        $html .= '<span class="ztr-repeater-group-title">Группа <span class="ztr-repeater-group-num">'
               . ($idx === '__INDEX__' ? 'N' : (int) $idx + 1) . '</span></span>';
        $html .= '<button type="button" class="ztr-repeater-collapse btn btn-sm btn-link text-muted p-0 ms-auto" title="Свернуть"><i class="bi bi-chevron-up"></i></button>';
        $html .= '<button type="button" class="ztr-repeater-del btn btn-sm btn-link text-danger p-0" title="Удалить группу"><i class="bi bi-x-circle"></i></button>';
        $html .= '</div>';
        $html .= '<div class="ztr-repeater-group-body">';

        $fm = app(FieldManager::class);

        foreach ($subfields as $sf) {
            $sfAlias = (string) ($sf['alias'] ?? '');
            $sfType = (string) ($sf['type'] ?? 'text');

            if ($sfAlias === '') {
                continue;
            }

            $instance = $fm->instance($sfType);

            if ($instance === null) {
                continue;
            }

            $subValue = $groupData[$sfAlias] ?? null;
            $subName = $namePrefix . '[' . $idxSafe . '][' . $sfAlias . ']';

            $subConfig = [
                'name'        => $subName,
                'label'       => $sf['label'] ?? $sfAlias,
                'alias'       => $sfAlias . '_' . (is_string($idxSafe) ? str_replace('_', '', $idxSafe) : $idxSafe),
                'description' => $sf['description'] ?? '',
                'default'     => $sf['default_value'] ?? '',
                'config'      => $sf['config'] ?? [],
            ];

            $html .= '<div class="ztr-repeater-subfield mb-2">';
            $html .= $instance->renderEdit($subValue, $subConfig);
            $html .= '</div>';
        }

        $html .= '</div>';
        $html .= '</div>';

        return $html;
    }

    public function save(mixed $input): mixed
    {
        if (!is_array($input)) {
            return json_encode([]);
        }

        $groups = [];

        foreach ($input as $groupData) {
            if (!is_array($groupData)) {
                continue;
            }
            $cleaned = [];

            foreach ($groupData as $sfAlias => $sfValue) {
                if (!is_string($sfAlias) || $sfAlias === '') {
                    continue;
                }
                $cleaned[$sfAlias] = $sfValue;
            }

            if (!empty($cleaned)) {
                $groups[] = $cleaned;
            }
        }

        return json_encode($groups, JSON_UNESCAPED_UNICODE);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $groups = $this->decodeGroups($value);
        $subfields = is_array($config['subfields'] ?? null) ? $config['subfields'] : [];

        if (empty($groups)) {
            return '';
        }

        if ($template !== null) {
            if (preg_match('/\[items\](.*?)\[\/items\]/s', $template, $m)) {
                $itemTpl = $m[1];
                $rendered = '';
                $fm = app(FieldManager::class);

                foreach ($groups as $i => $groupData) {
                    $chunk = $itemTpl;
                    $chunk = str_replace('[value:index]', (string) $i, $chunk);

                    foreach ($subfields as $sf) {
                        $sfAlias = (string) ($sf['alias'] ?? '');
                        $sfType = (string) ($sf['type'] ?? 'text');

                        if ($sfAlias === '') {
                            continue;
                        }

                        $sfValue = $groupData[$sfAlias] ?? null;
                        $instance = $fm->instance($sfType);
                        $sfOut = $instance !== null
                            ? $instance->output($sfValue, null, $sf['config'] ?? [])
                            : e((string) ($sfValue ?? ''));

                        $chunk = str_replace('[value:' . $sfAlias . ']', $sfOut, $chunk);
                    }

                    $rendered .= $chunk;
                }

                return str_replace($m[0], $rendered, $template);
            }

            return $template;
        }

        return e(json_encode($groups, JSON_UNESCAPED_UNICODE));
    }

    public function toArray(mixed $value): array
    {
        return $this->decodeGroups($value);
    }

    public function fromArray(array $data): mixed
    {
        return json_encode(array_values($data), JSON_UNESCAPED_UNICODE);
    }

    public function getTemplateInfo(): ?array
    {
        $default = <<<TPL
[field:{alias}]
  [items]
    <div class="repeater-item">
      <!-- доступные токены: [value:index], [value:АЛИАС_ПОДПОЛЯ] -->
      <h3>[value:title]</h3>
      <p>[value:description]</p>
    </div>
  [/items]
[/field:{alias}]
TPL;

        $hint = 'Парный тег с шаблоном одной группы.<br>'
              . 'Блок <code>[items]...[/items]</code> - шаблон одной группы.<br>'
              . 'Доступные токены:<br>'
              . '• <code>[value:index]</code> - порядковый номер группы (с 0)<br>'
              . '• <code>[value:АЛИАС_ПОДПОЛЯ]</code> - значение подполя группы (для image - путь, для checkbox - 0/1, и т.д.)';

        return ['default' => $default, 'hint' => $hint];
    }

    private function decodeGroups(mixed $value): array
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
