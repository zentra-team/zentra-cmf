<?php

namespace App\Fields;

class ImageField extends BaseField
{
    public function getType(): string
    {
        return 'image';
    }

    public function getName(): string
    {
        return 'Изображение';
    }

    public function getIcon(): string
    {
        return 'bi-image';
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
        $data = is_array($value) ? $value : (is_string($value) ? json_decode($value, true) ?? [] : []);
        $path = e($data['path'] ?? '');
        $alt = e($data['alt'] ?? '');
        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $id = 'field_' . ($config['alias'] ?? uniqid());
        $hint = $config['description'] ?? '';
        $maxKb = (int) ($config['config']['max_size_kb'] ?? 0);
        $maxAttr = $maxKb > 0 ? " data-max-size-kb=\"{$maxKb}\"" : '';
        $rawFormats = trim($config['config']['accepted_formats'] ?? '');
        $accept = 'image/*';

        if ($rawFormats !== '') {
            $exts = array_filter(array_map(
                fn ($e) => '.' . ltrim(strtolower(trim($e)), '.'),
                explode(',', $rawFormats),
            ), fn ($e) => strlen($e) > 1);

            if (!empty($exts)) {
                $accept = implode(',', $exts);
            }
        }

        $html = $label ? "<label class=\"form-label\">{$label}</label>" : '';
        $html .= '<div class="mb-2">';
        $html .= '<div class="d-flex gap-2 align-items-center">';
        $html .= "<input type=\"file\" id=\"{$id}\" name=\"{$name}[file]\" class=\"form-control\" accept=\"{$accept}\" data-upload-type=\"image\"{$maxAttr}>";
        $html .= "<input type=\"text\" name=\"{$name}[alt]\" class=\"form-control\" value=\"{$alt}\" placeholder=\"Alt текст\">";
        $html .= '</div>';
        $html .= "<input type=\"hidden\" name=\"{$name}[path]\" value=\"{$path}\">";

        $previewHidden = $path === '' ? ' d-none' : '';
        $previewInner = $path !== ''
            ? '<img src="' . $path . "\" class=\"ztr-field-image-thumb\" alt=\"{$alt}\">"
            . '<button type="button" class="btn btn-link btn-sm p-0 text-danger ms-2 field-media-delete"'
            . ' title="Удалить изображение"><i class="bi bi-x-circle"></i></button>'
            : '';
        $html .= "<div class=\"mt-2 field-image-preview d-flex align-items-center{$previewHidden}\">{$previewInner}</div>";
        $html .= '</div>';

        if ($hint) {
            $html .= '<div class="form-text">' . e($hint) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        if (!is_array($input)) {
            return null;
        }

        return json_encode([
            'path' => $this->safePath(trim($input['path'] ?? '')),
            'alt'  => trim($input['alt'] ?? ''),
        ]);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $data = is_string($value) ? json_decode($value, true) ?? [] : (is_array($value) ? $value : []);
        $path = $this->safePath($data['path'] ?? '');
        $alt = $data['alt'] ?? '';

        if ($template !== null) {
            return $this->replaceParts($template, [$path, $alt], $template);
        }

        return $path ? '<img src="' . e($path) . '" alt="' . e($alt) . '">' : '';
    }

    public function toArray(mixed $value): array
    {
        return is_string($value) ? json_decode($value, true) ?? [] : (is_array($value) ? $value : []);
    }

    public function fromArray(array $data): mixed
    {
        return json_encode($data);
    }
}
