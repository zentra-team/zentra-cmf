<?php

namespace App\Services;

use App\Models\DocsRequest;
use App\Models\Document;
use App\Models\RubricField;
use App\Models\Setting;
use App\Support\DocumentUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RequestRenderer
{
    private const SYSTEM_SORT_MAP = [
        'id'           => 'id',
        'title'        => 'title',
        'position'     => 'position',
        'published_at' => 'published_at',
        'updated_at'   => 'updated_at',
        'views'        => 'views',
        'rand'         => null,
    ];

    private const DEFAULT_MAIN = '[if_notempty]<div class="ztr-request-list">[content][pages:links]</div>[/if_notempty]'
        . '[if_empty]<p class="ztr-request-empty text-muted">Нет документов.</p>[/if_empty]';

    private const DEFAULT_ITEM = '<div class="ztr-request-item"><a href="[doc:url]">[doc:title]</a></div>';

    public function renderByAlias(string $alias, Request $httpRequest, int $currentDocId = 0): string
    {
        $req = DocsRequest::where('alias', $alias)->first();

        if ($req === null) {
            return "<!-- request:{$alias} not found -->";
        }

        return $this->render($req, $httpRequest, $currentDocId);
    }

    public function render(DocsRequest $req, Request $httpRequest, int $currentDocId = 0): string
    {
        $cacheTime = max(0, (int) ($req->cache_time ?? 0));

        if ($cacheTime > 0) {
            $pageNum = max(1, (int) $httpRequest->query('page_' . $req->alias, 1));
            $cacheKey = "request:{$req->alias}:p{$pageNum}:x{$currentDocId}";

            return Cache::remember($cacheKey, $cacheTime, fn () => $this->doRender($req, $httpRequest, $currentDocId));
        }

        return $this->doRender($req, $httpRequest, $currentDocId);
    }

    private function doRender(DocsRequest $req, Request $httpRequest, int $currentDocId): string
    {
        $rubricIds = $req->rubric_ids ?? [];
        $limit = (int) ($req->limit ?? 0);
        $fetchMode = $req->fetch_mode ?? 'global';

        if ($fetchMode === 'distributed' && !empty($rubricIds) && count($rubricIds) > 1 && $limit > 0) {
            $documents = $this->fetchDistributed($rubricIds, $limit, $req, $currentDocId);
            $total = $documents->count();

            $pageParam = 'page_' . $req->alias;
            $currentPage = 1;
            $totalPages = 1;
        } else {
            $query = $this->buildGlobalQuery($req, $rubricIds, $currentDocId);
            $this->applySorting($query, $req);

            $pageParam = 'page_' . $req->alias;
            $currentPage = max(1, (int) $httpRequest->query($pageParam, 1));
            $perPage = max(1, (int) ($req->per_page ?? 10));
            $totalPages = 1;
            $total = 0;

            if ($req->show_pagination && $req->per_page > 0) {
                $total = $query->count();
                $totalPages = (int) ceil($total / $perPage);
                $totalPages = max(1, $totalPages);
                $documents = $query->offset(($currentPage - 1) * $perPage)->limit($perPage)->get();
            } else {
                if ($limit > 0) {
                    $query->limit($limit);
                }

                $documents = $query->get();
                $total = $documents->count();
            }
        }

        $isEmpty = $documents->isEmpty();
        $count = $documents->count();

        $rubricIds = $req->rubric_ids ?? [];
        $rubricFieldIndex = [];

        if (!empty($rubricIds)) {
            $allFields = RubricField::whereIn('rubric_id', $rubricIds)->get();

            foreach ($allFields as $rf) {
                $rubricFieldIndex[$rf->id] = [
                    'alias'  => $rf->alias,
                    'type'   => $rf->type,
                    'config' => is_array($rf->config) ? $rf->config : [],
                ];
            }
        } else {
            foreach ($req->first_rubric?->fields ?? [] as $rf) {
                $rubricFieldIndex[$rf->id] = [
                    'alias'  => $rf->alias,
                    'type'   => $rf->type,
                    'config' => is_array($rf->config) ? $rf->config : [],
                ];
            }
        }

        $itemsHtml = '';
        $itemTpl = trim($req->template_item ?? '') ?: self::DEFAULT_ITEM;

        foreach ($documents as $idx => $doc) {
            $itemsHtml .= $this->renderItem($itemTpl, $doc, $idx, $count, $rubricFieldIndex, $httpRequest);
        }

        $pagesHtml = '';

        if ($req->show_pagination && $totalPages > 1) {
            $pagesHtml = $this->renderPagination($currentPage, $totalPages, $httpRequest, $pageParam);
        }

        $mainTpl = trim($req->template_main ?? '') ?: self::DEFAULT_MAIN;

        return $this->renderMain($mainTpl, $itemsHtml, $pagesHtml, [
            'pages_current' => $currentPage,
            'pages_total'   => $totalPages,
            'doctotal'      => $total,
            'doconpage'     => $count,
            'is_empty'      => $isEmpty,
        ]);
    }

    private function renderItem(
        string $tpl,
        Document $doc,
        int $idx,
        int $total,
        array $rubricFieldIndex,
        Request $httpRequest,
    ): string {
        $isFirst = $idx === 0;
        $isLast = $idx === $total - 1;
        $num = $idx + 1;

        $byAlias = [];
        $rawByAlias = [];
        $byId = [];
        $fm = app(FieldManager::class);

        foreach ($doc->fields as $df) {
            $raw = $df->value ?? '';
            $info = $rubricFieldIndex[$df->field_id] ?? null;
            $rendered = $raw;

            if ($info !== null) {
                $instance = $fm->instance($info['type']);

                if ($instance !== null) {
                    $rendered = $instance->output($raw, null, $info['config'] ?? []);
                }

                $byAlias[$info['alias']] = $rendered;
                $rawByAlias[$info['alias']] = $raw;
            }

            $byId[$df->field_id] = $rendered;
        }

        $rubricAlias = $doc->rubric?->alias ?? '';
        $urlSuffix = Setting::getValue('url_suffix', '');
        $docUrl = DocumentUrl::build($rubricAlias, $doc->alias, $urlSuffix);

        $date = $doc->published_at?->format('d.m.Y') ?? $doc->created_at?->format('d.m.Y') ?? '';
        $time = $doc->published_at?->format('H:i') ?? $doc->created_at?->format('H:i') ?? '';

        $html = $tpl;

        $html = $this->processConditionalBlock($html, 'if_first', $isFirst);
        $html = $this->processConditionalBlock($html, 'if_not_first', !$isFirst);
        $html = $this->processConditionalBlock($html, 'if_last', $isLast);
        $html = $this->processConditionalBlock($html, 'if_not_last', !$isLast);

        $html = preg_replace_callback(
            '/\[if_every:(\d+)\](.*?)\[\/if_every:\1\]/s',
            fn ($m) => ($num % (int) $m[1] === 0) ? $m[2] : '',
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[if_notempty:field:([a-zA-Z0-9_\-]+)\](.*?)\[\/if_notempty:field:\1\]/s',
            fn ($m) => (($byAlias[$m[1]] ?? '') !== '') ? $m[2] : '',
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[if_empty:field:([a-zA-Z0-9_\-]+)\](.*?)\[\/if_empty:field:\1\]/s',
            fn ($m) => (($byAlias[$m[1]] ?? '') === '') ? $m[2] : '',
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[if_field:([a-zA-Z0-9_\-]+)=([^\[\]]*?)\](.*?)\[\/if_field:\1=\2\]/s',
            fn ($m) => (($rawByAlias[$m[1]] ?? '') === $m[2]) ? $m[3] : '',
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[if_not_field:([a-zA-Z0-9_\-]+)=([^\[\]]*?)\](.*?)\[\/if_not_field:\1=\2\]/s',
            fn ($m) => (($rawByAlias[$m[1]] ?? '') !== $m[2]) ? $m[3] : '',
            $html,
        ) ?? $html;

        $rubric = $doc->rubric;
        $rubricTitle = e($rubric?->title ?? '');
        $rubricAlias2 = e($rubric?->alias ?? '');
        $rubricColor = e($rubric?->color ?? '');
        $rubricUrl = DocumentUrl::rubric($rubricAlias);

        $html = str_replace([
            '[doc:id]',
            '[doc:num]',
            '[doc:title]',
            '[doc:url]',
            '[doc:date]',
            '[doc:time]',
            '[doc:author]',
            '[doc:views]',
            '[doc:rubric]',
            '[doc:rubric_alias]',
            '[doc:rubric_url]',
            '[doc:rubric_color]',
            '[docid]',
            '[docitemnum]',
            '[doctitle]',
            '[doc_url]',
            '[docdate]',
            '[doctime]',
            '[docauthor]',
            '[docviews]',
        ], [
            (string) $doc->id,
            (string) $num,
            e($doc->title),
            $docUrl,
            $date,
            $time,
            e($doc->author?->name ?? ''),
            (string) ($doc->views ?? 0),
            $rubricTitle,
            $rubricAlias2,
            $rubricUrl,
            $rubricColor,
            (string) $doc->id,
            (string) $num,
            e($doc->title),
            $docUrl,
            $date,
            $time,
            e($doc->author?->name ?? ''),
            (string) ($doc->views ?? 0),
        ], $html);

        $html = preg_replace_callback(
            '/\[field:([a-zA-Z][a-zA-Z0-9_\-]*)\]/',
            fn ($m) => $byAlias[$m[1]] ?? '',
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[field:(\d+)\]/',
            fn ($m) => $byId[(int) $m[1]] ?? '',
            $html,
        ) ?? $html;

        return $html;
    }

    private function renderMain(string $tpl, string $content, string $pages, array $ctx): string
    {
        $isEmpty = $ctx['is_empty'];

        $html = $this->processConditionalBlock($tpl, 'if_empty', $isEmpty);
        $html = $this->processConditionalBlock($html, 'if_notempty', !$isEmpty);

        $html = str_replace([
            '[content]',
            '[pages:links]',
            '[pages:current]',
            '[pages:total]',
            '[docs:total]',
            '[docs:page]',
            '[pages]',
            '[pages_current]',
            '[pages_total]',
            '[doctotal]',
            '[doconpage]',
            '[pagetitle]',
        ], [
            $content,
            $pages,
            (string) $ctx['pages_current'],
            (string) $ctx['pages_total'],
            (string) $ctx['doctotal'],
            (string) $ctx['doconpage'],
            $pages,
            (string) $ctx['pages_current'],
            (string) $ctx['pages_total'],
            (string) $ctx['doctotal'],
            (string) $ctx['doconpage'],
            '',
        ], $html);

        return $html;
    }

    private function applyConditions(\Illuminate\Database\Eloquent\Builder $query, array $conditions, ?int $rubricId): void
    {
        $active = array_filter($conditions, fn ($c) => (bool) ($c['active'] ?? true));

        if (empty($active)) {
            return;
        }

        $first = true;

        foreach ($active as $cond) {
            $field = trim($cond['field'] ?? '');
            $operator = trim($cond['operator'] ?? '=');
            $value = $cond['value'] ?? '';
            $logic = strtoupper(trim($cond['logic'] ?? 'AND'));

            if ($field === '') {
                continue;
            }

            $subQuery = function ($q) use ($field, $operator, $value) {
                $this->applyFieldCondition($q, $field, $operator, $value);
            };

            if ($first || $logic === 'AND') {
                $query->where($subQuery);
            } else {
                $query->orWhere($subQuery);
            }
            $first = false;
        }
    }

    private function applyFieldCondition(\Illuminate\Database\Eloquent\Builder $q, string $field, string $operator, string $value): void
    {
        $q->whereExists(function ($sub) use ($field, $operator, $value) {
            $sub->select(DB::raw(1))
                ->from('document_fields')
                ->join('rubric_fields', 'rubric_fields.id', '=', 'document_fields.field_id')
                ->whereColumn('document_fields.document_id', 'documents.id')
                ->where('rubric_fields.alias', $field);

            match ($operator) {
                '='         => $sub->where('document_fields.value', $value),
                '!='        => $sub->where('document_fields.value', '!=', $value),
                'like'      => $sub->where('document_fields.value', 'ilike', '%' . $value . '%'),
                'not_like'  => $sub->where('document_fields.value', 'not ilike', '%' . $value . '%'),
                '>'         => $sub->where('document_fields.value', '>', $value),
                '>='        => $sub->where('document_fields.value', '>=', $value),
                '<'         => $sub->where('document_fields.value', '<', $value),
                '<='        => $sub->where('document_fields.value', '<=', $value),
                'empty'     => $sub->where(fn ($s) => $s->whereNull('document_fields.value')->orWhere('document_fields.value', '')),
                'not_empty' => $sub->whereNotNull('document_fields.value')->where('document_fields.value', '!=', ''),
                default     => $sub->where('document_fields.value', $value),
            };
        });
    }

    private function applySorting(\Illuminate\Database\Eloquent\Builder $query, DocsRequest $req): void
    {
        $dir = $req->sort_order === 'asc' ? 'asc' : 'desc';

        if ($req->sort_field) {
            $alias = $req->sort_field;
            $query->orderByRaw(
                '(SELECT df.value FROM document_fields df
                  JOIN rubric_fields rf ON rf.id = df.field_id
                  WHERE df.document_id = documents.id AND rf.alias = ?
                  LIMIT 1) ' . $dir,
                [$alias],
            );

            return;
        }

        if ($req->sort_system) {
            if ($req->sort_system === 'rand') {
                $query->orderByRaw('RANDOM()');

                return;
            }

            $col = self::SYSTEM_SORT_MAP[$req->sort_system] ?? null;

            if ($col) {
                if ($col === 'views') {
                    $query->orderByRaw('CASE WHEN views > 0 THEN 0 ELSE 1 END')
                          ->orderBy('views', $dir)
                          ->orderBy('id', 'desc');
                } else {
                    $query->orderBy($col, $dir);
                }

                return;
            }
        }

        $query->orderBy('id', 'desc');
    }

    private function renderPagination(int $current, int $total, Request $httpRequest, string $pageParam): string
    {
        if ($total <= 1) {
            return '';
        }

        $params = $httpRequest->query();
        $html = '<nav aria-label="pagination"><ul class="pagination">';

        if ($current > 1) {
            $url = $httpRequest->fullUrlWithQuery(array_merge($params, [$pageParam => $current - 1]));
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . '">&laquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&laquo;</span></li>';
        }

        $window = 3;
        $start = max(1, $current - $window);
        $end = min($total, $current + $window);

        if ($start > 1) {
            $url = $httpRequest->fullUrlWithQuery(array_merge($params, [$pageParam => 1]));
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . '">1</a></li>';

            if ($start > 2) {
                $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            }
        }

        for ($p = $start; $p <= $end; $p++) {
            if ($p === $current) {
                $html .= '<li class="page-item active"><span class="page-link">' . $p . '</span></li>';
            } else {
                $url = $httpRequest->fullUrlWithQuery(array_merge($params, [$pageParam => $p]));
                $html .= '<li class="page-item"><a class="page-link" href="' . $url . '">' . $p . '</a></li>';
            }
        }

        if ($end < $total) {
            if ($end < $total - 1) {
                $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
            }

            $url = $httpRequest->fullUrlWithQuery(array_merge($params, [$pageParam => $total]));
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . '">' . $total . '</a></li>';
        }

        if ($current < $total) {
            $url = $httpRequest->fullUrlWithQuery(array_merge($params, [$pageParam => $current + 1]));
            $html .= '<li class="page-item"><a class="page-link" href="' . $url . '">&raquo;</a></li>';
        } else {
            $html .= '<li class="page-item disabled"><span class="page-link">&raquo;</span></li>';
        }

        $html .= '</ul></nav>';

        return $html;
    }

    private function buildGlobalQuery(DocsRequest $req, array $rubricIds, int $currentDocId): \Illuminate\Database\Eloquent\Builder
    {
        $query = Document::with(['rubric', 'fields', 'author'])
            ->where('status', Document::STATUS_ACTIVE)
            ->where(function ($q) {
                $now = now();
                $q->whereNull('published_at')->orWhere('published_at', '<=', $now);
            })
            ->where(function ($q) {
                $now = now();
                $q->whereNull('unpublished_at')->orWhere('unpublished_at', '>', $now);
            });

        if (!empty($rubricIds)) {
            $query->whereIn('rubric_id', $rubricIds);
        }

        if ($req->exclude_current && $currentDocId > 0) {
            $query->where('id', '!=', $currentDocId);
        }

        $this->applyConditions($query, $req->conditions ?? [], $req->first_rubric_id);

        return $query;
    }

    private function fetchDistributed(array $rubricIds, int $limit, DocsRequest $req, int $currentDocId): \Illuminate\Support\Collection
    {
        $all = collect();

        foreach ($rubricIds as $rubricId) {
            $q = $this->buildGlobalQuery($req, [$rubricId], $currentDocId);
            $this->applySorting($q, $req);
            $q->limit($limit);
            $all = $all->merge($q->get());
        }

        $dir = $req->sort_order === 'asc' ? 'asc' : 'desc';

        if ($req->sort_system === 'rand') {
            return $all->shuffle()->values();
        }

        if ($req->sort_field) {
            $alias = $req->sort_field;
            $all = $all->sort(function ($a, $b) use ($alias, $dir) {
                $aVal = $a->fields->first(fn ($f) => ($f->rubricField->alias ?? '') === $alias)?->value ?? '';
                $bVal = $b->fields->first(fn ($f) => ($f->rubricField->alias ?? '') === $alias)?->value ?? '';
                $cmp = strcmp($aVal, $bVal);

                return $dir === 'asc' ? $cmp : -$cmp;
            });

            return $all->values();
        }

        if ($req->sort_system) {
            $col = self::SYSTEM_SORT_MAP[$req->sort_system] ?? 'id';

            if ($col === 'views') {
                $all = $all->sort(function ($a, $b) use ($dir) {
                    $aHas = ($a->views ?? 0) > 0 ? 0 : 1;
                    $bHas = ($b->views ?? 0) > 0 ? 0 : 1;

                    if ($aHas !== $bHas) {
                        return $aHas - $bHas;
                    }
                    $cmp = ($a->views ?? 0) <=> ($b->views ?? 0);

                    if ($cmp !== 0) {
                        return $dir === 'asc' ? $cmp : -$cmp;
                    }

                    return $b->id <=> $a->id;
                });
            } else {
                $all = $dir === 'asc' ? $all->sortBy($col) : $all->sortByDesc($col);
            }

            return $all->values();
        }

        return ($dir === 'asc' ? $all->sortBy('id') : $all->sortByDesc('id'))->values();
    }

    private function processConditionalBlock(string $html, string $tag, bool $show): string
    {
        return preg_replace_callback(
            '/\[' . preg_quote($tag, '/') . '\](.*?)\[\/' . preg_quote($tag, '/') . '\]/s',
            fn ($m) => $show ? $m[1] : '',
            $html,
        ) ?? $html;
    }
}
