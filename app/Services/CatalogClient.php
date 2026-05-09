<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class CatalogClient
{
    private const CACHE_KEY = 'modules_catalog';

    public function modules(): array
    {
        $catalog = $this->fetch();
        $modules = $catalog['modules'] ?? [];

        return is_array($modules) ? array_values(array_filter($modules, 'is_array')) : [];
    }

    public function fetch(): array
    {
        $url = (string) config('modules.catalog_url', '');

        if ($url === '') {
            return ['modules' => []];
        }

        $ttl = max(60, (int) config('modules.catalog_cache_ttl', 3600));

        return Cache::remember(self::CACHE_KEY, $ttl, function () use ($url) {
            try {
                $response = Http::timeout(10)->acceptJson()->get($url);

                if (!$response->ok()) {
                    return ['modules' => []];
                }

                $data = $response->json();

                return is_array($data) ? $data : ['modules' => []];
            } catch (\Throwable) {
                return ['modules' => []];
            }
        });
    }

    public function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
