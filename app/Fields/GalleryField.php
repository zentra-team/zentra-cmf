<?php

namespace App\Fields;

class GalleryField extends BaseField
{
    public function getType(): string
    {
        return 'gallery';
    }

    public function getName(): string
    {
        return 'Галерея изображений';
    }

    public function getIcon(): string
    {
        return 'bi-images';
    }

    public function getGroup(): string
    {
        return 'media';
    }

    public function getDatabaseColumn(): string
    {
        return 'jsonb';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $items = $this->decodeItems($value);
        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $hint = $config['description'] ?? '';
        $cfg = $config['config'] ?? [];
        $maxItems = (int) ($cfg['max_items'] ?? 0);
        $maxKb = (int) ($cfg['max_size_kb'] ?? 0);
        $maxKbAttr = $maxKb > 0 ? ' data-max-size-kb="' . $maxKb . '"' : '';

        $html = $label ? "<label class=\"form-label\">{$label}</label>" : '';
        $html .= '<div class="ztr-gallery-field" data-field-name="' . $name . '" data-max-items="' . $maxItems . '"' . $maxKbAttr . '>';
        $html .= '<div class="ztr-gallery-items">';

        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }
            $html .= $this->renderItem($it['path'] ?? '', $it['alt'] ?? '', $it['description'] ?? '');
        }

        $html .= '</div>';
        $html .= '<div class="d-flex gap-2 mt-2 align-items-center">';
        $html .= '<input type="file" class="d-none ztr-gallery-file" accept="image/*" multiple data-upload-type="image"' . $maxKbAttr . '>';
        $html .= '<button type="button" class="btn btn-sm btn-outline-primary ztr-gallery-add">'
               . '<i class="bi bi-plus-circle me-1"></i>Добавить изображение</button>';
        $hints = [];

        if ($maxItems > 0) {
            $hints[] = 'максимум ' . $maxItems . ' шт';
        }

        if ($maxKb > 0) {
            $hints[] = 'до ' . $maxKb . ' КБ';
        }

        if (!empty($hints)) {
            $html .= '<span class="text-muted ztr-gallery-hint">' . implode(', ', $hints) . '</span>';
        }

        $html .= '</div>';
        $html .= '</div>';

        if ($hint) {
            $html .= '<div class="form-text">' . e($hint) . '</div>';
        }

        return $html;
    }

    private function renderItem(string $path, string $alt, string $desc): string
    {
        $pathE = e($this->safePath($path));
        $altE = e($alt);
        $descE = e($desc);

        return <<<HTML
<div class="ztr-gallery-item" data-path="{$pathE}">
    <button type="button" class="ztr-gallery-drag btn btn-sm btn-link text-muted" title="Перетащить для сортировки"><i class="bi bi-grip-vertical"></i></button>
    <div class="ztr-gallery-thumb"><img src="{$pathE}" alt="{$altE}"></div>
    <div class="ztr-gallery-inputs">
        <input type="text" class="form-control form-control-sm ztr-gallery-alt" value="{$altE}" placeholder="Alt (для SEO и скринридеров)">
        <input type="text" class="form-control form-control-sm ztr-gallery-desc" value="{$descE}" placeholder="Описание (caption под картинкой)">
    </div>
    <button type="button" class="ztr-gallery-del btn btn-sm btn-link text-danger" title="Удалить"><i class="bi bi-x-circle"></i></button>
</div>
HTML;
    }

    public function save(mixed $input): mixed
    {
        if (!is_array($input)) {
            return json_encode([]);
        }

        $items = [];

        foreach ($input as $it) {
            if (!is_array($it)) {
                continue;
            }
            $path = $this->safePath(trim($it['path'] ?? ''));

            if ($path === '') {
                continue;
            }
            $items[] = [
                'path'        => $path,
                'alt'         => trim($it['alt'] ?? ''),
                'description' => trim($it['description'] ?? ''),
            ];
        }

        return json_encode($items, JSON_UNESCAPED_UNICODE);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $items = $this->decodeItems($value);

        if (empty($items)) {
            return '';
        }

        if ($template !== null) {
            if (preg_match('/\[items\](.*?)\[\/items\]/s', $template, $m)) {
                $itemTpl = $m[1];
                $rendered = '';

                foreach ($items as $i => $it) {
                    if (!is_array($it)) {
                        continue;
                    }
                    $chunk = $itemTpl;
                    $chunk = str_replace('[value:path]', e($it['path'] ?? ''), $chunk);
                    $chunk = str_replace('[value:alt]', e($it['alt'] ?? ''), $chunk);
                    $chunk = str_replace('[value:description]', e($it['description'] ?? ''), $chunk);
                    $chunk = str_replace('[value:index]', (string) $i, $chunk);
                    $rendered .= $chunk;
                }

                return str_replace($m[0], $rendered, $template);
            }

            return $template;
        }

        $html = '<div class="gallery">';

        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }

            $path = $this->safePath($it['path'] ?? '');

            if ($path === '') {
                continue;
            }

            $alt = $it['alt'] ?? '';
            $desc = $it['description'] ?? '';
            $html .= '<figure><img src="' . e($path) . '" alt="' . e($alt) . '">';

            if ($desc !== '') {
                $html .= '<figcaption>' . e($desc) . '</figcaption>';
            }

            $html .= '</figure>';
        }

        $html .= '</div>';

        return $html;
    }

    public function toArray(mixed $value): array
    {
        return $this->decodeItems($value);
    }

    public function fromArray(array $data): mixed
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

    public function getTemplateInfo(): ?array
    {
        $default = <<<TPL
[field:{alias}]
  <div class="gallery">
    [items]
      <figure>
        <img src="[value:path]" alt="[value:alt]">
        <figcaption>[value:description]</figcaption>
      </figure>
    [/items]
  </div>
[/field:{alias}]
TPL;

        $hint = 'Парный тег с кастомным HTML.<br>'
              . 'Блок <code>[items]...[/items]</code> - шаблон одного элемента.<br>'
              . 'Доступные токены:<br>'
              . '• <code>[value:path]</code> - URL картинки<br>'
              . '• <code>[value:alt]</code> - Alt-текст<br>'
              . '• <code>[value:description]</code> - описание (caption)<br>'
              . '• <code>[value:index]</code> - порядковый номер (с 0)';

        return ['default' => $default, 'hint' => $hint];
    }

    private function decodeItems(mixed $value): array
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
