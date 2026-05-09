<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRubricTemplateRequest;
use App\Models\Rubric;
use App\Services\Logger;
use App\Services\RubricFieldService;
use App\Services\RubricService;
use App\Support\Permission;
use Illuminate\Http\Request;

class RubricTemplateController extends Controller
{
    public function __construct(
        private readonly RubricService $rubricService,
        private readonly RubricFieldService $rubricFieldService,
    ) {}

    public function edit(Rubric $rubric, Request $request)
    {
        $fields  = $this->rubricFieldService->fieldsOrdered($rubric);
        $tags    = $this->rubricService->buildTemplateTags($rubric);
        $user    = $request->user('admin') ?? $request->user();
        $canEdit = $user?->hasPermission(Permission::RUBRICS_EDIT) ?? false;

        return view('admin.rubrics.template', compact('rubric', 'fields', 'tags', 'canEdit'));
    }

    public function update(Rubric $rubric, UpdateRubricTemplateRequest $request)
    {
        $data = $request->validated();

        $rubric->update(['template' => $data['template'] ?? '']);

        Logger::adminAction('Обновил шаблон рубрики', 'edit', 'rubric', $rubric->id, $rubric->title);

        return response()->json(['ok' => true, 'message' => 'Шаблон сохранён']);
    }
}
