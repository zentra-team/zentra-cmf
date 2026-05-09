<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Services\RedirectMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HandleRedirects
{
    public function __construct(
        private readonly RedirectMatcher $matcher,
    ) {
    }

    public function handle(Request $request, \Closure $next)
    {
        if (Setting::getValue('redirects_enabled', '1') !== '1') {
            return $next($request);
        }

        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return $next($request);
        }

        $path = '/' . ltrim($request->path(), '/');
        $maxHops = max(1, (int) Setting::getValue('redirects_max_hops', '10'));

        $match = $this->matcher->match($path, $maxHops);

        if ($match === null) {
            return $next($request);
        }

        $target = $match['to'];

        if (!str_starts_with($target, '/') && !str_starts_with($target, 'https://') && !str_starts_with($target, 'http://')) {
            return $next($request);
        }

        if ($match['preserve_query_string'] && $request->getQueryString()) {
            $target .= (str_contains($target, '?') ? '&' : '?') . $request->getQueryString();
        }

        if (Setting::getValue('redirects_track_hits', '1') === '1') {
            $this->matcher->recordHits($match['ids']);
        }

        return redirect()->away($target, $match['type']);
    }

    public function terminate(Request $request, $response): void
    {
        if (Setting::getValue('redirects_log_misses', '0') !== '1') {
            return;
        }

        if ($response->getStatusCode() !== 404) {
            return;
        }

        if (!in_array($request->method(), ['GET', 'HEAD'], true)) {
            return;
        }

        if (\App\Support\SystemPaths::isSystem($request->path())) {
            return;
        }

        try {
            $url = '/' . ltrim($request->path(), '/');
            $referer = $request->headers->get('referer');

            if ($referer !== null && strlen($referer) > 500) {
                $referer = substr($referer, 0, 500);
            }

            DB::statement(
                'INSERT INTO redirect_misses (url, hits, first_seen_at, last_seen_at, last_referer)
                 VALUES (?, 1, NOW(), NOW(), ?)
                 ON CONFLICT (url) DO UPDATE SET
                     hits = redirect_misses.hits + 1,
                     last_seen_at = EXCLUDED.last_seen_at,
                     last_referer = EXCLUDED.last_referer',
                [$url, $referer],
            );
        } catch (\Throwable $e) {
        }
    }
}
