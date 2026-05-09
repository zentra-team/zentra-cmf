<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDocsRequestRequest;
use App\Http\Requests\Admin\UpdateDocsRequestRequest;
use App\Models\DocsRequest;
use App\Services\DocsRequestService;
use App\Services\Logger;

class DocsRequestController extends Controller
{
    public function __construct(private readonly DocsRequestService $docsRequestService)
    {
    }

    public function index()
    {
        ['requests' => $requests, 'rubrics' => $rubrics] = $this->docsRequestService->indexData();

        /** @var \App\Models\User|null $authUser */
        $authUser  = auth('admin')->user();
        $canList   = $authUser?->hasPermission('requests.list') ?? false;
        $canCreate = $authUser?->hasPermission('requests.create') ?? false;
        $canEdit   = $authUser?->hasPermission('requests.edit') ?? false;
        $canDelete = $authUser?->hasPermission('requests.delete') ?? false;

        return view('admin.requests.index', compact(
            'requests',
            'rubrics',
            'canList',
            'canCreate',
            'canEdit',
            'canDelete',
        ));
    }

    public function store(StoreDocsRequestRequest $request)
    {
        $data = $request->validated();
        $rubricIds = !empty($data['rubric_id']) ? [(int) $data['rubric_id']] : null;

        $req = DocsRequest::create([
            'title'       => $data['title'],
            'alias'       => $data['alias'],
            'rubric_ids'  => $rubricIds,
            'description' => $data['description'] ?? null,
            'sort_order'  => 'desc',
            'conditions'  => [],
        ]);

        Logger::adminAction('Создал запрос', 'create', 'request', $req->id, $req->title);

        return redirect()->route('admin.requests.edit', $req)
            ->with('success', 'Запрос создан.');
    }

    public function edit(DocsRequest $docsRequest)
    {
        $rubrics = $this->docsRequestService->rubrics();
        $rubricIds = $docsRequest->rubric_ids ?? [];
        $fields = !empty($rubricIds)
            ? $this->docsRequestService->fieldsForRubrics($rubricIds)
            : collect();

        /** @var \App\Models\User|null $authUser */
        $authUser = auth('admin')->user();
        $canEdit = $authUser?->hasPermission('requests.edit') ?? false;
        $canDelete = $authUser?->hasPermission('requests.delete') ?? false;

        return view('admin.requests.edit', compact('docsRequest', 'rubrics', 'fields', 'canEdit', 'canDelete'));
    }

    public function update(UpdateDocsRequestRequest $request, DocsRequest $docsRequest)
    {
        $data = $request->validated();
        $rubricIds = !empty($data['rubric_ids'])
            ? array_map('intval', array_filter($data['rubric_ids']))
            : null;

        $docsRequest->update([
            'title'           => $data['title'],
            'alias'           => $data['alias'],
            'rubric_ids'      => $rubricIds ?: null,
            'description'     => $data['description'] ?? null,
            'sort_field'      => $data['sort_field'] ?? null,
            'sort_system'     => $data['sort_system'] ?? null,
            'sort_order'      => $data['sort_order'] ?? 'desc',
            'fetch_mode'      => $data['fetch_mode'] ?? 'global',
            'limit'           => $data['limit'] ?? null,
            'show_pagination' => $request->boolean('show_pagination'),
            'per_page'        => $data['per_page'] ?? null,
            'exclude_current' => $request->boolean('exclude_current'),
            'cache_time'      => $data['cache_time'] ?? null,
            'conditions'      => $data['conditions'] ?? [],
            'template_main'   => $data['template_main'] ?? null,
            'template_item'   => $data['template_item'] ?? null,
        ]);

        Logger::adminAction('Редактировал запрос', 'edit', 'request', $docsRequest->id, $docsRequest->title);

        return response()->json(['ok' => true, 'message' => 'Запрос сохранён']);
    }

    public function copy(DocsRequest $docsRequest)
    {
        ['copy' => $copy, 'rubricNames' => $rubricNames] = $this->docsRequestService->copy($docsRequest);

        Logger::adminAction("Скопировал запрос «{$docsRequest->title}»", 'create', 'request', $copy->id, $copy->title);

        return response()->json([
            'ok'      => true,
            'message' => 'Запрос скопирован',
            'id'      => $copy->id,
            'title'   => $copy->title,
            'alias'   => $copy->alias,
            'tag'     => $copy->tag(),
            'rubric'  => $rubricNames,
        ]);
    }

    public function destroy(DocsRequest $docsRequest)
    {
        [$id, $title] = [$docsRequest->id, $docsRequest->title];
        $docsRequest->delete();
        Logger::adminAction('Удалил запрос', 'delete', 'request', $id, $title);

        return response()->json(['ok' => true, 'message' => 'Запрос удалён']);
    }
}
