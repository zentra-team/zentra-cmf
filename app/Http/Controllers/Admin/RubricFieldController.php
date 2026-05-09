<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderRubricFieldRequest;
use App\Http\Requests\Admin\StoreRubricFieldRequest;
use App\Http\Requests\Admin\UpdateRubricFieldAliasRequest;
use App\Http\Requests\Admin\UpdateRubricFieldConfigRequest;
use App\Http\Requests\Admin\UpdateRubricFieldRequest;
use App\Models\Rubric;
use App\Models\RubricField;
use App\Services\FieldManager;
use App\Services\Logger;
use App\Services\RubricFieldService;
use App\Support\Permission;
use Illuminate\Http\Request;

class RubricFieldController extends Controller
{
    public function __construct(private readonly RubricFieldService $rubricFieldService)
    {
    }

    public function show(Rubric $rubric, Request $request)
    {
        $fields = $this->rubricFieldService->fieldsOrdered($rubric);
        $fieldTypes = app(FieldManager::class)->groups();
        $allRubrics = $this->rubricFieldService->allRubricsForSelect();

        $user = $request->user('admin') ?? $request->user();
        $canEdit = $user?->hasPermission(Permission::RUBRICS_EDIT) ?? false;
        $canDelete = $user?->hasPermission(Permission::RUBRICS_DELETE) ?? false;
        $canTemplate = $canEdit;
        $canPermissions = $user?->hasPermission(Permission::RUBRICS_PERMISSIONS) ?? false;

        return view('admin.rubrics.fields', compact(
            'rubric',
            'fields',
            'fieldTypes',
            'allRubrics',
            'canEdit',
            'canDelete',
            'canTemplate',
            'canPermissions',
        ));
    }

    public function toggleInApi(Rubric $rubric, RubricField $field, Request $request)
    {
        abort_if($field->rubric_id !== $rubric->id, 404);

        $user = $request->user('admin') ?? $request->user();

        if (!($user?->hasPermission(Permission::RUBRICS_EDIT) ?? false)) {
            abort(403);
        }

        $request->validate(['in_api' => ['required', 'boolean']]);
        $field->update(['in_api' => $request->boolean('in_api')]);

        Logger::adminAction(
            $field->in_api
                ? "Включил поле «{$field->title}» в API"
                : "Исключил поле «{$field->title}» из API",
            'edit',
            'rubric_field',
            $field->id,
            $field->title,
        );

        return response()->json(['ok' => true, 'in_api' => $field->in_api]);
    }

    public function store(Rubric $rubric, StoreRubricFieldRequest $request)
    {
        $data = $request->validated();
        $alias = $this->rubricFieldService->generateUniqueAlias($rubric, $data['title']);
        $pos = $this->rubricFieldService->nextPosition($rubric);

        $field = $rubric->fields()->create([
            'alias'    => $alias,
            'title'    => $data['title'],
            'type'     => $data['type'],
            'position' => $pos,
        ]);

        Logger::adminAction("Добавил поле «{$field->title}» в рубрику «{$rubric->title}»", 'create', 'rubric_field', $field->id, $field->title);

        return response()->json([
            'ok'    => true,
            'field' => [
                'id'        => $field->id,
                'alias'     => $field->alias,
                'title'     => $field->title,
                'type'      => $field->type,
                'type_name' => $field->typeName(),
                'position'  => $field->position,
            ],
        ]);
    }

    public function update(Rubric $rubric, RubricField $field, UpdateRubricFieldRequest $request)
    {
        abort_if($field->rubric_id !== $rubric->id, 404);

        $field->update($request->validated());

        return response()->json([
            'ok'        => true,
            'type_name' => $field->fresh()->typeName(),
        ]);
    }

    public function updateAlias(Rubric $rubric, RubricField $field, UpdateRubricFieldAliasRequest $request)
    {
        abort_if($field->rubric_id !== $rubric->id, 404);

        $field->update(['alias' => $request->validated()['alias']]);

        return response()->json(['ok' => true, 'alias' => $field->alias]);
    }

    public function updateConfig(Rubric $rubric, RubricField $field, UpdateRubricFieldConfigRequest $request)
    {
        abort_if($field->rubric_id !== $rubric->id, 404);

        $data = $request->validated();

        if (array_key_exists('config', $data)) {
            $existing = is_array($field->config) ? $field->config : [];
            $data['config'] = array_merge($existing, $data['config'] ?? []);
        }

        $field->update($data);

        return response()->json(['ok' => true]);
    }

    public function reorder(Rubric $rubric, ReorderRubricFieldRequest $request)
    {
        $this->rubricFieldService->reorder($rubric, $request->validated()['order']);

        return response()->json(['ok' => true]);
    }

    public function destroy(Rubric $rubric, RubricField $field)
    {
        abort_if($field->rubric_id !== $rubric->id, 404);

        $docsCount = $rubric->docsCount();
        [$fid, $ftitle] = [$field->id, $field->title];
        $field->delete();

        Logger::adminAction("Удалил поле «{$ftitle}» из рубрики «{$rubric->title}»", 'delete', 'rubric_field', $fid, $ftitle);

        return response()->json([
            'ok'         => true,
            'docs_count' => $docsCount,
        ]);
    }
}
