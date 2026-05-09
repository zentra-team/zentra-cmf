<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentField;
use App\Models\DocumentRevision;
use App\Models\Navigation;
use App\Models\NavigationItem;
use App\Models\Rubric;
use App\Models\RubricField;
use App\Models\Setting;
use App\Support\DocumentUrl;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class DocumentService
{
    private const DOCUMENT_FIELDS = [
        'title', 'alias', 'meta_title', 'meta_description',
        'og_title', 'og_description', 'og_image',
        'meta_robots', 'sitemap_changefreq', 'sitemap_priority', 'published_at',
        'unpublished_at', 'status', 'position', 'nav_item_id', 'breadcrumb_title',
        'parent_doc_id', 'public_cache_disabled', 'public_cache_ttl',
    ];

    private const SORTABLE = ['id', 'position', 'title', 'alias', 'published_at', 'updated_at'];

    public function __construct(private readonly RubricAccessGate $gate)
    {
    }

    public function buildListQuery(Request $request, Authenticatable $user): Builder
    {
        $query = Document::with(['rubric', 'author']);

        $allowedViewIds = $this->gate->allowedRubricIds($user, 'can_view');

        if ($allowedViewIds !== null) {
            if (empty($allowedViewIds)) {
                $query->whereRaw('1=0');
            } else {
                $query->whereIn('rubric_id', $allowedViewIds);
            }
        }

        if ($rid = $request->rubric_id) {
            $query->where('rubric_id', $rid);
        }

        if ($s = $request->search) {
            $query->where('title', 'ilike', "%{$s}%");
        }

        if ($did = $request->doc_id) {
            $query->where('id', $did);
        }

        if ($request->filled('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        if ($df = $request->date_from) {
            $query->whereDate('published_at', '>=', $df);
        }

        if ($dt = $request->date_to) {
            $query->whereDate('published_at', '<=', $dt);
        }

        $fieldAlias = trim($request->field_alias ?? '');
        $fieldOperator = $request->field_operator ?? '=';
        $fieldValue = trim($request->field_value ?? '');

        if ($fieldAlias !== '' && $fieldValue !== '') {
            $query->whereExists(function ($sub) use ($fieldAlias, $fieldOperator, $fieldValue) {
                $sub->select(DB::raw(1))
                    ->from('document_fields')
                    ->join('rubric_fields', 'rubric_fields.id', '=', 'document_fields.field_id')
                    ->whereColumn('document_fields.document_id', 'documents.id')
                    ->where('rubric_fields.alias', $fieldAlias);

                match ($fieldOperator) {
                    '!='       => $sub->where('document_fields.value', '!=', $fieldValue),
                    'like'     => $sub->where('document_fields.value', 'ilike', '%' . $fieldValue . '%'),
                    'not_like' => $sub->where('document_fields.value', 'not ilike', '%' . $fieldValue . '%'),
                    '>'        => $sub->where('document_fields.value', '>', $fieldValue),
                    '>='       => $sub->where('document_fields.value', '>=', $fieldValue),
                    '<'        => $sub->where('document_fields.value', '<', $fieldValue),
                    '<='       => $sub->where('document_fields.value', '<=', $fieldValue),
                    default    => $sub->where('document_fields.value', $fieldValue),
                };
            });
        }

        $sort = in_array($request->sort, self::SORTABLE) ? $request->sort : 'id';
        $dir = $request->dir === 'asc' ? 'asc' : 'desc';

        return $query->orderBy($sort, $dir);
    }

    public function allowedViewIds(Authenticatable $user): ?array
    {
        return $this->gate->allowedRubricIds($user, 'can_view');
    }

    public function rubricsForList(Authenticatable $user): Collection
    {
        $allowedViewIds = $this->gate->allowedRubricIds($user, 'can_view');
        $query = Rubric::orderBy('title');

        if ($allowedViewIds !== null) {
            $query->whereIn('id', $allowedViewIds);
        }

        return $query->get();
    }

    public function rubricFieldsForFilter(int $rubricId): Collection
    {
        $rubric = Rubric::find($rubricId);

        return $rubric ? $rubric->fields()->orderBy('position')->get() : collect();
    }

    public function findRubric(int $id): Rubric
    {
        return Rubric::findOrFail($id);
    }

    public function rubricExists(int $id): bool
    {
        return Rubric::where('id', $id)->exists();
    }

    public function allowedForBulk(array $ids, string $action, Authenticatable $user): Collection
    {
        return Document::whereIn('id', $ids)->get()->filter(fn ($d) => match ($action) {
            'delete' => $this->gate->canDelete($user, $d),
            default  => $this->gate->canEdit($user, $d),
        });
    }

    public function searchDocuments(Request $request, Authenticatable $user): array
    {
        $q = trim((string) $request->input('q', ''));
        $rubricId = (int) $request->input('rubric_id', 0);
        $rubricIds = array_values(array_filter(array_map('intval', (array) $request->input('rubric_ids', []))));
        $excludeId = (int) $request->input('exclude_id', 0);
        $excludeIds = array_values(array_filter(array_map('intval', (array) $request->input('exclude_ids', []))));
        $limit = min(50, max(1, (int) $request->input('limit', 15)));

        $query = Document::with('rubric')->orderByDesc('id');
        $allowedViewIds = $this->gate->allowedRubricIds($user, 'can_view');

        if ($allowedViewIds !== null) {
            if (empty($allowedViewIds)) {
                return [];
            }
            $query->whereIn('rubric_id', $allowedViewIds);
        }

        if (!empty($rubricIds)) {
            $query->whereIn('rubric_id', $rubricIds);
        } elseif ($rubricId > 0) {
            $query->where('rubric_id', $rubricId);
        }

        if (!empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        } elseif ($excludeId > 0) {
            $query->where('id', '!=', $excludeId);
        }

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('title', 'ilike', '%' . $q . '%')
                    ->orWhere('alias', 'ilike', '%' . $q . '%');

                if (ctype_digit($q)) {
                    $sub->orWhere('id', (int) $q);
                }
            });
        }

        return $query->limit($limit)->get(['id', 'title', 'alias', 'rubric_id', 'status'])->map(fn ($d) => [
            'id'           => $d->id,
            'title'        => $d->title,
            'alias'        => $d->alias,
            'rubric_title' => $d->rubric?->title ?? '—',
            'status'       => $d->statusLabel(),
            'status_class' => $d->statusClass(),
        ])->values()->all();
    }

    public function getFilterableFields(array $ids): Collection
    {
        return RubricField::whereIn('rubric_id', $ids)
            ->join('rubrics', 'rubrics.id', '=', 'rubric_fields.rubric_id')
            ->orderBy('rubrics.title')
            ->orderBy('rubric_fields.position')
            ->select('rubric_fields.id', 'rubric_fields.alias', 'rubric_fields.title', 'rubrics.title as rubric_title')
            ->get();
    }

    public function create(array $data, Rubric $rubric, Authenticatable $user, int $status): Document
    {
        $alias = trim($data['alias'] ?? '') !== '' ? $data['alias'] : null;
        $position = Document::where('rubric_id', $rubric->id)->max('position') + 10;

        return Document::create([
            'title'       => $data['title'],
            'alias'       => $alias,
            'rubric_id'   => $rubric->id,
            'status'      => $status,
            'author_id'   => $user->id,
            'meta_robots' => 'index,follow',
            'position'    => $position,
        ]);
    }

    public function prepareEditData(Document $document): array
    {
        $navigations = Navigation::with('rootItems.allChildren')->orderBy('title')->get();

        $navItemsGrouped = $navigations->map(function ($nav) {
            $flat = [];
            $walker = function ($items, int $depth) use (&$walker, &$flat): void {
                foreach ($items as $item) {
                    $flat[] = ['item' => $item, 'depth' => $depth];
                    if ($item->allChildren->isNotEmpty()) {
                        $walker($item->allChildren, $depth + 1);
                    }
                }
            };
            $walker($nav->rootItems, 0);

            return ['nav' => $nav, 'items' => $flat];
        })->filter(fn ($g) => count($g['items']) > 0)->values();

        $parentDocs = Document::where('id', '!=', $document->id)
            ->with('rubric')
            ->orderBy('title')
            ->limit(500)
            ->get();

        return compact('navItemsGrouped', 'parentDocs');
    }

    public function update(Document $document, array $data): void
    {
        $newAlias = trim($data['alias'] ?? '') !== '' ? $data['alias'] : null;
        $oldAlias = $document->alias;

        if ($oldAlias !== $newAlias) {
            $this->recordAliasChange($document, $oldAlias, $newAlias);
        }

        $data['alias'] = $newAlias;
        $this->applyData($document, $data);

        if ($document->nav_item_id) {
            $suffix = Setting::getValue('url_suffix', '');
            $docUrl = DocumentUrl::build($document->rubric?->alias, $document->alias, $suffix);
            NavigationItem::where('id', $document->nav_item_id)->update(['url' => $docUrl]);
        }

        $this->saveRevision($document);

        $rubricFields = $document->rubric?->fields ?? collect();

        foreach ($data['fields'] ?? [] as $fieldId => $value) {
            $rubricField = $rubricFields->find($fieldId);

            if (!$rubricField) {
                continue;
            }

            $instance = $rubricField->fieldInstance();
            $saved = $instance ? $instance->save($value) : $value;

            if (is_array($saved)) {
                $saved = json_encode($saved, JSON_UNESCAPED_UNICODE);
            }

            DocumentField::updateOrCreate(
                ['document_id' => $document->id, 'field_id' => $fieldId],
                ['value' => $saved],
            );
        }
    }

    public function copy(Document $document, Authenticatable $user): Document
    {
        $baseAlias = $document->alias ?? 'doc-' . $document->id;
        $newAlias = $baseAlias . '-copy';
        $i = 1;

        while (Document::where('alias', $newAlias)->where('rubric_id', $document->rubric_id)->exists()) {
            $newAlias = $baseAlias . '-copy-' . $i++;
        }

        return DB::transaction(function () use ($document, $newAlias, $user) {
            $copy = $document->replicate(['fields']);
            $copy->title = $document->title . ' (копия)';
            $copy->alias = $newAlias;
            $copy->status = Document::STATUS_DRAFT;
            $copy->views = 0;
            $copy->author_id = $user->id;
            $copy->save();

            foreach ($document->fields as $f) {
                DocumentField::create([
                    'document_id' => $copy->id,
                    'field_id'    => $f->field_id,
                    'value'       => $f->value,
                ]);
            }

            return $copy;
        });
    }

    public function normalizePositions(int $rubricId): int
    {
        $ids = Document::where('rubric_id', $rubricId)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id');

        foreach ($ids as $i => $id) {
            Document::where('id', $id)->update(['position' => ($i + 1) * 10]);
        }

        return $ids->count();
    }

    public function bulkDelete(array $ids): void
    {
        Document::whereIn('id', $ids)->delete();
    }

    public function bulkStatus(array $ids, int $status): void
    {
        Document::whereIn('id', $ids)->update(['status' => $status]);
    }

    public function listRevisions(Document $document): SupportCollection
    {
        $dtFormat = Setting::getValue('date_format', 'd.m.Y') . ' ' . Setting::getValue('time_format', 'H:i');

        return DocumentRevision::with('author')
            ->where('document_id', $document->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id'         => $r->id,
                'created_at' => $r->created_at->format($dtFormat),
                'author'     => $r->author?->name ?? '—',
            ]);
    }

    public function restoreRevision(Document $document, DocumentRevision $revision): void
    {
        $this->saveRevision($document);
        $this->applyData($document, $revision->snapshot);

        foreach ($revision->snapshot['fields'] ?? [] as $fieldId => $value) {
            DocumentField::updateOrCreate(
                ['document_id' => $document->id, 'field_id' => (int) $fieldId],
                ['value' => $value],
            );
        }
    }

    public function deleteAllRevisions(Document $document): int
    {
        return DocumentRevision::where('document_id', $document->id)->delete();
    }

    public function applyData(Document $document, array $data): void
    {
        $defaults = [
            'meta_title'            => null,
            'meta_description'      => null,
            'og_title'              => null,
            'og_description'        => null,
            'og_image'              => null,
            'meta_robots'           => 'index,follow',
            'sitemap_changefreq'    => null,
            'sitemap_priority'      => null,
            'published_at'          => null,
            'unpublished_at'        => null,
            'status'                => Document::STATUS_DRAFT,
            'position'              => 0,
            'nav_item_id'           => null,
            'breadcrumb_title'      => null,
            'parent_doc_id'         => null,
            'public_cache_disabled' => false,
            'public_cache_ttl'      => null,
        ];

        $fill = [];

        foreach (self::DOCUMENT_FIELDS as $field) {
            $fill[$field] = $data[$field] ?? $defaults[$field] ?? null;
        }

        if ((int) ($fill['status'] ?? 0) === Document::STATUS_ACTIVE
            && $document->status !== Document::STATUS_ACTIVE
            && empty($fill['published_at'])
        ) {
            $fill['published_at'] = now();
        }

        $document->update($fill);
    }

    public function saveRevision(Document $document): void
    {
        $document->loadMissing('fields');

        $fields = [];

        foreach ($document->fields as $f) {
            $fields[$f->field_id] = $f->value;
        }

        $snapshot = $document->only(self::DOCUMENT_FIELDS);
        $snapshot['published_at'] = $document->published_at?->toIso8601String();
        $snapshot['unpublished_at'] = $document->unpublished_at?->toIso8601String();
        $snapshot['fields'] = $fields;

        DocumentRevision::create([
            'document_id' => $document->id,
            'author_id'   => auth('admin')->id(),
            'snapshot'    => $snapshot,
        ]);
    }

    public function canEdit(Authenticatable $user, Document $document): bool
    {
        return $this->gate->canEdit($user, $document);
    }

    public function canDelete(Authenticatable $user, Document $document): bool
    {
        return $this->gate->canDelete($user, $document);
    }

    private function recordAliasChange(Document $document, ?string $oldAlias, ?string $newAlias): void
    {
        if ($newAlias !== null) {
            DB::table('document_alias_history')
                ->where('old_alias', $newAlias)
                ->where('document_id', $document->id)
                ->delete();
        }

        if ($oldAlias !== null && trim($oldAlias) !== '') {
            $rubric = $document->rubric;
            $oldRubricId = ($rubric && trim($rubric->alias ?? '') !== '') ? $rubric->id : null;

            DB::table('document_alias_history')->updateOrInsert(
                ['old_alias' => $oldAlias, 'old_rubric_id' => $oldRubricId],
                ['document_id' => $document->id, 'created_at' => now()],
            );
        }
    }
}
