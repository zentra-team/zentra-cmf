<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdjustDocumentPositionRequest;
use App\Http\Requests\Admin\BulkDocumentActionRequest;
use App\Http\Requests\Admin\StoreDocumentRequest;
use App\Http\Requests\Admin\UpdateDocumentRequest;
use App\Models\Document;
use App\Models\DocumentRevision;
use App\Services\DocumentService;
use App\Services\LayoutRenderer;
use App\Services\Logger;
use App\Services\RubricAccessGate;
use App\Support\Permission;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function __construct(
        private readonly RubricAccessGate $gate,
        private readonly LayoutRenderer $layoutRenderer,
        private readonly DocumentService $documentService,
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user('admin') ?? $request->user();
        $perPage = in_array((int) $request->per_page, [10, 25, 50, 100]) ? (int) $request->per_page : 25;

        $documents = $this->documentService->buildListQuery($request, $user)
            ->paginate($perPage)
            ->withQueryString();

        $rubrics = $this->documentService->rubricsForList($user);

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
            ? $this->documentService->rubricFieldsForFilter((int) $request->rubric_id)
            : collect();

        session(['documents.list_url' => $request->fullUrl()]);

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
        $user = $request->user('admin') ?? $request->user();
        $results = $this->documentService->searchDocuments($request, $user);

        if ($results === []) {
            return response()->json(['results' => []]);
        }

        return response()->json(['results' => $results]);
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
        $allowedViewIds = $this->documentService->allowedViewIds($user);

        if ($allowedViewIds !== null) {
            $ids = array_values(array_intersect($ids, $allowedViewIds));

            if (empty($ids)) {
                return response()->json([]);
            }
        }

        return response()->json($this->documentService->getFilterableFields($ids));
    }

    public function store(StoreDocumentRequest $request)
    {
        $data = $request->validated();
        $rubric = $this->documentService->findRubric($data['rubric_id']);
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

        $doc = $this->documentService->create($data, $rubric, $user, $requestedStatus);

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
        $backUrl = session('documents.list_url', route('admin.documents.index'));

        ['navItemsGrouped' => $navItemsGrouped, 'parentDocs' => $parentDocs]
            = $this->documentService->prepareEditData($document);

        return view('admin.documents.edit', compact(
            'document',
            'fieldValues',
            'rubricFields',
            'navItemsGrouped',
            'parentDocs',
            'canEdit',
            'canDelete',
            'canRevisions',
            'canPublish',
            'backUrl',
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
        $document->loadMissing('rubric');

        if ($document->rubric) {
            $isPublisher = $this->gate->rubricsWithEditAll($user);
            $canPublishHere = $isPublisher === null
                || in_array((int) $document->rubric_id, $isPublisher, true);

            if (!$canPublishHere && isset($data['status']) && (int) $data['status'] === Document::STATUS_ACTIVE) {
                $data['status'] = Document::STATUS_MODERATION;
            }
        }

        $this->documentService->update($document, $data);

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

        $copy = $this->documentService->copy($document, $user);

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

        $document->update(['position' => $request->validated()['position']]);

        return response()->json(['ok' => true, 'position' => $document->position]);
    }

    public function normalizePositions(Request $request)
    {
        $rubricId = (int) $request->input('rubric_id');

        if (!$rubricId) {
            return response()->json(['ok' => false, 'message' => 'Укажите рубрику'], 422);
        }

        if (!$this->documentService->rubricExists($rubricId)) {
            return response()->json(['ok' => false, 'message' => 'Рубрика не найдена'], 422);
        }

        $user = $request->user('admin') ?? $request->user();
        $editableRubrics = $this->gate->rubricsWithEditAll($user);

        if ($editableRubrics !== null && !in_array($rubricId, $editableRubrics, true)) {
            abort(403, 'У вас нет прав на изменение порядка в этой рубрике.');
        }

        $count = $this->documentService->normalizePositions($rubricId);

        Logger::adminAction(
            'Нормализовал позиции документов',
            'edit',
            'rubric',
            $rubricId,
            "Рубрика #{$rubricId} ({$count} документов)",
        );

        return response()->json(['ok' => true, 'message' => "Позиции нормализованы: {$count} документов"]);
    }

    public function bulkAction(BulkDocumentActionRequest $request)
    {
        $data = $request->validated();
        $ids = array_map('intval', $data['ids']);
        $action = $data['action'];
        $user = $request->user('admin') ?? $request->user();

        $allowed = $this->documentService->allowedForBulk($ids, $action, $user);

        if ($allowed->isEmpty()) {
            return response()->json(['ok' => false, 'message' => 'Нет прав на выбранные документы']);
        }

        $allowedIds = $allowed->pluck('id')->all();
        $skipped = count($ids) - count($allowedIds);

        [$ok, $message] = match ($action) {
            'delete'   => $this->doBulkDelete($allowedIds),
            'activate' => $this->doBulkStatus($allowedIds, Document::STATUS_ACTIVE, 'Документы опубликованы'),
            'draft'    => $this->doBulkStatus($allowedIds, Document::STATUS_DRAFT, 'Переведены в черновики'),
            default    => [false, 'Неизвестное действие'],
        };

        if (!$ok) {
            return response()->json(['ok' => false, 'message' => $message]);
        }

        if ($skipped > 0) {
            $message .= " (пропущено без прав: {$skipped})";
        }

        return response()->json(['ok' => true, 'message' => $message]);
    }

    public function revisions(Request $request, Document $document)
    {
        $this->assertRevisionsAccess($request, $document);

        return response()->json([
            'ok'        => true,
            'revisions' => $this->documentService->listRevisions($document),
        ]);
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

        $this->documentService->restoreRevision($document, $revision);

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

        $count = $this->documentService->deleteAllRevisions($document);

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

    private function doBulkDelete(array $ids): array
    {
        $this->documentService->bulkDelete($ids);
        Logger::adminAction('Массовое удаление документов (' . count($ids) . ' шт.)', 'delete', 'document');

        return [true, 'Документы удалены'];
    }

    private function doBulkStatus(array $ids, int $status, string $message): array
    {
        $this->documentService->bulkStatus($ids, $status);
        $action = $status === Document::STATUS_ACTIVE ? 'опубликовал' : 'перевёл в черновики';
        Logger::adminAction("Массово {$action} документы (" . count($ids) . ' шт.)', 'edit', 'document');

        return [true, $message];
    }
}
