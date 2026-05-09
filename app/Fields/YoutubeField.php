<?php

namespace App\Fields;

class YoutubeField extends BaseField
{
    public function getType(): string
    {
        return 'youtube';
    }

    public function getName(): string
    {
        return 'YouTube видео';
    }

    public function getIcon(): string
    {
        return 'bi-youtube';
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
        $cfgW = (int) ($config['config']['default_width'] ?? 560);
        $cfgH = (int) ($config['config']['default_height'] ?? 315);
        $cfgFs = !empty($config['config']['default_fullscreen']);
        $url = e($data['url'] ?? '');
        $w = e((string) ($data['width'] ?? $cfgW));
        $h = e((string) ($data['height'] ?? $cfgH));
        $fs = (isset($data['fullscreen']) ? !empty($data['fullscreen']) : $cfgFs) ? 'checked' : '';
        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());

        $html = $label ? "<label class=\"form-label\">{$label}</label>" : '';
        $html .= "<input type=\"url\" id=\"{$id}\" name=\"{$name}[url]\" class=\"form-control mb-2\""
               . " value=\"{$url}\" placeholder=\"https://www.youtube.com/watch?v=...\">";
        $html .= '<div class="row g-2 mb-2">';
        $html .= "<div class=\"col-4\"><input type=\"number\" name=\"{$name}[width]\" class=\"form-control\""
               . " value=\"{$w}\" placeholder=\"Ширина\"></div>";
        $html .= "<div class=\"col-4\"><input type=\"number\" name=\"{$name}[height]\" class=\"form-control\""
               . " value=\"{$h}\" placeholder=\"Высота\"></div>";
        $html .= '<div class="col-4"><div class="form-check mt-2">'
               . "<input type=\"checkbox\" name=\"{$name}[fullscreen]\" class=\"form-check-input\" value=\"1\" {$fs}>"
               . '<label class="form-check-label">Полный экран</label></div></div>';
        $html .= '</div>';

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        if (!is_array($input) || empty($input['url'])) {
            return null;
        }

        $url = trim($input['url']);

        preg_match('/(?:v=|youtu\.be\/)([a-zA-Z0-9_\-]{11})/', $url, $m);
        $videoId = $m[1] ?? '';

        return json_encode([
            'url'        => $url,
            'video_id'   => $videoId,
            'width'      => max(100, (int) ($input['width'] ?? 560)),
            'height'     => max(60, (int) ($input['height'] ?? 315)),
            'fullscreen' => !empty($input['fullscreen']),
        ]);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $data = is_string($value) ? json_decode($value, true) ?? [] : (is_array($value) ? $value : []);
        $videoId = $data['video_id'] ?? '';
        $w = $data['width'] ?? 560;
        $h = $data['height'] ?? 315;
        $fs = !empty($data['fullscreen']) ? ' allowfullscreen' : '';
        $url = $data['url'] ?? '';

        if (!$videoId) {
            return '';
        }

        $embed = "https://www.youtube.com/embed/{$videoId}";

        if ($template !== null) {
            return $this->replaceParts($template, [$url, $w, $h, $data['fullscreen'] ? '1' : '0', $embed], $template);
        }

        return "<iframe width=\"{$w}\" height=\"{$h}\" src=\"{$embed}\" frameborder=\"0\"{$fs}></iframe>";
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
