<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveGeneralSettingsRequest;
use App\Http\Requests\Admin\SaveMapsSettingsRequest;
use App\Http\Requests\Admin\SaveSeoSettingsRequest;
use App\Http\Requests\Admin\SendTestEmailRequest;
use App\Models\Setting;
use App\Services\CacheService;
use App\Services\Logger;
use App\Support\Format;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Process\Process;
use App\Services\SitemapGenerator;
use App\Services\RssGenerator;
use App\Services\ApiJsonGenerator;
use App\Services\PublicCacheManager;
use App\Http\Requests\Admin\SavePublicCacheSettingsRequest;

class SettingsController extends Controller
{
    private const ENV_WHITELIST = [
        'app' => [
            'APP_NAME' => ['label' => 'Название приложения', 'type' => 'text'],
            'APP_URL'  => ['label' => 'URL сайта', 'type' => 'url'],
            'APP_ENV'  => ['label'    => 'Окружение', 'type' => 'select',
                            'options' => ['production' => 'production', 'local' => 'local',
                                          'staging'    => 'staging', 'testing' => 'testing']],
            'APP_DEBUG' => ['label' => 'Режим отладки', 'type' => 'bool'],
        ],
        'session' => [
            'SESSION_LIFETIME' => ['label' => 'Время сессии (минут)', 'type' => 'number'],
            'SESSION_DRIVER'   => ['label'   => 'Драйвер сессий', 'type' => 'select',
                                   'options' => ['file'  => 'file', 'database' => 'database',
                                                 'redis' => 'redis', 'cookie' => 'cookie']],
        ],
        'cache' => [
            'CACHE_STORE' => ['label'   => 'Хранилище кэша', 'type' => 'select',
                              'options' => ['file'  => 'file', 'database' => 'database',
                                            'redis' => 'redis', 'array' => 'array']],
        ],
        'log' => [
            'LOG_LEVEL' => ['label'   => 'Уровень логов', 'type' => 'select',
                            'options' => ['debug'  => 'debug', 'info' => 'info',
                                          'notice' => 'notice', 'warning' => 'warning',
                                          'error'  => 'error', 'critical' => 'critical',
                                          'alert'  => 'alert', 'emergency' => 'emergency']],
        ],
        'redis' => [
            'REDIS_CLIENT' => ['label'     => 'Клиент', 'type' => 'select',
                                 'options' => ['phpredis' => 'phpredis', 'predis' => 'predis']],
            'REDIS_HOST'     => ['label' => 'Хост', 'type' => 'text'],
            'REDIS_PORT'     => ['label' => 'Порт', 'type' => 'number'],
            'REDIS_PASSWORD' => ['label' => 'Пароль', 'type' => 'password'],
        ],
        'mail' => [
            'MAIL_MAILER' => ['label'         => 'Транспорт', 'type' => 'select',
                                    'options' => ['smtp' => 'SMTP', 'log' => 'Только в лог']],
            'MAIL_HOST'         => ['label' => 'SMTP хост', 'type' => 'text'],
            'MAIL_PORT'         => ['label' => 'SMTP порт', 'type' => 'number'],
            'MAIL_USERNAME'     => ['label' => 'Логин', 'type' => 'text'],
            'MAIL_PASSWORD'     => ['label' => 'Пароль', 'type' => 'password'],
            'MAIL_FROM_ADDRESS' => ['label' => 'Email отправителя', 'type' => 'email'],
            'MAIL_FROM_NAME'    => ['label' => 'Имя отправителя', 'type' => 'text'],
        ],
    ];

    private static array $defaults = [
        'date_format'           => 'd.m.Y',
        'time_format'           => 'H:i',
        'timezone'              => 'Europe/Moscow',
        'page_404_id'           => '',
        'message_403'           => '<h1>Доступ запрещён</h1><p>У вас нет прав для просмотра этой страницы.</p>',
        'breadcrumbs_show_home' => '1',
        'breadcrumbs_separator' => '/',
        'breadcrumbs_last_link' => '0',
        'url_suffix'            => '',
        'analytics_google'      => '',
        'analytics_yandex'      => '',
        'head_code'             => '',
        'body_code'             => '',
        'maps_provider'         => 'yandex',
        'yandex_maps_api_key'   => '',
        'google_maps_api_key'   => '',

        'redirects_enabled'           => '1',
        'redirects_use_alias_history' => '1',
        'redirects_track_hits'        => '1',
        'redirects_log_misses'        => '0',
        'redirects_default_type'      => '301',
        'redirects_max_hops'          => '10',

        'sitemap_enabled'                => '1',
        'sitemap_cache_ttl'              => '3600',
        'sitemap_include_homepage'       => '1',
        'sitemap_include_rubric_indexes' => '1',
        'sitemap_lastmod_source'         => 'updated_at',
        'sitemap_default_changefreq'     => 'weekly',
        'sitemap_default_priority'       => '0.5',
        'sitemap_max_urls_per_file'      => '50000',

        'rss_enabled'                => '1',
        'rss_default_limit'          => '20',
        'rss_cache_ttl'              => '1800',
        'rss_description_max_length' => '500',
        'rss_site_feed_enabled'      => '0',
        'rss_site_feed_title'        => '',
        'rss_site_feed_description'  => '',
        'rss_site_feed_limit'        => '50',

        'api_enabled'            => '0',
        'api_domain'             => '',
        'api_url_prefix'         => '/api/v1',
        'api_default_per_page'   => '20',
        'api_max_per_page'       => '100',
        'api_cache_ttl'          => '300',
        'api_default_rate_limit' => '60',

        'public_cache_enabled'            => '0',
        'public_cache_default_ttl'        => '3600',
        'public_cache_query_strategy'     => 'blacklist',
        'public_cache_query_blacklist'    => "utm_*\nfbclid\nyclid\ngclid\nref\nfrom",
        'public_cache_query_whitelist'    => '',
        'public_cache_skip_authenticated' => '1',
        'public_cache_skip_with_csrf'     => '1',
        'public_cache_skip_markers'       => "<form\nmod-comment",
        'public_cache_send_headers'       => '1',
    ];

    public function index(Request $request): View
    {
        $user = $request->user('admin') ?? $request->user();

        $canView = [
            'general' => $user?->hasPermission(Permission::SETTINGS_TAB_GENERAL) ?? false,
            'env'     => $user?->hasPermission(Permission::SETTINGS_TAB_ENV) ?? false,
            'seo'     => $user?->hasPermission(Permission::SETTINGS_TAB_SEO) ?? false,
            'cache'   => $user?->hasPermission(Permission::SETTINGS_TAB_CACHE) ?? false,
            'maps'    => $user?->hasPermission(Permission::SETTINGS_TAB_MAPS) ?? false,
        ];

        $canEdit = [
            'general' => $canView['general'] && ($user?->hasPermission(Permission::SETTINGS_EDIT_GENERAL) ?? false),
            'env'     => $canView['env'] && ($user?->hasPermission(Permission::SETTINGS_EDIT_ENV) ?? false),
            'seo'     => $canView['seo'] && ($user?->hasPermission(Permission::SETTINGS_EDIT_SEO) ?? false),
            'cache'   => $canView['cache'] && ($user?->hasPermission(Permission::CACHE_CLEAR) ?? false),
            'maps'    => $canView['maps'] && ($user?->hasPermission(Permission::SETTINGS_EDIT_MAPS) ?? false),
        ];

        $defaultTab = null;

        foreach (['general', 'env', 'seo', 'cache', 'maps'] as $t) {
            if ($canView[$t]) {
                $defaultTab = $t;
                break;
            }
        }

        $requestedTab = $request->query('tab');

        if ($requestedTab && isset($canView[$requestedTab]) && $canView[$requestedTab]) {
            $defaultTab = $requestedTab;
        }

        $saved = Setting::allAsArray();
        $settings = array_merge(self::$defaults, $saved);

        $timezones = $dateFormats = $timeFormats = [];

        if ($canView['general']) {
            $cisTimezones = [
                'Россия' => [
                    'Europe/Kaliningrad', 'Europe/Moscow', 'Europe/Simferopol', 'Europe/Kirov', 'Europe/Volgograd',
                    'Europe/Astrakhan', 'Europe/Saratov', 'Europe/Ulyanovsk', 'Europe/Samara',
                    'Asia/Yekaterinburg', 'Asia/Omsk', 'Asia/Novosibirsk', 'Asia/Novokuznetsk',
                    'Asia/Barnaul', 'Asia/Tomsk', 'Asia/Krasnoyarsk', 'Asia/Irkutsk', 'Asia/Chita',
                    'Asia/Yakutsk', 'Asia/Khandyga', 'Asia/Vladivostok', 'Asia/Ust-Nera',
                    'Asia/Sakhalin', 'Asia/Magadan', 'Asia/Srednekolymsk', 'Asia/Kamchatka', 'Asia/Anadyr',
                ],
                'Беларусь'    => ['Europe/Minsk'],
                'Казахстан'   => ['Asia/Almaty', 'Asia/Aqtau', 'Asia/Aqtobe', 'Asia/Atyrau', 'Asia/Oral', 'Asia/Qostanay', 'Asia/Qyzylorda'],
                'Азербайджан' => ['Asia/Baku'],
                'Киргизия'    => ['Asia/Bishkek'],
                'Таджикистан' => ['Asia/Dushanbe'],
                'Узбекистан'  => ['Asia/Samarkand', 'Asia/Tashkent'],
                'Туркмения'   => ['Asia/Ashgabat'],
            ];

            $nowUtc = new \DateTime('now', new \DateTimeZone('UTC'));
            $formatOffset = static function (string $tz) use ($nowUtc): string {
                $offset = (new \DateTimeZone($tz))->getOffset($nowUtc);
                $sign = $offset >= 0 ? '+' : '-';
                $abs = abs($offset);
                $h = intdiv($abs, 3600);
                $m = intdiv($abs % 3600, 60);

                return $m === 0
                    ? sprintf('UTC%s%d', $sign, $h)
                    : sprintf('UTC%s%d:%02d', $sign, $h, $m);
            };

            $allCurated = [];

            foreach ($cisTimezones as $country => $list) {
                $items = [];

                foreach ($list as $tz) {
                    $items[$tz] = $tz . ' (' . $formatOffset($tz) . ')';
                    $allCurated[] = $tz;
                }

                $timezones[$country] = $items;
            }

            $currentTz = $settings['timezone'] ?? '';

            if ($currentTz !== ''
                && !in_array($currentTz, $allCurated, true)
                && in_array($currentTz, \DateTimeZone::listIdentifiers(\DateTimeZone::ALL), true)
            ) {
                $timezones['Прочее'] = [$currentTz => $currentTz . ' (' . $formatOffset($currentTz) . ')'];
            }

            $dateFormats = [
                'd.m.Y' => date('d.m.Y'),
                'd/m/Y' => date('d/m/Y'),
                'd-m-Y' => date('d-m-Y'),
            ];
            $timeFormats = [
                'H:i'   => date('H:i'),
                'H:i:s' => date('H:i:s'),
            ];
        }

        $envValues = [];
        $sysInfo = [];
        $logNotify = [];

        if ($canView['env']) {
            foreach (self::ENV_WHITELIST as $group => $vars) {
                foreach ($vars as $key => $meta) {
                    $envValues[$key] = $this->readEnvValue($key);
                }
            }

            $sysInfo = [
                'php'     => phpversion(),
                'laravel' => app()->version(),
                'db'      => (($pgv = DB::selectOne('SELECT version() AS v')?->v) ? preg_replace('/^PostgreSQL (\S+).*/i', '$1', $pgv) : '—'),
                'app_key' => $this->maskAppKey(env('APP_KEY', '')),
            ];
            $logNotify = [
                'level'            => Setting::getValue('log_notify_level', 'error'),
                'email_enabled'    => Setting::getValue('log_notify_email_enabled', '0'),
                'email'            => Setting::getValue('log_notify_email', ''),
                'telegram_enabled' => Setting::getValue('log_notify_telegram_enabled', '0'),
                'telegram_token'   => Setting::getValue('log_notify_telegram_token', ''),
                'telegram_chat_id' => Setting::getValue('log_notify_telegram_chat_id', ''),
            ];
        }

        $robotsTxt = $canView['seo'] ? $this->readRobotsTxt() : '';

        return view('admin.settings.index', compact(
            'settings',
            'timezones',
            'dateFormats',
            'timeFormats',
            'envValues',
            'sysInfo',
            'robotsTxt',
            'logNotify',
            'canView',
            'canEdit',
            'defaultTab',
        ));
    }

    public function saveGeneral(SaveGeneralSettingsRequest $request): JsonResponse
    {
        foreach (['date_format', 'time_format', 'timezone', 'page_404_id', 'message_403', 'breadcrumbs_separator'] as $key) {
            Setting::setValue($key, $request->input($key, ''));
        }

        Setting::setValue('breadcrumbs_show_home', $request->boolean('breadcrumbs_show_home') ? '1' : '0');
        Setting::setValue('breadcrumbs_last_link', $request->boolean('breadcrumbs_last_link') ? '1' : '0');

        Logger::adminAction('Сохранил основные настройки', 'edit', 'settings', null, 'Основные');

        return response()->json(['ok' => true, 'message' => 'Основные настройки сохранены']);
    }

    public function saveMaps(SaveMapsSettingsRequest $request): JsonResponse
    {
        Setting::setValue('maps_provider', $request->input('maps_provider'));
        Setting::setValue('yandex_maps_api_key', (string) $request->input('yandex_maps_api_key', ''));
        Setting::setValue('google_maps_api_key', (string) $request->input('google_maps_api_key', ''));

        Logger::adminAction('Сохранил настройки карт', 'edit', 'settings', null, 'Карты');

        return response()->json(['ok' => true, 'message' => 'Настройки карт сохранены']);
    }

    public function checkMapsKey(Request $request): JsonResponse
    {
        $provider = (string) $request->input('provider', '');
        $key = trim((string) $request->input('key', ''));

        if (!in_array($provider, ['yandex', 'google'], true)) {
            return response()->json(['ok' => false, 'message' => 'Неизвестный провайдер']);
        }

        if ($key === '') {
            return response()->json(['ok' => false, 'message' => 'Ключ не указан']);
        }

        try {
            if ($provider === 'yandex') {
                return $this->checkYandexKey($key);
            }

            return $this->checkGoogleKey($key);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'message' => 'Не удалось обратиться к API: ' . $e->getMessage(),
            ]);
        }
    }

    private function checkYandexKey(string $key): JsonResponse
    {
        $url     = 'https://api-maps.yandex.ru/v3/?apikey=' . urlencode($key) . '&lang=ru_RU';
        $siteUrl = rtrim(config('app.url', 'http://localhost'), '/') . '/';

        $response = Http::timeout(6)->withHeaders([
            'User-Agent' => 'Zentra-CMF/1.0',
            'Referer'    => $siteUrl,
        ])->get($url);

        if ($response->status() === 403) {
            return response()->json([
                'ok'      => false,
                'message' => 'Яндекс отказал: ключ недействителен или домен не разрешён',
            ]);
        }

        if (!$response->ok()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Яндекс ответил кодом ' . $response->status(),
            ]);
        }

        return response()->json(['ok' => true, 'message' => 'Ключ работает']);
    }

    private function checkGoogleKey(string $key): JsonResponse
    {
        $url = 'https://maps.googleapis.com/maps/api/geocode/json?latlng=0,0&key=' . urlencode($key);
        $response = Http::timeout(6)->withHeaders(['User-Agent' => 'Zentra-CMF/1.0'])->get($url);

        if (!$response->ok()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Google ответил кодом ' . $response->status(),
            ]);
        }

        $data = $response->json();
        $status = is_array($data) ? (string) ($data['status'] ?? '') : '';
        $errMsg = is_array($data) ? (string) ($data['error_message'] ?? '') : '';

        if ($status === 'OK' || $status === 'ZERO_RESULTS') {
            return response()->json(['ok' => true, 'message' => 'Ключ работает (Google принял запрос)']);
        }

        if ($status === 'REQUEST_DENIED') {
            if (stripos($errMsg, 'invalid') !== false || stripos($errMsg, 'expired') !== false) {
                return response()->json([
                    'ok'      => false,
                    'message' => 'Google отказал: ключ недействителен' . ($errMsg !== '' ? ' (' . $errMsg . ')' : ''),
                ]);
            }

            return response()->json([
                'ok'      => true,
                'message' => 'Ключ принят Google, но Geocoding API не включён - точную проверку JS API сделать удалённо нельзя. Откройте документ с полем «Карта» для финальной верификации.',
            ]);
        }

        return response()->json([
            'ok'      => false,
            'message' => 'Google вернул неожиданный статус: ' . ($status ?: 'unknown') . ($errMsg !== '' ? ' (' . $errMsg . ')' : ''),
        ]);
    }

    public function saveEnv(Request $request): JsonResponse
    {
        $metaMap = [];

        foreach (self::ENV_WHITELIST as $vars) {
            foreach ($vars as $key => $meta) {
                $metaMap[$key] = $meta;
            }
        }

        $data = [];

        foreach ($metaMap as $key => $meta) {
            if ($meta['type'] === 'bool') {
                $data[$key] = $request->boolean($key) ? 'true' : 'false';
                continue;
            }

            if (!$request->has($key)) {
                continue;
            }

            $value = $request->input($key, '');

            if ($meta['type'] === 'password' && $value === '••••••••') {
                continue;
            }

            $data[$key] = $value;
        }

        if (isset($data['MAIL_PORT'])) {
            $data['MAIL_SCHEME'] = ((int) $data['MAIL_PORT'] === 465) ? 'smtps' : '';
        }

        $usesRedis = in_array('redis', [$data['SESSION_DRIVER'] ?? '', $data['CACHE_STORE'] ?? ''], true);

        if ($usesRedis && trim($data['REDIS_HOST'] ?? '') === '') {
            return response()->json([
                'ok'      => false,
                'message' => 'Невозможно сохранить: выбран драйвер Redis, но не указан хост. Заполните поле «Хост» в блоке Redis.',
            ], 422);
        }

        if ($usesRedis && !extension_loaded('redis') && !class_exists('Predis\Client')) {
            return response()->json([
                'ok'      => false,
                'message' => 'Невозможно сохранить: выбран драйвер Redis, но на сервере не установлено ни расширение phpredis, ни пакет predis/predis. Сначала установите одно из них.',
            ], 422);
        }

        try {
            $this->writeEnvValues($data);
            $cached = app()->getCachedConfigPath();
            if (file_exists($cached)) {
                @unlink($cached);
            }
            Logger::adminAction('Сохранил настройки окружения (.env)', 'edit', 'settings', null, 'Окружение');

            return response()->json(['ok' => true, 'message' => 'Настройки окружения сохранены, конфиг обновлён']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Ошибка записи .env: ' . $e->getMessage()], 422);
        }
    }

    public function sendTestEmail(SendTestEmailRequest $request): JsonResponse
    {
        $mailer = $request->input('MAIL_MAILER') ?: $this->readEnvValue('MAIL_MAILER', 'smtp');
        $from = $request->input('MAIL_FROM_ADDRESS') ?: $this->readEnvValue('MAIL_FROM_ADDRESS', 'noreply@example.com');
        $name = $request->input('MAIL_FROM_NAME') ?: $this->readEnvValue('MAIL_FROM_NAME', 'Zentra CMF');
        $to = $request->input('test_email');

        try {
            $cfg = ['transport' => $mailer];

            if ($mailer === 'smtp') {
                $port = (int) ($request->input('MAIL_PORT') ?: $this->readEnvValue('MAIL_PORT', '587'));
                $password = $request->input('MAIL_PASSWORD');

                if ($password === null || $password === '' || $password === '••••••••') {
                    $password = $this->readEnvValue('MAIL_PASSWORD', '');
                }

                $cfg = array_merge($cfg, [
                    'scheme'   => $port === 465 ? 'smtps' : null,
                    'host'     => $request->input('MAIL_HOST') ?: $this->readEnvValue('MAIL_HOST', '127.0.0.1'),
                    'port'     => $port,
                    'username' => $request->input('MAIL_USERNAME') ?? $this->readEnvValue('MAIL_USERNAME', ''),
                    'password' => $password,
                    'timeout'  => 10,
                ]);
            }

            config(['mail.mailers.zentra_test' => $cfg]);
            config(['mail.from.address' => $from, 'mail.from.name' => $name]);

            $body = "Это тестовое письмо от Zentra CMF.\n\nОно подтверждает, что настройки Email работают корректно.";

            Mail::mailer('zentra_test')->raw($body, function ($msg) use ($to, $from, $name) {
                $msg->to($to)->from($from, $name)->subject('Тестовое письмо - Zentra CMF');
            });

            return response()->json(['ok' => true, 'message' => "Письмо отправлено на {$to}"]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Ошибка отправки: ' . $e->getMessage()], 422);
        }
    }

    public function saveSeo(SaveSeoSettingsRequest $request): JsonResponse
    {
        foreach (['og_default_image', 'url_suffix', 'analytics_google', 'analytics_yandex', 'head_code', 'body_code'] as $key) {
            Setting::setValue($key, $request->input($key, ''));
        }

        if ($request->has('robots_txt')) {
            $this->writeRobotsTxt($request->input('robots_txt', ''));
        }

        Setting::setValue('redirects_enabled', $request->boolean('redirects_enabled') ? '1' : '0');
        Setting::setValue('redirects_use_alias_history', $request->boolean('redirects_use_alias_history') ? '1' : '0');
        Setting::setValue('redirects_track_hits', $request->boolean('redirects_track_hits') ? '1' : '0');
        Setting::setValue('redirects_log_misses', $request->boolean('redirects_log_misses') ? '1' : '0');
        Setting::setValue('redirects_default_type', (string) $request->input('redirects_default_type', '301'));
        Setting::setValue('redirects_max_hops', (string) $request->input('redirects_max_hops', '10'));

        Setting::setValue('sitemap_enabled', $request->boolean('sitemap_enabled') ? '1' : '0');
        Setting::setValue('sitemap_include_homepage', $request->boolean('sitemap_include_homepage') ? '1' : '0');
        Setting::setValue('sitemap_include_rubric_indexes', $request->boolean('sitemap_include_rubric_indexes') ? '1' : '0');
        Setting::setValue('sitemap_cache_ttl', (string) $request->input('sitemap_cache_ttl', '3600'));
        Setting::setValue('sitemap_lastmod_source', (string) $request->input('sitemap_lastmod_source', 'updated_at'));
        Setting::setValue('sitemap_default_changefreq', (string) $request->input('sitemap_default_changefreq', 'weekly'));
        Setting::setValue('sitemap_default_priority', (string) $request->input('sitemap_default_priority', '0.5'));
        Setting::setValue('sitemap_max_urls_per_file', (string) $request->input('sitemap_max_urls_per_file', '50000'));

        Setting::setValue('rss_enabled', $request->boolean('rss_enabled') ? '1' : '0');
        Setting::setValue('rss_default_limit', (string) $request->input('rss_default_limit', '20'));
        Setting::setValue('rss_cache_ttl', (string) $request->input('rss_cache_ttl', '1800'));
        Setting::setValue('rss_description_max_length', (string) $request->input('rss_description_max_length', '500'));
        Setting::setValue('rss_site_feed_enabled', $request->boolean('rss_site_feed_enabled') ? '1' : '0');
        Setting::setValue('rss_site_feed_title', (string) $request->input('rss_site_feed_title', ''));
        Setting::setValue('rss_site_feed_description', (string) $request->input('rss_site_feed_description', ''));
        Setting::setValue('rss_site_feed_limit', (string) $request->input('rss_site_feed_limit', '50'));

        $apiDomainOld = (string) Setting::getValue('api_domain', '');
        $apiPrefixOld = (string) Setting::getValue('api_url_prefix', '/api/v1');

        Setting::setValue('api_enabled', $request->boolean('api_enabled') ? '1' : '0');
        Setting::setValue('api_domain', trim((string) $request->input('api_domain', '')));
        Setting::setValue('api_url_prefix', (string) $request->input('api_url_prefix', '/api/v1'));
        Setting::setValue('api_default_per_page', (string) $request->input('api_default_per_page', '20'));
        Setting::setValue('api_max_per_page', (string) $request->input('api_max_per_page', '100'));
        Setting::setValue('api_cache_ttl', (string) $request->input('api_cache_ttl', '300'));
        Setting::setValue('api_default_rate_limit', (string) $request->input('api_default_rate_limit', '60'));

        $apiDomainNew = (string) Setting::getValue('api_domain', '');
        $apiPrefixNew = (string) Setting::getValue('api_url_prefix', '/api/v1');

        if ($apiDomainNew !== $apiDomainOld || $apiPrefixNew !== $apiPrefixOld) {
            $cachedRoutes = app()->getCachedRoutesPath();
            if (file_exists($cachedRoutes)) {
                @unlink($cachedRoutes);
            }
        }

        app(SitemapGenerator::class)->flush();
        app(RssGenerator::class)->flushAll();
        app(ApiJsonGenerator::class)->flushAll();

        Logger::adminAction('Сохранил SEO-настройки', 'edit', 'settings', null, 'SEO');

        return response()->json(['ok' => true, 'message' => 'SEO-настройки сохранены']);
    }

    public function savePublicCache(SavePublicCacheSettingsRequest $request, PublicCacheManager $manager): JsonResponse
    {
        Setting::setValue('public_cache_enabled', $request->boolean('public_cache_enabled') ? '1' : '0');
        Setting::setValue('public_cache_skip_authenticated', $request->boolean('public_cache_skip_authenticated') ? '1' : '0');
        Setting::setValue('public_cache_skip_with_csrf', $request->boolean('public_cache_skip_with_csrf') ? '1' : '0');
        Setting::setValue('public_cache_send_headers', $request->boolean('public_cache_send_headers') ? '1' : '0');
        Setting::setValue('public_cache_default_ttl', (string) $request->input('public_cache_default_ttl', '3600'));
        Setting::setValue('public_cache_query_strategy', (string) $request->input('public_cache_query_strategy', 'blacklist'));
        Setting::setValue('public_cache_query_blacklist', (string) $request->input('public_cache_query_blacklist', ''));
        Setting::setValue('public_cache_query_whitelist', (string) $request->input('public_cache_query_whitelist', ''));
        Setting::setValue('public_cache_skip_markers', (string) $request->input('public_cache_skip_markers', ''));

        $manager->flushAll();

        Logger::adminAction('Сохранил настройки кеша публичных страниц', 'edit', 'settings', null, 'Кэш публичных страниц');

        return response()->json(['ok' => true, 'message' => 'Настройки кеша публичных страниц сохранены']);
    }

    public function flushPublicCache(PublicCacheManager $manager): JsonResponse
    {
        $manager->flushAll();
        Logger::adminAction('Очистил кеш публичных страниц', 'other', 'cache', null, 'Кэш публичных страниц');

        return response()->json(['ok' => true, 'message' => 'Кэш публичных страниц очищен']);
    }

    public function sitemapPreview(SitemapGenerator $generator): JsonResponse
    {
        try {
            return response()->json([
                'ok'        => true,
                'preview'   => $generator->preview(),
                'enabled'   => Setting::getValue('sitemap_enabled', '1') === '1',
                'cache_ttl' => (int) Setting::getValue('sitemap_cache_ttl', '3600'),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Ошибка сборки превью: ' . $e->getMessage()], 422);
        }
    }

    public function sitemapFlush(SitemapGenerator $generator): JsonResponse
    {
        $generator->flush();
        Logger::adminAction('Очистил кеш sitemap', 'other', 'cache', null, 'Sitemap');

        return response()->json(['ok' => true, 'message' => 'Кеш sitemap очищен']);
    }

    public function cacheStats(CacheService $cacheService): JsonResponse
    {
        return response()->json([
            'cache'         => $cacheService->cacheStats(),
            'views'         => Format::fileSize($this->dirSize(storage_path('framework/views'))),
            'sessions'      => $cacheService->sessionStats(),
            'config_cached' => file_exists(base_path('bootstrap/cache/config.php')),
            'routes_cached' => file_exists(base_path('bootstrap/cache/routes-v7.php'))
                            || file_exists(base_path('bootstrap/cache/routes.php')),
        ]);
    }

    public function clearCache(Request $request): JsonResponse
    {
        $type = $request->input('type');

        try {
            match ($type) {
                'framework' => Artisan::call('cache:clear'),
                'views'     => Artisan::call('view:clear'),
                'config'    => Artisan::call('config:clear'),
                'routes'    => Artisan::call('route:clear'),
                default     => throw new \InvalidArgumentException("Неизвестный тип: {$type}"),
            };

            $cacheLabels = ['application' => 'Приложение', 'views' => 'Шаблоны', 'config' => 'Конфигурация', 'routes' => 'Маршруты'];
            Logger::adminAction("Очистил кэш: {$type}", 'other', 'cache', null, $cacheLabels[$type] ?? $type);

            return response()->json(['ok' => true, 'message' => 'Кэш очищен']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 422);
        }
    }

    public function clearAllCache(): JsonResponse
    {
        try {
            Artisan::call('cache:clear');
            Artisan::call('view:clear');
            Artisan::call('config:clear');
            Artisan::call('route:clear');

            Logger::adminAction('Очистил весь кэш', 'other', 'cache', null, 'Весь кэш');

            return response()->json(['ok' => true, 'message' => 'Весь кэш успешно очищен']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Ошибка: ' . $e->getMessage()], 422);
        }
    }

    public function optimize(): JsonResponse
    {
        try {
            Artisan::call('config:cache');
            Artisan::call('route:cache');
            Artisan::call('view:cache');

            Logger::adminAction('Запустил кеширование (config:cache, route:cache, view:cache)', 'other', 'cache', null, 'Кеширование');

            return response()->json(['ok' => true, 'message' => 'Кеширование выполнено: конфиги, маршруты и шаблоны закешированы']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Ошибка кеширования: ' . $e->getMessage()], 422);
        }
    }

    public function optimizeClear(): JsonResponse
    {
        try {
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');

            Logger::adminAction('Очистил кеш оптимизации (config:clear, route:clear, view:clear)', 'other', 'cache', null, 'Кеширование');

            return response()->json(['ok' => true, 'message' => 'Кеш конфигов, маршрутов и шаблонов очищен']);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Ошибка очистки: ' . $e->getMessage()], 422);
        }
    }

    public function saveLogNotify(Request $request): JsonResponse
    {
        $emailEnabled = $request->boolean('email_enabled');
        $telegramEnabled = $request->boolean('telegram_enabled');

        if ($emailEnabled && empty(trim($request->input('email', '')))) {
            return response()->json(['ok' => false, 'message' => 'Укажите email для получения уведомлений.'], 422);
        }

        if ($telegramEnabled) {
            $token = trim($request->input('telegram_token', ''));
            $chatId = trim($request->input('telegram_chat_id', ''));

            if (empty($token) || empty($chatId)) {
                return response()->json(['ok' => false, 'message' => 'Заполните Bot Token и Chat ID для Telegram.'], 422);
            }
        }

        $fields = [
            'log_notify_level'            => $request->input('level', 'error'),
            'log_notify_email_enabled'    => $emailEnabled ? '1' : '0',
            'log_notify_email'            => trim($request->input('email', '')),
            'log_notify_telegram_enabled' => $telegramEnabled ? '1' : '0',
            'log_notify_telegram_token'   => trim($request->input('telegram_token', '')),
            'log_notify_telegram_chat_id' => trim($request->input('telegram_chat_id', '')),
        ];

        foreach ($fields as $key => $value) {
            Setting::setValue($key, $value);
        }

        Cache::forget('log_notify_settings');
        Logger::adminAction('Сохранил настройки уведомлений об ошибках', 'edit', 'settings', null, 'Уведомления');

        return response()->json(['ok' => true, 'message' => 'Настройки уведомлений сохранены']);
    }

    public function testLogNotify(Request $request): JsonResponse
    {
        $emailEnabled = $request->input('email_enabled') === '1';
        $telegramEnabled = $request->input('telegram_enabled') === '1';
        $email = $request->input('email', '');
        $token = $request->input('telegram_token', '');
        $chatId = $request->input('telegram_chat_id', '');

        if (!$emailEnabled && !$telegramEnabled) {
            return response()->json(['ok' => false, 'message' => 'Включите хотя бы один канал уведомлений.'], 422);
        }

        $text = '🔴 [CRITICAL] Тестовое уведомление от ' . config('app.name') . "\n"
              . "Это тест - реальной ошибки нет.\n"
              . 'Время: ' . now()->format('Y-m-d H:i:s') . "\n"
              . 'Сайт: ' . config('app.url');

        $sent = [];

        if ($emailEnabled && !empty($email)) {
            try {
                Mail::raw($text, fn ($m) => $m->to($email)
                    ->subject('[' . config('app.name') . '] Тест уведомлений об ошибках'));
                $sent[] = 'Email';
            } catch (\Throwable $e) {
                return response()->json(['ok' => false, 'message' => 'Ошибка отправки email: ' . $e->getMessage()], 422);
            }
        }

        if ($telegramEnabled && !empty($token) && !empty($chatId)) {
            $ch = curl_init("https://api.telegram.org/bot{$token}/sendMessage");
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode(['chat_id' => $chatId, 'text' => $text]),
                CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 10,
            ]);
            $result = curl_exec($ch);
            $curlError = curl_error($ch);
            curl_close($ch);

            if ($result === false) {
                return response()->json(['ok' => false, 'message' => 'Ошибка отправки в Telegram: ' . $curlError], 422);
            }

            $json = json_decode($result, true);

            if (!($json['ok'] ?? false)) {
                return response()->json(['ok' => false, 'message' => 'Telegram: ' . ($json['description'] ?? 'Неизвестная ошибка')], 422);
            }

            $sent[] = 'Telegram';
        }

        if (empty($sent)) {
            return response()->json(['ok' => false, 'message' => 'Не указаны данные для выбранного канала.'], 422);
        }

        return response()->json(['ok' => true, 'message' => 'Тест отправлен: ' . implode(', ', $sent)]);
    }

    public function redisCheck(): JsonResponse
    {
        return response()->json([
            'phpredis' => extension_loaded('redis'),
            'predis'   => class_exists('Predis\Client'),
        ]);
    }

    public function composerOutdated(Request $request): JsonResponse
    {
        $lockMtime = @filemtime(base_path('composer.lock')) ?: 0;
        $cacheKey = 'composer.outdated:' . $lockMtime;

        if ($request->boolean('fresh')) {
            Cache::forget($cacheKey);
        }

        try {
            $packages = Cache::remember($cacheKey, 3600, function () {
                $outdated = $this->runComposer(
                    ['composer', 'outdated', '--format=json', '--no-interaction', '--no-ansi', '--no-dev'],
                );
                $raw = trim($outdated['stdout']);
                if (($pos = strpos($raw, '{')) > 0) {
                    $raw = substr($raw, $pos);
                }
                $json = json_decode($raw, true);

                if (!is_array($json)) {
                    $err = trim($outdated['stderr']);

                    if ($raw === '' && $err === '') {
                        throw new \RuntimeException('Composer не вернул данных. Укажите полный путь к бинарному файлу через COMPOSER_PATH в .env (например: COMPOSER_PATH=/usr/local/bin/composer).');
                    }

                    throw new \RuntimeException($err !== '' ? $err : ('Неожиданный ответ: ' . substr($raw, 0, 300)));
                }

                $packages = $json['installed'] ?? [];

                if (empty($packages)) {
                    return [];
                }

                $dry = $this->runComposer(
                    ['composer', 'update', '--dry-run', '--no-interaction', '--no-ansi', '--no-scripts', '--no-dev'],
                );
                $installable = [];
                $combined = $dry['stdout'] . "\n" . $dry['stderr'];

                foreach (explode("\n", $combined) as $line) {
                    if (preg_match('/(?:Upgrading|Updating|Installing|Downgrading)\s+(\S+?)\s+\(/', $line, $m)) {
                        $installable[$m[1]] = true;
                    }
                }

                return array_values(array_filter(
                    $packages,
                    fn ($p) => isset($installable[$p['name']]),
                ));
            });

            return response()->json(['ok' => true, 'packages' => $packages]);
        } catch (\Throwable $e) {
            Cache::forget($cacheKey);

            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }
    }

    private function runComposer(array $cmd): array
    {
        $binary = env('COMPOSER_PATH', 'composer');
        $cmd[0] = $binary;

        $composerHome = env('COMPOSER_HOME', storage_path('app/.composer'));
        if (!is_dir($composerHome)) {
            mkdir($composerHome, 0755, true);
        }

        $process = new Process($cmd, base_path());
        $process->setTimeout(120);
        $process->setEnv(['HOME' => $composerHome, 'COMPOSER_HOME' => $composerHome]);
        $process->run();

        if (!$process->isStarted()) {
            throw new \RuntimeException("Не удалось запустить composer. Укажите COMPOSER_PATH в .env.");
        }

        return [
            'stdout' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
        ];
    }

    public function clearSessions(Request $request, CacheService $cacheService): JsonResponse
    {
        $result = $cacheService->clearSessions();

        if (!$result['supported']) {
            return response()->json(['ok' => false, 'message' => $result['message']], 422);
        }

        Logger::adminAction(
            "Очистил сессии ({$result['driver']}: {$result['count']})",
            'other',
            'cache',
            null,
            'Сессии',
        );

        Auth::guard('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'ok'          => true,
            'message'     => $result['message'],
            'logged_out'  => true,
            'redirect_to' => route('admin.login'),
        ]);
    }

    public static function readEnvValue(string $key, string $default = ''): string
    {
        $path = base_path('.env');

        if (!file_exists($path)) {
            return $default;
        }

        $content = file_get_contents($path);

        if (preg_match('/^' . preg_quote($key, '/') . '=(.*)$/m', $content, $m)) {
            $val = trim($m[1]);

            if (str_starts_with($val, '"') && str_ends_with($val, '"')) {
                $val = stripslashes(substr($val, 1, -1));
            }

            return $val;
        }

        return $default;
    }

    private function writeEnvValues(array $data): void
    {
        $path = base_path('.env');
        $content = file_exists($path) ? file_get_contents($path) : '';

        foreach ($data as $key => $value) {
            $needsQuotes = $value !== '' && (
                str_contains($value, ' ') ||
                str_contains($value, '#') ||
                str_contains($value, '"') ||
                str_contains($value, '$')
            );
            $formatted = $needsQuotes
                ? '"' . str_replace(['"', '$'], ['\"', '\$'], $value) . '"'
                : ($value === '' ? '' : $value);

            $line = $key . '=' . $formatted;

            if (preg_match('/^' . preg_quote($key, '/') . '=/m', $content)) {
                $content = preg_replace('/^' . preg_quote($key, '/') . '=.*/m', $line, $content);
            } else {
                $content = rtrim($content) . "\n" . $line . "\n";
            }
        }

        if (!file_put_contents($path, $content)) {
            throw new \RuntimeException('Не удалось записать файл .env');
        }
    }

    private function maskAppKey(string $key): string
    {
        if (strlen($key) < 16) {
            return $key ?: '—';
        }

        return substr($key, 0, 12) . '...' . substr($key, -4);
    }

    private function readRobotsTxt(): string
    {
        $path = public_path('robots.txt');

        if (!file_exists($path)) {
            return "User-agent: *\nDisallow:";
        }

        return file_get_contents($path);
    }

    private function writeRobotsTxt(string $content): void
    {
        file_put_contents(public_path('robots.txt'), $content);
    }

    private function dirSize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }
        $bytes = 0;

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }
            $bytes += $file->getSize();
        }

        return $bytes;
    }
}
