<?php

namespace App\Fields;

use App\Models\Document;
use App\Models\Setting;
use App\Support\DocumentUrl;

class RelationMultiField extends BaseField
{
    public function getType(): string
    {
        return 'relation_multi';
    }

    public function getName(): string
    {
        return 'Связь с документами (несколько)';
    }

    public function getIcon(): string
    {
        return 'bi-link';
    }

    public function getGroup(): string
    {
        return 'relation';
    }

    public function getDatabaseColumn(): string
    {
        return 'jsonb';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $ids = $this->decodeIds($value);
        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $hint = $config['description'] ?? '';
        $cfg = $config['config'] ?? [];
        $rubricIds = is_array($cfg['rubric_ids'] ?? null) ? array_map('intval', $cfg['rubric_ids']) : [];
        $maxItems = (int) ($cfg['max_items'] ?? 0);
        $rubricFilterAttr = !empty($rubricIds) ? ' data-rubric-ids="' . e(implode(',', $rubricIds)) . '"' : '';

        $html = $label ? "<label class=\"form-label\">{$label}</label>" : '';
        $html .= '<div class="ztr-rel-multi" data-field-name="' . $name . '" data-max-items="' . $maxItems . '"' . $rubricFilterAttr . '>';
        $html .= '<div class="ztr-rel-multi-items">';

        if (!empty($ids)) {
            $docs = Document::with('rubric')->whereIn('id', $ids)->get()->keyBy('id');

            foreach ($ids as $id) {
                $doc = $docs->get($id);

                if (!$doc) {
                    continue;
                }
                $html .= $this->renderChip($doc->id, $doc->title, $doc->rubric?->title ?? '');
            }
        }

        $html .= '</div>';
        $html .= '<div class="ztr-rel-multi-search position-relative">';
        $html .= '<input type="text" class="form-control ztr-rel-multi-input" placeholder="Начните вводить название документа…" autocomplete="off">';
        $html .= '<div class="ztr-rel-multi-results d-none"></div>';
        $html .= '</div>';

        if ($maxItems > 0) {
            $html .= '<div class="form-text ztr-rel-multi-hint">Максимум: ' . $maxItems . '</div>';
        }

        $html .= '</div>';

        if ($hint) {
            $html .= '<div class="form-text">' . e($hint) . '</div>';
        }

        return $html;
    }

    private function renderChip(int $id, string $title, string $rubricTitle): string
    {
        $titleE = e($title);
        $rubricE = e($rubricTitle);
        $rubricBlock = $rubricTitle !== ''
            ? "<span class=\"ztr-rel-multi-chip-rubric text-muted\">· {$rubricE}</span>"
            : '';

        return <<<HTML
<div class="ztr-rel-multi-chip" data-id="{$id}">
    <button type="button" class="ztr-rel-multi-drag btn btn-sm btn-link text-muted p-0" title="Перетащить"><i class="bi bi-grip-vertical"></i></button>
    <i class="bi bi-file-earmark-text"></i>
    <span class="ztr-rel-multi-chip-title">{$titleE}</span>
    {$rubricBlock}
    <button type="button" class="ztr-rel-multi-del btn btn-sm btn-link text-danger p-0 ms-auto" title="Убрать"><i class="bi bi-x-circle"></i></button>
</div>
HTML;
    }

    public function save(mixed $input): mixed
    {
        $ids = [];

        if (is_array($input)) {
            foreach ($input as $v) {
                $n = (int) $v;

                if ($n > 0 && !in_array($n, $ids, true)) {
                    $ids[] = $n;
                }
            }
        } elseif (is_string($input) && $input !== '') {
            $decoded = json_decode($input, true);

            if (is_array($decoded)) {
                foreach ($decoded as $v) {
                    $n = (int) $v;

                    if ($n > 0 && !in_array($n, $ids, true)) {
                        $ids[] = $n;
                    }
                }
            }
        }

        return json_encode($ids);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $ids = $this->decodeIds($value);

        if (empty($ids)) {
            return '';
        }

        $docs = Document::with('rubric')->whereIn('id', $ids)->get()->keyBy('id');
        $suffix = (string) Setting::getValue('url_suffix', '');
        $ordered = [];

        foreach ($ids as $id) {
            $doc = $docs->get($id);

            if ($doc) {
                $ordered[] = $doc;
            }
        }

        if ($template !== null) {
            if (preg_match('/\[items\](.*?)\[\/items\]/s', $template, $m)) {
                $itemTpl = $m[1];
                $rendered = '';

                foreach ($ordered as $i => $doc) {
                    $url = DocumentUrl::build($doc->rubric?->alias, $doc->alias, $suffix);
                    $chunk = $itemTpl;
                    $chunk = str_replace('[value:id]', (string) $doc->id, $chunk);
                    $chunk = str_replace('[value:title]', e($doc->title), $chunk);
                    $chunk = str_replace('[value:url]', e($url), $chunk);
                    $chunk = str_replace('[value:rubric_alias]', e($doc->rubric?->alias ?? ''), $chunk);
                    $chunk = str_replace('[value:rubric_title]', e($doc->rubric?->title ?? ''), $chunk);
                    $chunk = str_replace('[value:index]', (string) $i, $chunk);
                    $rendered .= $chunk;
                }

                return str_replace($m[0], $rendered, $template);
            }

            return $template;
        }

        $html = '<ul class="rel-multi">';

        foreach ($ordered as $doc) {
            $url = DocumentUrl::build($doc->rubric?->alias, $doc->alias, $suffix);
            $html .= '<li><a href="' . e($url) . '">' . e($doc->title) . '</a></li>';
        }

        $html .= '</ul>';

        return $html;
    }

    public function toArray(mixed $value): array
    {
        return $this->decodeIds($value);
    }

    public function fromArray(array $data): mixed
    {
        return json_encode(array_values(array_map('intval', $data)));
    }

    public function getTemplateInfo(): ?array
    {
        $default = <<<TPL
[field:{alias}]
  <ul>
    [items]
      <li><a href="[value:url]">[value:title]</a> <span class="rubric">[value:rubric_title]</span></li>
    [/items]
  </ul>
[/field:{alias}]
TPL;

        $hint = 'Парный тег с кастомным HTML.<br>'
              . 'Блок <code>[items]...[/items]</code> - шаблон одного связанного документа.<br>'
              . 'Доступные токены:<br>'
              . '• <code>[value:id]</code> - ID документа<br>'
              . '• <code>[value:title]</code> - заголовок<br>'
              . '• <code>[value:url]</code> - URL документа<br>'
              . '• <code>[value:rubric_alias]</code> - алиас рубрики<br>'
              . '• <code>[value:rubric_title]</code> - название рубрики<br>'
              . '• <code>[value:index]</code> - порядковый номер (с 0)';

        return ['default' => $default, 'hint' => $hint];
    }

    private function decodeIds(mixed $value): array
    {
        if (is_array($value)) {
            return array_values(array_filter(array_map('intval', $value), fn ($n) => $n > 0));
        }

        if (is_string($value) && $value !== '') {
            $decoded = json_decode($value, true);

            if (is_array($decoded)) {
                return array_values(array_filter(array_map('intval', $decoded), fn ($n) => $n > 0));
            }
        }

        return [];
    }
}
