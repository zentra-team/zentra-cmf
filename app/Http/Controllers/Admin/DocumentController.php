<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustDocumentPositionRequest;
use App\Http\Requests\Admin\BulkDocumentActionRequest;
use App\Http\Requests\Admin\StoreDocumentRequest;
use App\Http\Requests\Admin\UpdateDocumentRequest;
use App\Models\Document;
use App\Models\DocumentField;
use App\Models\DocumentRevision;
use App\Models\Navigation;
use App\Models\Rubric;
use App\Models\RubricField;
use App\Models\Setting;
use App\Services\LayoutRenderer;
use App\Services\Logger;
use App\Services\RubricAccessGate;
use App\Support\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DocumentController extends Controller
{
    private const SORTABLE = ['id', 'position', 'title', 'alias', 'published_at', 'updated_at'];

    public function __construct(
        private readonly RubricAccessGate $gate,
        private readonly LayoutRenderer $layoutRenderer,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user('admin') ?? $request->user();

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
                $sub->select(\Illuminate\Support\Facades\DB::raw(1))
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
        $perPage = in_array((int) $request->per_page, [10, 25, 50, 100]) ? (int) $request->per_page : 25;

        $documents = $query->orderBy($sort, $dir)->paginate($perPage)->withQueryString();

        $rubricsQuery = Rubric::orderBy('title');

        if ($allowedViewIds !== null) {
            $rubricsQuery->whereIn('id', $allowedViewIds);
        }

        $rubrics = $rubricsQuery->get();

        $canCreateGlobal = $user->hasPermission(Permission::DOCUMENTS_CREATE);
        $canEditGlobal = $user->hasPermission(Permission::DOCUMENTS_EDIT);
        $canDeleteGlobal = $user->hasPermission(Permission::DOCUMENTS_DELETE);

        $documents->getCollection()->each(function ($doc) use ($user, $canEditGlobal, $canDeleteGlobal, $canCreateGlobal) {
            $doc->perm_edit = $canEditGlobal && $this->gate->canEdit($user, $doc);
            $doc->perm_delete = $canDeleteGlobal && $this->gate->canDelete($user, $doc);
            $doc->perm_copy = $canCreateGlobal && $doc->rubric && $this->gate->canCreateAny($user, $doc->rubric);
        });

        $rubricsForCreate = $canCreateGlobal
            ? $rubrics->filter(fn ($r) => $this->gate->canCreateAny($user, $r))->values()
            : collect();

        $rubricsPublishMap = $rubricsForCreate->mapWithKeys(
            fn ($r) => [$r->id => $this->gate->canCreate($user, $r)],
        )->all();

        $rubricFields = $request->rubric_id
            ? Rubric::find($request->rubric_id)?->fields()->orderBy('position')->get() ?? collect()
            : collect();

        return view('admin.documents.index', compact(
            'documents',
            'rubrics',
            'rubricFields',
            'rubricsForCreate',
            'rubricsPublishMap',
            'canEditGlobal',
            'canDeleteGlobal',
            'canCreateGlobal',
        ));
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->input('q', ''));
        $rubricId = (int) $request->input('rubric_id', 0);
        $rubricIds = array_values(array_filter(array_map('intval', (array) $request->input('rubric_ids', []))));
        $excludeId = (int) $request->input('exclude_id', 0);
        $excludeIds = array_values(array_filter(array_map('intval', (array) $request->input('exclude_ids', []))));
        $limit = min(50, max(1, (int) $request->input('limit', 15)));

        $query = Document::with('rubric')->orderByDesc('id');

        $user = $request->user('admin') ?? $request->user();
        $allowedViewIds = $this->gate->allowedRubricIds($user, 'can_view');

        if ($allowedViewIds !== null) {
            if (empty($allowedViewIds)) {
                return response()->json(['results' => []]);
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

        $docs = $query->limit($limit)->get(['id', 'title', 'alias', 'rubric_id', 'status']);

        return response()->json([
            'results' => $docs->map(fn ($d) => [
                'id'           => $d->id,
                'title'        => $d->title,
                'alias'        => $d->alias,
                'rubric_title' => $d->rubric?->title ?? '—',
                'status'       => $d->statusLabel(),
                'status_class' => $d->statusClass(),
            ])->values(),
        ]);
    }

    public function rubricFields(Request $request)
    {
        $ids = $request->rubric_ids
            ? array_filter(array_map('intval', (array) $request->rubric_ids))
            : ($request->rubric_id ? [(int) $request->rubric_id] : []);

        if (empty($ids)) {
            return response()->json([]);
        }

        $user = $request->user('admin') ?? $request->user();
        $allowedViewIds = $this->gate->allowedRubricIds($user, 'can_view');

        if ($allowedViewIds !== null) {
            $ids = array_values(array_intersect($ids, $allowedViewIds));

            if (empty($ids)) {
                return response()->json([]);
            }
        }

        $fields = RubricField::whereIn('rubric_id', $ids)
            ->join('rubrics', 'rubrics.id', '=', 'rubric_fields.rubric_id')
            ->orderBy('rubrics.title')
            ->orderBy('rubric_fields.position')
            ->select('rubric_fields.id', 'rubric_fields.alias', 'rubric_fields.title', 'rubrics.title as rubric_title')
            ->get();

        return response()->json($fields);
    }

    public function store(StoreDocumentRequest $request)
    {
        $data = $request->validated();
        $alias = trim($data['alias'] ?? '') !== '' ? $data['alias'] : null;

        $rubric = Rubric::findOrFail($data['rubric_id']);
        $user = $request->user('admin') ?? $request->user();

        $canCreate = $this->gate->canCreate($user, $rubric);
        $canCreateModerated = $this->gate->canCreateModerated($user, $rubric);

        if (!$canCreate && !$canCreateModerated) {
            abort(403, 'У вас нет прав на создание документов в этой рубрике.');
        }

        $requestedStatus = (int) ($data['status'] ?? Document::STATUS_DRAFT);

        if (!$canCreate && $canCreateModerated && $requestedStatus === Document::STATUS_ACTIVE) {
            $requestedStatus = Document::STATUS_MODERATION;
        }

        $doc = Document::create([
            'title'       => $data['title'],
            'alias'       => $alias,
            'rubric_id'   => $rubric->id,
            'status'      => $requestedStatus,
            'author_id'   => $user->id,
            'meta_robots' => 'index,follow',
        ]);

        Logger::adminAction('Создал документ', 'create', 'document', $doc->id, $doc->title);

        return redirect()->route('admin.documents.edit', $doc)
            ->with('success', 'Документ создан.');
    }

    public function edit(Request $request, Document $document)
    {
        $document->load(['rubric.fields', 'fields']);

        $user = $request->user('admin') ?? $request->user();

        if ($document->rubric && !$this->gate->canView($user, $document->rubric)) {
            abort(403, 'Нет доступа к документам этой рубрики.');
        }

        $canEditGlobal = $user->hasPermission(Permission::DOCUMENTS_EDIT);

        $canEdit = $canEditGlobal && $this->gate->canEdit($user, $document);
        $canDelete = $user->hasPermission(Permission::DOCUMENTS_DELETE) && $this->gate->canDelete($user, $document);
        $canRevisions = $document->rubric ? $this->gate->canRevisions($user, $document->rubric) : false;

        $editAllRubrics = $this->gate->rubricsWithEditAll($user);
        $canPublish = $canEditGlobal && ($editAllRubrics === null
            || ($document->rubric && in_array((int) $document->rubric_id, $editAllRubrics, true)));

        $fieldValues = $document->fields->keyBy('field_id');
        $rubricFields = $document->rubric?->fields ?? collect();

        $navigations = Navigation::with('items')->orderBy('title')->get();
        $parentDocs = Document::where('id', '!=', $document->id)
            ->with('rubric')
            ->orderBy('title')
            ->limit(500)
            ->get();

        return view('admin.documents.edit', compact(
            'document',
            'fieldValues',
            'rubricFields',
            'navigations',
            'parentDocs',
            'canEdit',
            'canDelete',
            'canRevisions',
            'canPublish',
        ));
    }

    public function preview(Request $request, Document $document)
    {
        $user = $request->user('admin') ?? $request->user();

        if ($document->rubric && !$this->gate->canView($user, $document->rubric)) {
            abort(403, 'Нет доступа к документам этой рубрики.');
        }

        $document->loadMissing('rubric');

        return $this->layoutRenderer->render($document, $request);
    }

    public function update(UpdateDocumentRequest $request, Document $document)
    {
        $user = $request->user('admin') ?? $request->user();

        if (!$this->gate->canEdit($user, $document)) {
            abort(403, 'У вас нет прав на редактирование этого документа.');
        }

        $data = $request->validated();
        $newAlias = trim($data['alias'] ?? '') !== '' ? $data['alias'] : null;

        $document->loadMissing('rubric');

        if ($document->rubric) {
            $isPublisher = $this->gate->rubricsWithEditAll($user);
            $canPublishHere = $isPublisher === null
                || in_array((int) $document->rubric_id, $isPublisher, true);

            if (!$canPublishHere && isset($data['status']) && (int) $data['status'] === Document::STATUS_ACTIVE) {
                $data['status'] = Document::STATUS_MODERATION;
            }
        }

        $oldAlias = $document->alias;

        if ($oldAlias !== $newAlias) {
            $this->recordDocumentAliasChange($document, $oldAlias, $newAlias);
        }

        $data['alias'] = $newAlias;
        $this->applyDocumentData($document, $data);

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

        Logger::adminAction('Редактировал документ', 'edit', 'document', $document->id, $document->title);

        return response()->json(['ok' => true, 'message' => 'Документ сохранён']);
    }

    public function copy(Request $request, Document $document)
    {
        $user = $request->user('admin') ?? $request->user();
        $document->load('fields', 'rubric');

        if (!$document->rubric || !$this->gate->canCreateAny($user, $document->rubric)) {
            abort(403, 'У вас нет прав на создание документов в этой рубрике.');
        }

        $baseAlias = $document->alias ?? 'doc-' . $document->id;
        $newAlias = $baseAlias . '-copy';
        $i = 1;
        while (Document::where('alias', $newAlias)->where('rubric_id', $document->rubric_id)->exists()) {
            $newAlias = $baseAlias . '-copy-' . $i++;
        }

        $copy = DB::transaction(function () use ($document, $newAlias, $user) {
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

        Logger::adminAction("Скопировал документ «{$document->title}»", 'create', 'document', $copy->id, $copy->title);

        return response()->json([
            'ok'           => true,
            'message'      => 'Документ скопирован',
            'id'           => $copy->id,
            'title'        => $copy->title,
            'alias'        => $copy->alias,
            'rubric'       => $copy->rubric?->title ?? '—',
            'rubric_alias' => $copy->rubric?->alias ?? '',
            'status'       => $copy->statusLabel(),
            'status_class' => $copy->statusClass(),
        ]);
    }

    public function destroy(Request $request, Document $document)
    {
        $user = $request->user('admin') ?? $request->user();

        if (!$this->gate->canDelete($user, $document)) {
            abort(403, 'У вас нет прав на удаление этого документа.');
        }

        [$id, $title] = [$document->id, $document->title];
        $document->delete();
        Logger::adminAction('Удалил документ', 'delete', 'document', $id, $title);

        return response()->json(['ok' => true, 'message' => 'Документ удалён']);
    }

    public function adjustPosition(AdjustDocumentPositionRequest $request, Document $document)
    {
        $user = $request->user('admin') ?? $request->user();

        if (!$this->gate->canEdit($user, $document)) {
            abort(403, 'У вас нет прав на изменение порядка в этой рубрике.');
        }

        if ($request->validated()['dir'] === 'up') {
            $document->decrement('position');
        } else {
            $document->increment('position');
        }

        return response()->json(['ok' => true, 'position' => $document->fresh()->position]);
    }

    public function bulkAction(BulkDocumentActionRequest $request)
    {
        $data = $request->validated();
        $ids = array_map('intval', $data['ids']);
        $action = $data['action'];

        $user = $request->user('admin') ?? $request->user();

        $docs = Document::whereIn('id', $ids)->get();

        $allowed = $docs->filter(fn ($d) => match ($action) {
            'delete' => $this->gate->canDelete($user, $d),
            default  => $this->gate->canEdit($user, $d),
        });

        if ($allowed->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'Нет прав на выбранные документы']);
        }

        $allowedIds = $allowed->pluck('id')->all();
        $skipped = count($ids) - count($allowedIds);

        $result = match ($action) {
            'delete'   => $this->bulkDelete($allowedIds),
            'activate' => $this->bulkStatus($allowedIds, Document::STATUS_ACTIVE, 'Документы опубликованы'),
            'draft'    => $this->bulkStatus($allowedIds, Document::STATUS_DRAFT, 'Переведены в черновики'),
            default    => response()->json(['ok' => false, 'message' => 'Неизвестное действие']),
        };

        if ($skipped > 0 && ($result instanceof \Illuminate\Http\JsonResponse)) {
            $data = $result->getData(true);

            if (!empty($data['ok'])) {
                $data['message'] .= " (пропущено без прав: {$skipped})";

                return response()->json($data);
            }
        }

        return $result;
    }

    private function bulkDelete(array $ids)
    {
        Document::whereIn('id', $ids)->delete();
        Logger::adminAction('Массовое удаление документов (' . count($ids) . ' шт.)', 'delete', 'document');

        return response()->json(['ok' => true, 'message' => 'Документы удалены']);
    }

    private function bulkStatus(array $ids, int $status, string $message)
    {
        Document::whereIn('id', $ids)->update(['status' => $status]);
        $action = $status === Document::STATUS_ACTIVE ? 'опубликовал' : 'перевёл в черновики';
        Logger::adminAction("Массово {$action} документы (" . count($ids) . ' шт.)', 'edit', 'document');

        return response()->json(['ok' => true, 'message' => $message]);
    }

    public function revisions(Request $request, Document $document)
    {
        $this->assertRevisionsAccess($request, $document);

        $dtFormat = Setting::getValue('date_format', 'd.m.Y') . ' ' . Setting::getValue('time_format', 'H:i');

        $revisions = DocumentRevision::with('author')
            ->where('document_id', $document->id)
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($r) => [
                'id'         => $r->id,
                'created_at' => $r->created_at->format($dtFormat),
                'author'     => $r->author?->name ?? '—',
            ]);

        return response()->json(['ok' => true, 'revisions' => $revisions]);
    }

    public function revisionView(Request $request, Document $document, DocumentRevision $revision)
    {
        abort_if($revision->document_id !== $document->id, 404);
        $this->assertRevisionsAccess($request, $document);

        return response()->json(['ok' => true, 'snapshot' => $revision->snapshot]);
    }

    public function revisionRestore(Request $request, Document $document, DocumentRevision $revision)
    {
        abort_if($revision->document_id !== $document->id, 404);
        $this->assertRevisionsAccess($request, $document);

        $user = $request->user('admin') ?? $request->user();

        if (!$this->gate->canEdit($user, $document)) {
            abort(403, 'У вас нет прав на восстановление этой ревизии.');
        }

        $this->saveRevision($document);

        $this->applyDocumentData($document, $revision->snapshot);

        foreach ($revision->snapshot['fields'] ?? [] as $fieldId => $value) {
            DocumentField::updateOrCreate(
                ['document_id' => $document->id, 'field_id' => (int) $fieldId],
                ['value' => $value],
            );
        }

        Logger::adminAction('Восстановил ревизию документа', 'edit', 'document', $document->id, $document->title);

        return response()->json(['ok' => true, 'message' => 'Ревизия восстановлена']);
    }

    public function revisionDelete(Request $request, Document $document, DocumentRevision $revision)
    {
        abort_if($revision->document_id !== $document->id, 404);
        $this->assertRevisionsAccess($request, $document);

        $revision->delete();

        return response()->json(['ok' => true, 'message' => 'Ревизия удалена']);
    }

    public function revisionDeleteAll(Request $request, Document $document)
    {
        $this->assertRevisionsAccess($request, $document);

        $count = DocumentRevision::where('document_id', $document->id)->delete();
        Logger::adminAction("Удалил все ревизии документа ({$count})", 'delete', 'document', $document->id, $document->title);

        return response()->json(['ok' => true, 'message' => 'Все ревизии удалены']);
    }

    private function assertRevisionsAccess(Request $request, Document $document): void
    {
        $user = $request->user('admin') ?? $request->user();
        $document->loadMissing('rubric');

        if (!$document->rubric || !$this->gate->canRevisions($user, $document->rubric)) {
            abort(403, 'У вас нет прав на работу с ревизиями этой рубрики.');
        }
    }

    private function recordDocumentAliasChange(Document $document, ?string $oldAlias, ?string $newAlias): void
    {
        $rubricId = $document->rubric_id;

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

    private const DOCUMENT_FIELDS = [
        'title', 'alias', 'meta_title', 'meta_keywords', 'meta_description',
        'meta_robots', 'sitemap_changefreq', 'sitemap_priority', 'published_at',
        'unpublished_at', 'status', 'position', 'nav_item_id', 'breadcrumb_title',
        'parent_doc_id', 'public_cache_disabled', 'public_cache_ttl',
    ];

    private function applyDocumentData(Document $document, array $data): void
    {
        $defaults = [
            'meta_title'            => null,
            'meta_keywords'         => null,
            'meta_description'      => null,
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

        $document->update($fill);
    }

    private function saveRevision(Document $document): void
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
}
