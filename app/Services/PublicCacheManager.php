<?php

namespace App\Services;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PublicCacheManager
{
    public const VERSION_KEY = 'public_cache:version';

    public const REQUEST_DOC_ATTR = '_public_cache_document';

    private array $authDetectors = [];

    public function addAuthDetector(callable $detector): void
    {
        $this->authDetectors[] = $detector;
    }

    public function isEnabled(): bool
    {
        return Setting::getValue('public_cache_enabled', '0') === '1';
    }

    public function shouldHandle(Request $request): bool
    {
        if (!$this->isEnabled()) {
            return false;
        }

        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return false;
        }

        return !(Setting::getValue('public_cache_skip_authenticated', '1') === '1' && $this->isAuthenticated($request))

        ;
    }

    public function getKey(Request $request): string
    {
        $version = $this->currentVersion();
        $path = '/' . ltrim($request->path(), '/');
        $query = $this->filteredQuery($request);
        $hash = md5($path . '?' . $query);

        return "public_cache:v{$version}:{$hash}";
    }

    public function get(string $key): ?array
    {
        $payload = Cache::get($key);

        if (!is_array($payload) || !isset($payload['content'])) {
            return null;
        }

        return $payload;
    }

    public function put(string $key, Response $response, int $ttl): void
    {
        if ($ttl <= 0) {
            return;
        }

        $payload = [
            'content' => $response->getContent(),
            'status'  => $response->getStatusCode(),
            'ctype'   => $response->headers->get('Content-Type', 'text/html; charset=utf-8'),
        ];

        Cache::put($key, $payload, $ttl);
    }

    public function flushAll(): void
    {
        try {
            Cache::increment(self::VERSION_KEY);
        } catch (\Throwable) {
            $v = (int) Cache::get(self::VERSION_KEY, 0);
            Cache::forever(self::VERSION_KEY, $v + 1);
        }
    }

    public function currentVersion(): int
    {
        $v = Cache::get(self::VERSION_KEY);

        if ($v === null) {
            Cache::forever(self::VERSION_KEY, 1);

            return 1;
        }

        return (int) $v;
    }

    public function shouldStore(Request $request, Response $response): bool
    {
        if ($response->getStatusCode() !== 200) {
            return false;
        }

        $ctype = strtolower((string) $response->headers->get('Content-Type', ''));

        if ($ctype !== '' && !str_contains($ctype, 'text/html')) {
            return false;
        }

        $content = (string) $response->getContent();

        if ($content === '') {
            return false;
        }

        if (Setting::getValue('public_cache_skip_with_csrf', '1') === '1') {
            if (str_contains($content, 'name="_token"') || str_contains($content, "name='_token'")) {
                return false;
            }
        }

        $skipMarkers = $this->skipMarkers();

        foreach ($skipMarkers as $marker) {
            if ($marker !== '' && str_contains($content, $marker)) {
                return false;
            }
        }

        $doc = $request->attributes->get(self::REQUEST_DOC_ATTR);

        if (is_object($doc)) {
            if (!empty($doc->public_cache_disabled)) {
                return false;
            }
            $rubric = $doc->rubric ?? null;

            if (is_object($rubric) && !empty($rubric->public_cache_disabled)) {
                return false;
            }
        }

        return true;
    }

    public function resolveTtl(Request $request): int
    {
        $doc = $request->attributes->get(self::REQUEST_DOC_ATTR);

        if (is_object($doc)) {
            $docTtl = $doc->public_cache_ttl ?? null;

            if ($docTtl !== null && (int) $docTtl > 0) {
                return (int) $docTtl;
            }
            $rubric = $doc->rubric ?? null;

            if (is_object($rubric)) {
                $rubricTtl = $rubric->public_cache_ttl ?? null;

                if ($rubricTtl !== null && (int) $rubricTtl > 0) {
                    return (int) $rubricTtl;
                }
            }
        }

        return max(0, (int) Setting::getValue('public_cache_default_ttl', '3600'));
    }

    public function sendStatusHeaders(): bool
    {
        return Setting::getValue('public_cache_send_headers', '1') === '1';
    }

    private function isAuthenticated(Request $request): bool
    {
        foreach ($this->authDetectors as $detector) {
            try {
                if ($detector($request) === true) {
                    return true;
                }
            } catch (\Throwable) {
                return true;
            }
        }

        return false;
    }

    private function filteredQuery(Request $request): string
    {
        $params = $request->query();

        if (empty($params)) {
            return '';
        }

        $strategy = (string) Setting::getValue('public_cache_query_strategy', 'blacklist');

        if ($strategy === 'ignore_all') {
            return '';
        }

        if ($strategy === 'whitelist') {
            $allowed = $this->parseList((string) Setting::getValue('public_cache_query_whitelist', ''));
            $params = $this->matchAgainstList($params, $allowed, true);
        } elseif ($strategy === 'blacklist') {
            $blocked = $this->parseList((string) Setting::getValue('public_cache_query_blacklist', "utm_*\nfbclid\nyclid\ngclid\nref\nfrom"));
            $params = $this->matchAgainstList($params, $blocked, false);
        }

        if (empty($params)) {
            return '';
        }

        ksort($params);

        return http_build_query($params);
    }

    private function matchAgainstList(array $params, array $list, bool $keepIfMatch): array
    {
        $result = [];

        foreach ($params as $name => $value) {
            $matched = false;

            foreach ($list as $pattern) {
                if ($this->matchPattern((string) $name, $pattern)) {
                    $matched = true;
                    break;
                }
            }

            if ($keepIfMatch ? $matched : !$matched) {
                $result[$name] = $value;
            }
        }

        return $result;
    }

    private function matchPattern(string $name, string $pattern): bool
    {
        if (!str_contains($pattern, '*')) {
            return strcasecmp($name, $pattern) === 0;
        }

        $regex = '#^' . str_replace('\*', '.*', preg_quote($pattern, '#')) . '$#i';

        return (bool) preg_match($regex, $name);
    }

    private function parseList(string $raw): array
    {
        return collect(preg_split('/[\r\n,]+/', $raw))
            ->map(fn ($s) => trim($s))
            ->filter(fn ($s) => $s !== '')
            ->values()
            ->all();
    }

    private function skipMarkers(): array
    {
        return $this->parseList(
            (string) Setting::getValue('public_cache_skip_markers', "<form\nmod-comment"),
        );
    }
}
