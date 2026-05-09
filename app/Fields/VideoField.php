<?php

namespace App\Fields;

class VideoField extends BaseField
{
    public function getType(): string
    {
        return 'video';
    }

    public function getName(): string
    {
        return 'Видео (универсальное)';
    }

    public function getIcon(): string
    {
        return 'bi-camera-video';
    }

    public function getGroup(): string
    {
        return 'media';
    }

    public function getDatabaseColumn(): string
    {
        return 'varchar';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $url = trim((string) ($value ?? $config['default'] ?? ''));
        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());
        $urlE = e($url);

        $previewIframe = '';
        $providerLabel = '';

        if ($url !== '') {
            $embed = self::buildEmbedUrl($url);

            if ($embed !== null) {
                $previewIframe = '<iframe src="' . e($embed['url']) . '" allowfullscreen></iframe>';
                $providerLabel = '<span class="badge bg-success-subtle text-success">' . e($embed['provider']) . '</span>';
            } else {
                $providerLabel = '<span class="badge bg-warning-subtle text-warning">Неизвестный провайдер - будет выведен URL текстом</span>';
            }
        }

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= '<div class="ztr-video-field">';
        $html .= "<input type=\"url\" id=\"{$id}\" name=\"{$name}\" class=\"form-control ztr-video-url\""
               . " value=\"{$urlE}\" placeholder=\"https://www.youtube.com/watch?v=… или vimeo.com/… или vk.com/video…\">";
        $html .= '<div class="ztr-video-info mt-1">' . $providerLabel . '</div>';
        $html .= '<div class="ztr-video-preview mt-2">' . $previewIframe . '</div>';
        $html .= '<div class="form-text">Поддерживаются: YouTube, Vimeo, RuTube, VK, Dailymotion. Другие URL сохранятся, но без iframe-предпросмотра.</div>';
        $html .= '</div>';

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        $url = trim((string) ($input ?? ''));

        return $url === '' ? null : $url;
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $url = trim((string) ($value ?? ''));

        if ($url === '') {
            return '';
        }

        $embed = self::buildEmbedUrl($url);
        $embedUrl = $embed['url'] ?? '';
        $provider = $embed['provider'] ?? '';

        if ($template !== null) {
            $template = str_replace('[value:url]', e($url), $template);
            $template = str_replace('[value:embed]', e($embedUrl), $template);
            $template = str_replace('[value:provider]', e($provider), $template);

            return $template;
        }

        if ($embedUrl !== '') {
            return '<iframe src="' . e($embedUrl) . '" frameborder="0" allowfullscreen class="ztr-field-video-frame"></iframe>';
        }

        return e($url);
    }

    public function getTemplateInfo(): ?array
    {
        $default = <<<TPL
[field:{alias}]
  <div class="video-wrap">
    <iframe src="[value:embed]" allowfullscreen></iframe>
  </div>
[/field:{alias}]
TPL;

        $hint = 'Парный тег с кастомным HTML вокруг iframe.<br>'
              . 'Доступные токены:<br>'
              . '• <code>[value:url]</code> - исходный URL<br>'
              . '• <code>[value:embed]</code> - embed-URL для <code>&lt;iframe src&gt;</code><br>'
              . '• <code>[value:provider]</code> - название платформы (youtube/vimeo/…)';

        return ['default' => $default, 'hint' => $hint];
    }

    public static function buildEmbedUrl(string $url): ?array
    {
        $url = trim($url);

        if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([a-zA-Z0-9_\-]{11})~i', $url, $m)) {
            return ['url' => 'https://www.youtube.com/embed/' . $m[1], 'provider' => 'youtube'];
        }

        if (preg_match('~vimeo\.com/(?:.+/)?(\d+)~i', $url, $m)) {
            return ['url' => 'https://player.vimeo.com/video/' . $m[1], 'provider' => 'vimeo'];
        }

        if (preg_match('~rutube\.ru/video/([a-z0-9]+)~i', $url, $m)) {
            return ['url' => 'https://rutube.ru/play/embed/' . $m[1], 'provider' => 'rutube'];
        }

        if (preg_match('~(?:vk\.com|vkvideo\.ru)/video(-?\d+)_(\d+)~i', $url, $m)) {
            return ['url' => 'https://vk.com/video_ext.php?oid=' . $m[1] . '&id=' . $m[2] . '&hd=2', 'provider' => 'vk'];
        }

        if (preg_match('~(?:dailymotion\.com/video/|dai\.ly/)([a-zA-Z0-9]+)~i', $url, $m)) {
            return ['url' => 'https://www.dailymotion.com/embed/video/' . $m[1], 'provider' => 'dailymotion'];
        }

        return null;
    }
}
