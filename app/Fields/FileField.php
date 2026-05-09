<?php

namespace App\Fields;

use App\Models\MediaFile;

class FileField extends BaseField
{
    public function getType(): string
    {
        return 'file';
    }

    public function getName(): string
    {
        return 'Файл для скачивания';
    }

    public function getIcon(): string
    {
        return 'bi-file-earmark-arrow-down';
    }

    public function getGroup(): string
    {
        return 'file';
    }

    public function getDatabaseColumn(): string
    {
        return 'jsonb';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $data = is_array($value) ? $value : (is_string($value) ? json_decode($value, true) ?? [] : []);
        $rawPath = $data['path'] ?? '';
        $path = e($rawPath);
        $desc = e($data['description'] ?? '');
        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $id = 'field_' . ($config['alias'] ?? uniqid());
        $hint = $config['description'] ?? '';
        $fileName = '';

        if ($rawPath !== '') {
            $fileName = basename($rawPath);

            if (preg_match('#^/media/([0-9a-f\-]{10,})$#i', $rawPath, $m)) {
                $media = MediaFile::where('uuid', $m[1])->first();

                if ($media) {
                    $fileName = $media->original_name;
                }
            }
        }

        $cfg = $config['config'] ?? [];
        $exts = trim((string) ($cfg['accepted_extensions'] ?? ''));
        $acceptAttr = '';

        if ($exts !== '') {
            $list = collect(explode(',', $exts))
                ->map(fn ($s) => '.' . ltrim(trim($s), '.'))
                ->filter(fn ($s) => $s !== '.')
                ->implode(',');

            if ($list !== '') {
                $acceptAttr = ' accept="' . e($list) . '"';
            }
        }

        $maxKb = (int) ($cfg['max_size_kb'] ?? 0);
        $maxAttr = $maxKb > 0 ? " data-max-size-kb=\"{$maxKb}\"" : '';

        $html = $label ? "<label class=\"form-label\">{$label}</label>" : '';
        $html .= '<div class="mb-2">';
        $html .= '<div class="d-flex gap-2 align-items-center">';
        $html .= "<input type=\"file\" id=\"{$id}\" name=\"{$name}[file]\" class=\"form-control\" data-upload-type=\"file\"{$acceptAttr}{$maxAttr}>";
        $html .= "<input type=\"text\" name=\"{$name}[description]\" class=\"form-control\" value=\"{$desc}\" placeholder=\"Описание файла\">";
        $html .= '</div>';
        $html .= "<input type=\"hidden\" name=\"{$name}[path]\" value=\"{$path}\">";

        $previewHidden = $rawPath === '' ? ' d-none' : '';
        $previewInner = $rawPath !== ''
            ? 'Текущий файл: <a href="' . $path . '" target="_blank" rel="noopener noreferrer">' . e($fileName) . '</a>'
            . '<button type="button" class="btn btn-link btn-sm p-0 text-danger ms-2 field-media-delete"'
            . ' title="Удалить файл"><i class="bi bi-x-circle"></i></button>'
            : '';
        $html .= "<div class=\"form-text field-file-preview mt-1{$previewHidden}\">{$previewInner}</div>";
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
            'path'        => $this->safePath(trim($input['path'] ?? '')),
            'description' => trim($input['description'] ?? ''),
        ]);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $data = is_string($value) ? json_decode($value, true) ?? [] : (is_array($value) ? $value : []);
        $path = $this->safePath($data['path'] ?? '');
        $desc = $data['description'] ?? '';

        if ($path === '') {
            return '';
        }

        $fileName = basename($path);

        if (preg_match('#^/media/([0-9a-f\-]{10,})$#i', $path, $m)) {
            $media = MediaFile::where('uuid', $m[1])->first();

            if ($media) {
                $fileName = $media->original_name;
            }
        }

        $linkText = $desc !== '' ? $desc : $fileName;

        if ($template !== null) {
            return $this->replaceParts($template, [$path, $desc, $linkText, $fileName], $template);
        }

        return '<a href="' . e($path) . '" rel="noopener noreferrer">' . e($linkText) . '</a>';
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
