<?php

namespace App\Fields;

use App\Models\Setting;

class MapField extends BaseField
{
    public const DEFAULT_LAT = 55.7558;
    public const DEFAULT_LNG = 37.6173;

    public function getType(): string
    {
        return 'map';
    }

    public function getName(): string
    {
        return 'Карта (точка на карте)';
    }

    public function getIcon(): string
    {
        return 'bi-geo-alt';
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
        $data = $this->decode($value);
        $lat = (string) ($data['lat'] ?? '');
        $lng = (string) ($data['lng'] ?? '');
        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $provider = Setting::getValue('maps_provider', 'yandex');
        $key = $provider === 'google'
            ? Setting::getValue('google_maps_api_key', '')
            : Setting::getValue('yandex_maps_api_key', '');
        $hasKey = $key !== '';

        $html = $label ? "<label class=\"form-label\">{$label}</label>" : '';
        $html .= '<div class="ztr-map-field" data-provider="' . e($provider) . '">';
        $html .= '<div class="row g-2 mb-2">';
        $html .= "<div class=\"col-6\"><input type=\"number\" name=\"{$name}[lat]\" class=\"form-control form-control-sm ztr-map-lat\""
               . ' value="' . e($lat) . '" placeholder="Широта (55.7558)" step="any"></div>';
        $html .= "<div class=\"col-6\"><input type=\"number\" name=\"{$name}[lng]\" class=\"form-control form-control-sm ztr-map-lng\""
               . ' value="' . e($lng) . '" placeholder="Долгота (37.6173)" step="any"></div>';
        $html .= '</div>';

        if (!$hasKey) {
            $providerName = $provider === 'google' ? 'Google Maps' : 'Яндекс.Карт';
            $html .= '<div class="alert alert-warning py-2 mb-0 small">'
                   . '<i class="bi bi-exclamation-triangle me-1"></i>'
                   . 'Чтобы выбирать координаты на интерактивной карте, настройте API-ключ ' . $providerName
                   . ' в <a href="' . e(route('admin.settings')) . '?tab=maps">Настройки → Карты</a>. '
                   . 'Координаты также можно ввести руками.'
                   . '</div>';
        } else {
            $html .= '<div class="ztr-map-canvas" data-has-key="1"></div>';
            $html .= '<div class="form-text">Кликните на карте, чтобы поставить маркер.</div>';
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
            return null;
        }

        $lat = $input['lat'] ?? null;
        $lng = $input['lng'] ?? null;

        if ($lat === null || $lat === '' || $lng === null || $lng === '') {
            return null;
        }

        if (!is_numeric($lat) || !is_numeric($lng)) {
            return null;
        }

        $lat = (float) $lat;
        $lng = (float) $lng;

        if ($lat < -90 || $lat > 90) {
            return null;
        }

        if ($lng < -180 || $lng > 180) {
            return null;
        }

        return json_encode([
            'lat' => $lat,
            'lng' => $lng,
        ]);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $data = $this->decode($value);

        if (empty($data) || !isset($data['lat'], $data['lng'])) {
            return '';
        }

        $lat = (float) $data['lat'];
        $lng = (float) $data['lng'];

        $provider = Setting::getValue('maps_provider', 'yandex');

        if ($template !== null) {
            $template = str_replace('[value:lat]', (string) $lat, $template);
            $template = str_replace('[value:lng]', (string) $lng, $template);
            $template = str_replace('[value:provider]', e($provider), $template);
            $template = str_replace('[value:embed]', e($this->embedUrl($lat, $lng, $provider)), $template);

            return $template;
        }

        return '<iframe src="' . e($this->embedUrl($lat, $lng, $provider)) . '"'
             . ' width="600" height="400" frameborder="0" allowfullscreen'
             . ' class="ztr-field-map-frame"></iframe>';
    }

    private function embedUrl(float $lat, float $lng, string $provider): string
    {
        if ($provider === 'google') {
            $key = (string) Setting::getValue('google_maps_api_key', '');

            if ($key !== '') {
                return 'https://www.google.com/maps/embed/v1/place?key=' . urlencode($key)
                     . '&q=' . $lat . ',' . $lng . '&zoom=15';
            }
        }

        return 'https://yandex.ru/map-widget/v1/?ll=' . $lng . ',' . $lat
             . '&z=15&pt=' . $lng . ',' . $lat . ',pm2rdm';
    }

    public function getTemplateInfo(): ?array
    {
        $default = <<<TPL
[field:{alias}]
  <div class="map-wrap">
    <iframe src="[value:embed]" allowfullscreen></iframe>
    <p class="coords">[value:lat], [value:lng]</p>
  </div>
[/field:{alias}]
TPL;

        $hint = 'Парный тег с кастомным HTML.<br>'
              . 'Доступные токены:<br>'
              . '• <code>[value:lat]</code> - широта<br>'
              . '• <code>[value:lng]</code> - долгота<br>'
              . '• <code>[value:embed]</code> - embed-URL для <code>&lt;iframe&gt;</code><br>'
              . '• <code>[value:provider]</code> - yandex/google';

        return ['default' => $default, 'hint' => $hint];
    }

    public function toArray(mixed $value): array
    {
        return $this->decode($value);
    }

    public function fromArray(array $data): mixed
    {
        return json_encode($data);
    }

    private function decode(mixed $value): array
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
