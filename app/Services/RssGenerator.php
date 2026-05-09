<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Rubric;
use App\Models\Setting;
use App\Models\User;
use App\Support\DocumentUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RssGenerator
{
    public const SITE_KEY = 'rss.site';

    public static function rubricKey(int $id): string
    {
        return 'rss.rubric.' . $id;
    }

    public function forRubric(Rubric $rubric): ?string
    {
        if (!$this->moduleEnabled() || !$rubric->rss_enabled) {
            return null;
        }

        $ttl = $this->cacheTtl();

        if ($ttl === 0) {
            return $this->buildRubricXml($rubric);
        }

        return Cache::remember(self::rubricKey((int) $rubric->id), $ttl, fn () => $this->buildRubricXml($rubric));
    }

    public function forSite(): ?string
    {
        if (!$this->moduleEnabled()) {
            return null;
        }

        if (Setting::getValue('rss_site_feed_enabled', '0') !== '1') {
            return null;
        }

        $ttl = $this->cacheTtl();

        if ($ttl === 0) {
            return $this->buildSiteXml();
        }

        return Cache::remember(self::SITE_KEY, $ttl, fn () => $this->buildSiteXml());
    }

    public function flushAll(): void
    {
        Cache::forget(self::SITE_KEY);
        Rubric::pluck('id')->each(fn ($id) => Cache::forget(self::rubricKey((int) $id)));
    }

    public function flushRubric(Rubric $rubric): void
    {
        Cache::forget(self::rubricKey((int) $rubric->id));
        Cache::forget(self::SITE_KEY);
    }

    private function moduleEnabled(): bool
    {
        return Setting::getValue('rss_enabled', '1') === '1';
    }

    private function cacheTtl(): int
    {
        return max(0, (int) Setting::getValue('rss_cache_ttl', '1800'));
    }

    private function buildRubricXml(Rubric $rubric): string
    {
        $limit = $rubric->rss_limit ?: (int) Setting::getValue('rss_default_limit', '20');
        $title = trim((string) $rubric->rss_title) !== '' ? $rubric->rss_title : $rubric->title;
        $desc = trim((string) $rubric->rss_description) !== '' ? $rubric->rss_description : ($rubric->description ?? '');
        $base = $this->baseUrl();
        $link = $base . DocumentUrl::rubric($rubric->alias);
        $self = $base . '/' . trim($rubric->alias ?? '', '/') . '/feed.xml';

        $documents = $this->loadDocuments([(int) $rubric->id], $limit);

        return $this->renderChannel(
            title:       $title,
            description: $desc,
            link:        $link !== '' ? $link : $base,
            selfLink:    $self,
            documents:   $documents,
        );
    }

    private function buildSiteXml(): string
    {
        $limit = (int) Setting::getValue('rss_site_feed_limit', '50');
        $base = $this->baseUrl();

        $title = trim((string) Setting::getValue('rss_site_feed_title', ''));

        if ($title === '') {
            $title = (string) (Setting::getValue('site_name', '') ?: config('app.name', 'Сайт'));
        }

        $desc = (string) Setting::getValue('rss_site_feed_description', '');

        $rubricIds = Rubric::where('rss_enabled', true)->pluck('id')->all();
        $documents = empty($rubricIds) ? collect() : $this->loadDocuments($rubricIds, $limit);

        return $this->renderChannel(
            title:       $title,
            description: $desc,
            link:        $base,
            selfLink:    $base . '/feed.xml',
            documents:   $documents,
        );
    }

    private function loadDocuments(array $rubricIds, int $limit): Collection
    {
        if (empty($rubricIds)) {
            return collect();
        }

        return Document::whereIn('rubric_id', $rubricIds)
            ->where('status', Document::STATUS_ACTIVE)
            ->whereNotNull('alias')
            ->where('alias', '!=', '')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('unpublished_at')->orWhere('unpublished_at', '>', now());
            })
            ->orderByRaw('COALESCE(published_at, created_at) DESC')
            ->orderBy('id', 'desc')
            ->limit($limit)
            ->with(['rubric', 'fields'])
            ->get();
    }

    private function renderChannel(string $title, string $description, string $link, string $selfLink, Collection $documents): string
    {
        $maxLen = (int) Setting::getValue('rss_description_max_length', '500');
        $userIds = $documents->pluck('author_id')->filter()->unique()->all();
        $authorNames = empty($userIds)
            ? []
            : User::whereIn('id', $userIds)->pluck('name', 'id')->all();

        $rubricsCache = $documents->pluck('rubric')->filter()->keyBy('id');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n";
        $xml .= "  <channel>\n";
        $xml .= '    <title>' . $this->xmlEscape($title) . "</title>\n";
        $xml .= '    <link>' . $this->xmlEscape($link) . "</link>\n";
        $xml .= '    <description>' . $this->xmlEscape($description) . "</description>\n";
        $xml .= '    <language>ru</language>' . "\n";
        $xml .= '    <atom:link href="' . $this->xmlEscape($selfLink) . '" rel="self" type="application/rss+xml" />' . "\n";
        $xml .= '    <generator>Zentra CMF</generator>' . "\n";

        if ($documents->isNotEmpty()) {
            $latest = $documents->first();
            $latestDate = $latest->published_at ?? $latest->created_at;

            if ($latestDate !== null) {
                $xml .= '    <lastBuildDate>' . $latestDate->toRfc2822String() . "</lastBuildDate>\n";
            }
        }

        foreach ($documents as $doc) {
            $rubric = $rubricsCache->get($doc->rubric_id) ?? $doc->rubric;

            if (!$rubric) {
                continue;
            }

            $itemUrl = $this->baseUrl() . DocumentUrl::build(
                $rubric->alias,
                $doc->alias,
                (string) Setting::getValue('url_suffix', ''),
            );

            $itemDesc = $this->resolveDescription($doc, $rubric, $maxLen);
            $pubDate = ($doc->published_at ?? $doc->created_at)?->toRfc2822String();

            $xml .= "    <item>\n";
            $xml .= '      <title>' . $this->xmlEscape($doc->title) . "</title>\n";
            $xml .= '      <link>' . $this->xmlEscape($itemUrl) . "</link>\n";
            $xml .= '      <guid isPermaLink="true">' . $this->xmlEscape($itemUrl) . "</guid>\n";

            if ($itemDesc !== '') {
                $xml .= '      <description>' . $this->xmlEscape($itemDesc) . "</description>\n";
            }

            if ($pubDate) {
                $xml .= '      <pubDate>' . $pubDate . "</pubDate>\n";
            }

            $authorName = $authorNames[$doc->author_id] ?? null;

            if ($authorName) {
                $xml .= '      <dc:creator>' . $this->xmlEscape($authorName) . "</dc:creator>\n";
            }

            $enclosure = $this->resolveEnclosure($doc, $rubric);

            if ($enclosure !== null) {
                $xml .= sprintf(
                    "      <enclosure url=\"%s\" type=\"%s\" length=\"0\" />\n",
                    $this->xmlEscape($enclosure['url']),
                    $this->xmlEscape($enclosure['type']),
                );
            }

            foreach ($this->resolveCategories($doc, $rubric) as $cat) {
                $xml .= '      <category>' . $this->xmlEscape($cat) . "</category>\n";
            }

            $xml .= "    </item>\n";
        }

        $xml .= "  </channel>\n";
        $xml .= "</rss>\n";

        return $xml;
    }

    private function resolveDescription(Document $doc, Rubric $rubric, int $maxLen): string
    {
        $raw = '';

        if ($rubric->rss_description_field_id) {
            $field = $doc->fields->firstWhere('field_id', $rubric->rss_description_field_id);

            if ($field !== null) {
                $raw = (string) $field->value;
            }
        }

        if ($raw === '') {
            $raw = (string) ($doc->meta_description ?? '');
        }

        $text = trim(strip_tags($raw));
        $text = preg_replace('/\s+/u', ' ', $text) ?? $text;

        if ($maxLen > 0 && function_exists('mb_strlen') && mb_strlen($text) > $maxLen) {
            $text = mb_substr($text, 0, $maxLen) . '…';
        } elseif ($maxLen > 0 && strlen($text) > $maxLen) {
            $text = substr($text, 0, $maxLen) . '…';
        }

        return $text;
    }

    private function resolveEnclosure(Document $doc, Rubric $rubric): ?array
    {
        if (!$rubric->rss_image_field_id) {
            return null;
        }

        $field = $doc->fields->firstWhere('field_id', $rubric->rss_image_field_id);

        if ($field === null || trim((string) $field->value) === '') {
            return null;
        }

        $value = $field->value;
        $path = '';

        $decoded = json_decode((string) $value, true);

        if (is_array($decoded) && isset($decoded[0])) {
            $first = $decoded[0];

            if (is_array($first) && isset($first['path'])) {
                $path = (string) $first['path'];
            } elseif (is_string($first)) {
                $path = $first;
            }
        }

        if ($path === '' && is_string($value)) {
            $path = trim($value);
        }

        if ($path === '') {
            return null;
        }

        $url = preg_match('#^https?://#i', $path) ? $path : $this->baseUrl() . '/' . ltrim($path, '/');

        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $type = match ($ext) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png'         => 'image/png',
            'gif'         => 'image/gif',
            'webp'        => 'image/webp',
            'avif'        => 'image/avif',
            'svg'         => 'image/svg+xml',
            default       => 'application/octet-stream',
        };

        return ['url' => $url, 'type' => $type];
    }

    private function resolveCategories(Document $doc, Rubric $rubric): array
    {
        if (!$rubric->rss_category_field_id) {
            return [];
        }

        $field = $doc->fields->firstWhere('field_id', $rubric->rss_category_field_id);

        if ($field === null) {
            return [];
        }

        $raw = (string) $field->value;

        if ($raw === '') {
            return [];
        }

        return collect(explode(',', $raw))
            ->map(fn ($s) => trim($s))
            ->filter(fn ($s) => $s !== '')
            ->values()
            ->all();
    }

    private function baseUrl(): string
    {
        return rtrim((string) config('app.url', ''), '/');
    }

    private function xmlEscape(string $s): string
    {
        return htmlspecialchars($s, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }
}
