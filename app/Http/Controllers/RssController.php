<?php

namespace App\Http\Controllers;

use App\Models\Rubric;
use App\Models\Setting;
use App\Services\RssGenerator;
use Illuminate\Http\Response;

class RssController extends Controller
{
    public function __construct(
        private readonly RssGenerator $generator,
    ) {
    }

    public function forSite(): Response
    {
        $xml = $this->generator->forSite();

        if ($xml === null) {
            return $this->notFound();
        }

        return $this->xmlResponse($xml);
    }

    public function forRubric(string $alias): Response
    {
        $rubric = Rubric::where('alias', $alias)->first();

        if (!$rubric) {
            return $this->notFound();
        }

        $xml = $this->generator->forRubric($rubric);

        if ($xml === null) {
            return $this->notFound();
        }

        return $this->xmlResponse($xml);
    }

    private function xmlResponse(string $xml): Response
    {
        $ttl = max(0, (int) Setting::getValue('rss_cache_ttl', '1800'));
        $headers = ['Content-Type' => 'application/rss+xml; charset=utf-8'];

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
