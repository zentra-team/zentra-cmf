<?php

namespace App\Support;

use App\Services\ApiJsonGenerator;
use App\Services\PublicCacheManager;
use App\Services\RssGenerator;
use App\Services\SitemapGenerator;
use Illuminate\Support\Facades\Cache;

final class PublicCacheInvalidator
{
    public static function flushAll(): void
    {
        try {
            Cache::forget(SitemapGenerator::CACHE_KEY);
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            Cache::forget(RssGenerator::SITE_KEY);
            \App\Models\Rubric::pluck('id')->each(
                fn ($id) => Cache::forget(RssGenerator::rubricKey((int) $id)),
            );
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            app(ApiJsonGenerator::class)->flushAll();
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            app(PublicCacheManager::class)->flushAll();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function flushForDocument(int $rubricId): void
    {
        try {
            Cache::forget(SitemapGenerator::CACHE_KEY);
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            Cache::forget(RssGenerator::SITE_KEY);

            if ($rubricId > 0) {
                Cache::forget(RssGenerator::rubricKey($rubricId));
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            if ($rubricId > 0) {
                app(ApiJsonGenerator::class)->flushRubric($rubricId);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        try {
            app(PublicCacheManager::class)->flushAll();
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function flushPublicHttpCache(): void
    {
        try {
            app(PublicCacheManager::class)->flushAll();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
