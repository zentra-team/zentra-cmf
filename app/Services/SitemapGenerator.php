<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Rubric;
use App\Models\Setting;
use App\Support\DocumentUrl;
use Illuminate\Support\Facades\Cache;

class SitemapGenerator
{
    public const CACHE_KEY = 'sitemap.urls';

    public function collectUrls(): array
    {
        $ttl = max(0, (int) Setting::getValue('sitemap_cache_ttl', '3600'));

        if ($ttl === 0) {
            return $this->buildUrlList();
        }

        return Cache::remember(self::CACHE_KEY, $ttl, fn () => $this->buildUrlList());
    }

    public function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function count(): int
    {
        return count($this->collectUrls());
    }

    public function maxUrlsPerFile(): int
    {
        return max(100, (int) Setting::getValue('sitemap_max_urls_per_file', '50000'));
    }

    public function needsIndex(): bool
    {
        return $this->count() > $this->maxUrlsPerFile();
    }

    public function chunkCount(): int
    {
        $total = $this->count();
        $perFile = $this->maxUrlsPerFile();

        if ($total === 0) {
            return 0;
        }

        return (int) ceil($total / $perFile);
    }

    public function renderUrlset(?int $chunk = null): string
    {
        $urls = $this->collectUrls();

        if ($chunk !== null) {
            $perFile = $this->maxUrlsPerFile();
            $offset = ($chunk - 1) * $perFile;
            $urls = array_slice($urls, $offset, $perFile);
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= '    <loc>' . htmlspecialchars($u['loc'], ENT_XML1) . "</loc>\n";

            if (!empty($u['lastmod'])) {
                $xml .= '    <lastmod>' . $u['lastmod'] . "</lastmod>\n";
            }

            if (!empty($u['changefreq'])) {
                $xml .= '    <changefreq>' . $u['changefreq'] . "</changefreq>\n";
            }

            if ($u['priority'] !== null) {
                $xml .= '    <priority>' . number_format((float) $u['priority'], 1, '.', '') . "</priority>\n";
            }
            $xml .= "  </url>\n";
        }

        $xml .= '</urlset>' . "\n";

        return $xml;
    }

    public function renderSitemapIndex(): string
    {
        $base = $this->baseUrl();
        $count = $this->chunkCount();
        $now = now()->toIso8601String();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        for ($i = 1; $i <= $count; $i++) {
            $xml .= "  <sitemap>\n";
            $xml .= '    <loc>' . $base . '/sitemap-' . $i . ".xml</loc>\n";
            $xml .= '    <lastmod>' . $now . "</lastmod>\n";
            $xml .= "  </sitemap>\n";
        }

        $xml .= '</sitemapindex>' . "\n";

        return $xml;
    }

    public function preview(): array
    {
        $urls = $this->buildUrlList();
        $stats = $this->buildExclusionStats();

        return array_merge($stats, [
            'total'  => count($urls),
            'chunks' => count($urls) > 0 ? (int) ceil(count($urls) / $this->maxUrlsPerFile()) : 0,
        ]);
    }

    private function buildUrlList(): array
    {
        $urls = [];

        $base = $this->baseUrl();
        $suffix = (string) Setting::getValue('url_suffix', '');
        $defCf = (string) Setting::getValue('sitemap_default_changefreq', 'weekly');
        $defPr = (float) Setting::getValue('sitemap_default_priority', '0.5');
        $lastmodSource = (string) Setting::getValue('sitemap_lastmod_source', 'updated_at');

        $includeHome = Setting::getValue('sitemap_include_homepage', '1') === '1';
        $includeRubricIdx = Setting::getValue('sitemap_include_rubric_indexes', '1') === '1';

        if ($includeHome) {
            $home = Document::where('alias', 'index')
                ->where('status', Document::STATUS_ACTIVE)
                ->first()
                ?? Document::where('status', Document::STATUS_ACTIVE)
                    ->orderBy('position')
                    ->orderBy('id')
                    ->first();

            if ($home !== null && $this->isPublished($home) && !$this->isNoindex($home)) {
                $urls[] = [
                    'loc'        => $base . '/',
                    'lastmod'    => $this->lastmodFor($home, $lastmodSource),
                    'changefreq' => $home->sitemap_changefreq ?: $defCf,
                    'priority'   => 1.0,
                ];
            }
        }

        $rubricsIncluded = Rubric::where('sitemap_include', true)->get()->keyBy('id');

        if ($rubricsIncluded->isEmpty()) {
            return $urls;
        }

        if ($includeRubricIdx) {
            $indexDocs = Document::whereIn('rubric_id', $rubricsIncluded->keys())
                ->whereNull('alias')
                ->where('status', Document::STATUS_ACTIVE)
                ->get()
                ->keyBy('rubric_id');

            foreach ($rubricsIncluded as $r) {
                if (!$r->alias) {
                    continue;
                }
                $idx = $indexDocs->get($r->id);

                if ($idx === null || !$this->isPublished($idx) || $this->isNoindex($idx)) {
                    continue;
                }

                $urls[] = [
                    'loc'        => $base . '/' . $r->alias,
                    'lastmod'    => $this->lastmodFor($idx, $lastmodSource),
                    'changefreq' => $idx->sitemap_changefreq ?: $r->sitemap_index_changefreq ?: $r->sitemap_changefreq ?: $defCf,
                    'priority'   => $idx->sitemap_priority ?? $r->sitemap_index_priority ?? $r->sitemap_priority ?? $defPr,
                ];
            }
        }

        Document::whereIn('rubric_id', $rubricsIncluded->keys())
            ->where('status', Document::STATUS_ACTIVE)
            ->whereNotNull('alias')
            ->where('alias', '!=', '')
            ->where(function ($q) {
                $q->whereNull('meta_robots')->orWhere('meta_robots', 'not ilike', 'noindex%');
            })
            ->orderBy('rubric_id')
            ->orderBy('id')
            ->chunk(500, function ($chunk) use (&$urls, $rubricsIncluded, $base, $suffix, $defCf, $defPr, $lastmodSource) {
                foreach ($chunk as $doc) {
                    if (!$this->isPublished($doc)) {
                        continue;
                    }

                    if ($doc->alias === 'index') {
                        continue;
                    }

                    $rubric = $rubricsIncluded[$doc->rubric_id] ?? null;

                    if (!$rubric) {
                        continue;
                    }

                    $urls[] = [
                        'loc'        => $base . DocumentUrl::build($rubric->alias, $doc->alias, $suffix),
                        'lastmod'    => $this->lastmodFor($doc, $lastmodSource),
                        'changefreq' => $doc->sitemap_changefreq ?: $rubric->sitemap_changefreq ?: $defCf,
                        'priority'   => $doc->sitemap_priority ?? $rubric->sitemap_priority ?? $defPr,
                    ];
                }
            });

        return $urls;
    }

    private function buildExclusionStats(): array
    {
        $rubricsIncludedIds = Rubric::where('sitemap_include', true)->pluck('id')->all();
        $rubricsExcludedIds = Rubric::where('sitemap_include', false)->pluck('id')->all();

        $homepageIncluded = false;

        if (Setting::getValue('sitemap_include_homepage', '1') === '1') {
            $home = Document::where('alias', 'index')
                ->where('status', Document::STATUS_ACTIVE)
                ->first();
            $homepageIncluded = $home !== null && $this->isPublished($home) && !$this->isNoindex($home);
        }

        $rubricIndexes = 0;

        if (Setting::getValue('sitemap_include_rubric_indexes', '1') === '1' && !empty($rubricsIncludedIds)) {
            $rubricIndexes = Document::whereIn('rubric_id', $rubricsIncludedIds)
                ->whereNull('alias')
                ->where('status', Document::STATUS_ACTIVE)
                ->where(function ($q) {
                    $q->whereNull('meta_robots')->orWhere('meta_robots', 'not ilike', 'noindex%');
                })
                ->count();
        }

        $documents = empty($rubricsIncludedIds) ? 0 : Document::whereIn('rubric_id', $rubricsIncludedIds)
            ->where('status', Document::STATUS_ACTIVE)
            ->whereNotNull('alias')
            ->where('alias', '!=', '')
            ->where('alias', '!=', 'index')
            ->where(function ($q) {
                $q->whereNull('meta_robots')->orWhere('meta_robots', 'not ilike', 'noindex%');
            })
            ->count();

        $excludedNoindex = Document::where('status', Document::STATUS_ACTIVE)
            ->where('meta_robots', 'ilike', 'noindex%')
            ->count();

        $excludedUnpublished = Document::where('status', '!=', Document::STATUS_ACTIVE)->count();

        $excludedRubric = empty($rubricsExcludedIds) ? 0 : Document::whereIn('rubric_id', $rubricsExcludedIds)
            ->where('status', Document::STATUS_ACTIVE)
            ->count();

        return [
            'homepage'             => $homepageIncluded,
            'rubric_indexes'       => $rubricIndexes,
            'documents'            => $documents,
            'excluded_noindex'     => $excludedNoindex,
            'excluded_unpublished' => $excludedUnpublished,
            'excluded_rubric'      => $excludedRubric,
        ];
    }

    private function isPublished(Document $d): bool
    {
        if ($d->published_at !== null && $d->published_at->isFuture()) {
            return false;
        }

        return !($d->unpublished_at !== null && $d->unpublished_at->isPast())

        ;
    }

    private function isNoindex(Document $d): bool
    {
        $r = (string) ($d->meta_robots ?? '');

        return $r !== '' && str_starts_with(strtolower($r), 'noindex');
    }

    private function lastmodFor(Document $d, string $source): ?string
    {
        $field = match ($source) {
            'created_at'   => $d->created_at,
            'published_at' => $d->published_at ?? $d->updated_at,
            default        => $d->updated_at,
        };

        return $field?->toIso8601String();
    }

    private function baseUrl(): string
    {
        $url = (string) config('app.url', '');

        return rtrim($url, '/') ?: '';
    }
}
