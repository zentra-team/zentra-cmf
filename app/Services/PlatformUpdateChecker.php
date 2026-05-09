<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class PlatformUpdateChecker
{
    private const CACHE_KEY = 'platform_update_info';
    private const CACHE_TTL = 86400;

    public function currentVersion(): string
    {
        $file = base_path('VERSION');

        return file_exists($file) ? trim(file_get_contents($file)) : '0.0.0';
    }

    public function check(bool $force = false): ?array
    {
        if ($force) {
            Cache::forget(self::CACHE_KEY);
        }

        if (!Cache::has(self::CACHE_KEY)) {
            $result = $this->fetchFromGitHub();

            if ($result !== false) {
                Cache::put(self::CACHE_KEY, $result, self::CACHE_TTL);
            }
        }

        return Cache::get(self::CACHE_KEY);
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function latest(): ?array
    {
        try {
            $repo = config('services.github.repo');

            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get("https://api.github.com/repos/{$repo}/releases/latest");

            if (!$response->ok()) {
                return null;
            }

            $data = $response->json();
            $latestVersion = ltrim($data['tag_name'] ?? '', 'v');

            if (!$latestVersion) {
                return null;
            }

            return [
                'version'      => $latestVersion,
                'tag'          => $data['tag_name'],
                'download_url' => $this->resolveDownloadUrl($data, $latestVersion),
                'changelog'    => $data['body'] ?? '',
                'published_at' => $data['published_at'] ?? null,
                'html_url'     => $data['html_url'] ?? null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }

    private function fetchFromGitHub(): array|null|false
    {
        try {
            $repo = config('services.github.repo');

            $response = Http::withHeaders($this->headers())
                ->timeout(10)
                ->get("https://api.github.com/repos/{$repo}/releases/latest");

            if (!$response->ok()) {
                return false;
            }

            $data = $response->json();
            $latestVersion = ltrim($data['tag_name'] ?? '', 'v');

            if (!$latestVersion) {
                return false;
            }

            if (version_compare($latestVersion, $this->currentVersion(), '>')) {
                return [
                    'version'      => $latestVersion,
                    'tag'          => $data['tag_name'],
                    'download_url' => $this->resolveDownloadUrl($data, $latestVersion),
                    'changelog'    => $data['body'] ?? '',
                    'published_at' => $data['published_at'] ?? null,
                    'html_url'     => $data['html_url'] ?? null,
                ];
            }

            return null;
        } catch (\Throwable) {
            return false;
        }
    }

    private function resolveDownloadUrl(array $data, string $version): ?string
    {
        $expectedName = "zentra-cmf-v{$version}.zip";

        foreach ($data['assets'] ?? [] as $asset) {
            if (($asset['name'] ?? '') === $expectedName) {
                return $asset['browser_download_url'];
            }
        }

        return $data['zipball_url'] ?? null;
    }

    private function headers(): array
    {
        $headers = [
            'User-Agent' => 'Zentra-CMF/' . $this->currentVersion(),
            'Accept'     => 'application/vnd.github.v3+json',
        ];

        $token = config('services.github.token');

        if ($token) {
            $headers['Authorization'] = "token {$token}";
        }

        return $headers;
    }
}
