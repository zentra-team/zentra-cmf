<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\Document;
use App\Models\Rubric;
use App\Models\RubricField;
use App\Models\Setting;
use App\Support\DocumentUrl;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class ApiJsonGenerator
{
    public const CACHE_PREFIX_RUBRIC = 'api.rubric.';
    public const CACHE_PREFIX_DOCUMENT = 'api.document.';
    public const CACHE_RUBRICS_LIST = 'api.rubrics.list';

    public function listRubrics(?ApiToken $token = null): array
    {
        $all = Cache::remember(
            self::CACHE_RUBRICS_LIST,
            $this->cacheTtl(),
            function () {
                return Rubric::where('api_enabled', true)
                    ->orderBy('position')
                    ->orderBy('id')
                    ->get(['id', 'alias', 'title', 'description'])
                    ->map(fn (Rubric $r) => [
                        'id'          => (int) $r->id,
                        'alias'       => (string) $r->alias,
                        'title'       => (string) $r->title,
                        'description' => (string) ($r->description ?? ''),
                    ])
                    ->values()
                    ->all();
            },
        );

        if ($token !== null && !empty($token->allowed_rubrics)) {
            $allowed = array_map('intval', $token->allowed_rubrics);
            $all = array_values(array_filter($all, fn ($r) => in_array($r['id'], $allowed, true)));
        }

        return ['data' => $all];
    }

    public function showRubric(Rubric $rubric): array
    {
        $payload = Cache::remember(
            self::CACHE_PREFIX_RUBRIC . $rubric->id,
            $this->cacheTtl(),
            function () use ($rubric) {
                $fields = RubricField::where('rubric_id', $rubric->id)
                    ->where('in_api', true)
                    ->orderBy('position')
                    ->get(['id', 'alias', 'title', 'type'])
                    ->map(fn (RubricField $f) => [
                        'alias' => (string) $f->alias,
                        'title' => (string) $f->title,
                        'type'  => (string) $f->type,
                    ])
                    ->values()
                    ->all();

                return [
                    'id'          => (int) $rubric->id,
                    'alias'       => (string) $rubric->alias,
                    'title'       => (string) $rubric->title,
                    'description' => (string) ($rubric->description ?? ''),
                    'fields'      => $fields,
                ];
            },
        );

        return ['data' => $payload];
    }

    public function listDocuments(Rubric $rubric, array $params, string $baseUrl): array
    {
        $page = max(1, (int) ($params['page'] ?? 1));
        $perPage = $this->resolvePerPage($rubric, (int) ($params['per_page'] ?? 0));
        [$sortColumn, $sortDir] = $this->resolveSort((string) ($params['sort'] ?? ''));

        $cacheKey = self::CACHE_PREFIX_RUBRIC . $rubric->id . '.docs.' . md5(serialize([$page, $perPage, $sortColumn, $sortDir]));

        return Cache::remember(
            $cacheKey,
            $this->cacheTtl(),
            fn () => $this->buildDocumentsPage($rubric, $page, $perPage, $sortColumn, $sortDir, $baseUrl),
        );
    }

    public function showDocument(Rubric $rubric, Document $document, string $baseUrl): array
    {
        $payload = Cache::remember(
            self::CACHE_PREFIX_DOCUMENT . $document->id,
            $this->cacheTtl(),
            fn () => ['data' => $this->serializeDocument($document, $rubric, $baseUrl, full: true)],
        );

        return $payload;
    }

    public function flushAll(): void
    {
        Cache::forget(self::CACHE_RUBRICS_LIST);

        Rubric::pluck('id')->each(function ($id) {
            Cache::forget(self::CACHE_PREFIX_RUBRIC . $id);
        });
        Document::pluck('id')->each(function ($id) {
            Cache::forget(self::CACHE_PREFIX_DOCUMENT . $id);
        });
    }

    public function flushRubric(int $rubricId): void
    {
        Cache::forget(self::CACHE_RUBRICS_LIST);
        Cache::forget(self::CACHE_PREFIX_RUBRIC . $rubricId);

        Document::where('rubric_id', $rubricId)->pluck('id')->each(function ($id) {
            Cache::forget(self::CACHE_PREFIX_DOCUMENT . $id);
        });
    }

    public function flushDocument(int $documentId, ?int $rubricId = null): void
    {
        Cache::forget(self::CACHE_PREFIX_DOCUMENT . $documentId);

        if ($rubricId !== null) {
            $this->flushRubric($rubricId);
        }
    }

    private function buildDocumentsPage(
        Rubric $rubric,
        int $page,
        int $perPage,
        string $sortColumn,
        string $sortDir,
        string $baseUrl,
    ): array {
        $query = Document::where('rubric_id', $rubric->id)
            ->where('status', Document::STATUS_ACTIVE)
            ->whereNotNull('alias')
            ->where('alias', '!=', '')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('unpublished_at')->orWhere('unpublished_at', '>', now());
            });

        $total = (clone $query)->count();
        $lastPage = max(1, (int) ceil($total / $perPage));

        $documents = $query
            ->orderBy($sortColumn, $sortDir)
            ->orderBy('id', 'desc')
            ->forPage($page, $perPage)
            ->with('fields')
            ->get();

        $data = $documents
            ->map(fn (Document $d) => $this->serializeDocument($d, $rubric, $baseUrl, full: false))
            ->values()
            ->all();

        return [
            'data' => $data,
            'meta' => [
                'page'      => $page,
                'per_page'  => $perPage,
                'total'     => $total,
                'last_page' => $lastPage,
            ],
            'links' => $this->buildPaginationLinks($baseUrl, $rubric->alias, $page, $perPage, $lastPage),
        ];
    }

    private function serializeDocument(Document $document, Rubric $rubric, string $baseUrl, bool $full): array
    {
        $url = $baseUrl . DocumentUrl::build(
            $rubric->alias,
            $document->alias,
            (string) Setting::getValue('url_suffix', ''),
        );

        $base = [
            'id'           => (int) $document->id,
            'alias'        => (string) $document->alias,
            'title'        => (string) $document->title,
            'url'          => $url,
            'rubric_alias' => (string) $rubric->alias,
            'published_at' => $document->published_at?->toIso8601String(),
            'created_at'   => $document->created_at?->toIso8601String(),
            'updated_at'   => $document->updated_at?->toIso8601String(),
        ];

        if ($full) {
            $base['meta'] = [
                'title'       => (string) ($document->meta_title ?? ''),
                'description' => (string) ($document->meta_description ?? ''),
            ];
        }

        $apiFields = $this->loadRubricApiFields($rubric->id);
        $values = [];

        foreach ($apiFields as $field) {
            $raw = $document->fields->firstWhere('field_id', $field->id)?->value;
            $values[$field->alias] = $this->castFieldValue($field, $raw);
        }

        $base['fields'] = $values;

        return $base;
    }

    private function loadRubricApiFields(int $rubricId): Collection
    {
        static $cache = [];

        if (!isset($cache[$rubricId])) {
            $cache[$rubricId] = RubricField::where('rubric_id', $rubricId)
                ->where('in_api', true)
                ->orderBy('position')
                ->get();
        }

        return $cache[$rubricId];
    }

    private function castFieldValue(RubricField $field, ?string $raw): mixed
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $type = $field->type;

        if ($type === 'checkbox') {
            return $raw === '1' || $raw === 'true';
        }

        if (in_array($type, ['number', 'rating', 'slider'], true)) {
            return is_numeric($raw) ? (str_contains($raw, '.') ? (float) $raw : (int) $raw) : $raw;
        }

        if ($type === 'tags') {
            return collect(explode(',', $raw))
                ->map(fn ($s) => trim($s))
                ->filter(fn ($s) => $s !== '')
                ->values()
                ->all();
        }

        $first = $raw[0] ?? '';

        if ($first === '[' || $first === '{') {
            $decoded = json_decode($raw, true);

            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
        }

        return $raw;
    }

    private function resolveSort(string $sort): array
    {
        $allowed = ['created_at', 'published_at', 'position', 'title', 'id'];
        $dir = 'desc';

        if (str_starts_with($sort, '-')) {
            $sort = substr($sort, 1);
            $dir = 'desc';
        } elseif ($sort !== '') {
            $dir = 'asc';
        }

        if (!in_array($sort, $allowed, true)) {
            return ['published_at', 'desc'];
        }

        return [$sort, $dir];
    }

    private function resolvePerPage(Rubric $rubric, int $requested): int
    {
        $globalDefault = (int) Setting::getValue('api_default_per_page', '20');
        $globalMax = (int) Setting::getValue('api_max_per_page', '100');

        $rubricDefault = (int) ($rubric->api_default_limit ?? 0);
        $rubricMax = (int) ($rubric->api_max_limit ?? 0);

        $default = $rubricDefault > 0 ? $rubricDefault : $globalDefault;
        $max = $rubricMax > 0 ? $rubricMax : $globalMax;

        $val = $requested > 0 ? $requested : $default;

        return max(1, min($val, $max));
    }

    private function buildPaginationLinks(string $baseUrl, string $rubricAlias, int $page, int $perPage, int $lastPage): array
    {
        $build = function (int $p) use ($baseUrl, $rubricAlias, $perPage): string {
            $apiPrefix = rtrim((string) Setting::getValue('api_url_prefix', '/api/v1'), '/');

            return $baseUrl . $apiPrefix . '/rubrics/' . $rubricAlias . '/documents?page=' . $p . '&per_page=' . $perPage;
        };

        return [
            'first' => $build(1),
            'last'  => $build($lastPage),
            'prev'  => $page > 1 ? $build($page - 1) : null,
            'next'  => $page < $lastPage ? $build($page + 1) : null,
        ];
    }

    public function findApiRubric(string $alias): ?Rubric
    {
        return Rubric::where('alias', $alias)
            ->where('api_enabled', true)
            ->first();
    }

    public function findApiDocument(Rubric $rubric, string $docAlias): ?Document
    {
        return Document::where('rubric_id', $rubric->id)
            ->where('alias', $docAlias)
            ->where('status', Document::STATUS_ACTIVE)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('unpublished_at')->orWhere('unpublished_at', '>', now()))
            ->with('fields')
            ->first();
    }

    public function docsPageConfig(): array
    {
        return [
            'api_enabled'  => Setting::getValue('api_enabled', '0') === '1',
            'api_domain'   => trim((string) Setting::getValue('api_domain', '')),
            'api_prefix'   => '/' . trim((string) Setting::getValue('api_url_prefix', '/api/v1'), '/'),
            'rate_default' => (int) Setting::getValue('api_default_rate_limit', '60'),
            'cache_ttl'    => (int) Setting::getValue('api_cache_ttl', '300'),
            'site_name'    => (string) Setting::getValue('site_name', 'Zentra'),
        ];
    }

    public function docsPageRubrics(): Collection
    {
        return Rubric::where('api_enabled', true)
            ->orderBy('alias')
            ->get(['id', 'alias', 'title', 'description'])
            ->each(function (Rubric $r): void {
                $r->setRelation(
                    'apiFields',
                    RubricField::where('rubric_id', $r->id)
                        ->where('in_api', true)
                        ->orderBy('position')
                        ->get(['alias', 'title', 'type']),
                );
            });
    }

    public function docsPageSampleDoc(Rubric $rubric): ?Document
    {
        return Document::where('rubric_id', $rubric->id)
            ->where('status', Document::STATUS_ACTIVE)
            ->whereNotNull('alias')
            ->where('alias', '!=', '')
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->with('fields')
            ->first();
    }

    private function cacheTtl(): int
    {
        return max(0, (int) Setting::getValue('api_cache_ttl', '300'));
    }
}
