<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDocsRequestRequest;
use App\Http\Requests\Admin\UpdateDocsRequestRequest;
use App\Models\DocsRequest;
use App\Models\Rubric;
use App\Models\RubricField;
use App\Services\Logger;

class DocsRequestController extends Controller
{
    public function index()
    {
        $requests = DocsRequest::orderBy('id')->get();
        $rubrics = Rubric::orderBy('title')->get();

        $authUser = auth('admin')->user();
        $canList = $authUser?->hasPermission('requests.list') ?? false;
        $canCreate = $authUser?->hasPermission('requests.create') ?? false;
        $canEdit = $authUser?->hasPermission('requests.edit') ?? false;
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
        $rubrics = Rubric::orderBy('title')->get();

        $rubricIds = $docsRequest->rubric_ids ?? [];
        $fields = !empty($rubricIds)
            ? RubricField::whereIn('rubric_id', $rubricIds)
                ->join('rubrics', 'rubrics.id', '=', 'rubric_fields.rubric_id')
                ->orderBy('rubrics.title')
                ->orderBy('rubric_fields.position')
                ->select('rubric_fields.*', 'rubrics.title as rubric_title')
                ->get()
            : collect();

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
        $newAlias = $docsRequest->alias . '-copy';
        $counter = 1;
        while (DocsRequest::where('alias', $newAlias)->exists()) {
            $newAlias = $docsRequest->alias . '-copy-' . $counter++;
        }

        $copy = $docsRequest->replicate();
        $copy->title = $docsRequest->title . ' (копия)';
        $copy->alias = $newAlias;
        $copy->save();

        Logger::adminAction("Скопировал запрос «{$docsRequest->title}»", 'create', 'request', $copy->id, $copy->title);

        $rubricNames = $copy->rubrics()->pluck('title')->join(', ') ?: '—';

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
