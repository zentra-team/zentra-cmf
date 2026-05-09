<?php

namespace App\Http\Middleware;

use App\Services\PublicCacheManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CachePublicResponse
{
    public function __construct(
        private readonly PublicCacheManager $manager,
    ) {
    }

    public function handle(Request $request, \Closure $next)
    {
        if (!$this->manager->shouldHandle($request)) {
            $response = $next($request);
            $this->addStatusHeader($response, 'BYPASS');

            return $response;
        }

        $key = $this->manager->getKey($request);
        $cached = $this->manager->get($key);

        if ($cached !== null) {
            return response($cached['content'], $cached['status'] ?? 200, [
                'Content-Type' => $cached['ctype'] ?? 'text/html; charset=utf-8',
                'X-Cache'      => $this->manager->sendStatusHeaders() ? 'HIT' : null,
            ]);
        }

        $response = $next($request);

        if ($this->manager->shouldStore($request, $response)) {
            $ttl = $this->manager->resolveTtl($request);
            $this->manager->put($key, $response, $ttl);
            $this->addStatusHeader($response, 'MISS');
        } else {
            $this->addStatusHeader($response, 'BYPASS');
        }

        return $response;
    }

    private function addStatusHeader(Response $response, string $status): void
    {
        if (!$this->manager->sendStatusHeaders()) {
            return;
        }

        $response->headers->set('X-Cache', $status);
    }
}
