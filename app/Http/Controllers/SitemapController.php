<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Services\SitemapGenerator;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        private readonly SitemapGenerator $generator,
    ) {
    }

    public function index(): Response
    {
        if (Setting::getValue('sitemap_enabled', '1') !== '1') {
            return $this->notFound();
        }

        $xml = $this->generator->needsIndex()
            ? $this->generator->renderSitemapIndex()
            : $this->generator->renderUrlset();

        return $this->xmlResponse($xml);
    }

    public function chunk(int $n): Response
    {
        if (Setting::getValue('sitemap_enabled', '1') !== '1') {
            return $this->notFound();
        }

        if ($n < 1 || $n > $this->generator->chunkCount()) {
            return $this->notFound();
        }

        return $this->xmlResponse($this->generator->renderUrlset($n));
    }

    private function xmlResponse(string $xml): Response
    {
        $ttl = max(0, (int) Setting::getValue('sitemap_cache_ttl', '3600'));

        $headers = [
            'Content-Type' => 'application/xml; charset=utf-8',
        ];

        if ($ttl > 0) {
            $headers['Cache-Control'] = 'public, max-age=' . $ttl;
        }

        return response($xml, 200, $headers);
    }

    private function notFound(): Response
    {
        return response('Not Found', 404, ['Content-Type' => 'text/plain']);
    }
}
