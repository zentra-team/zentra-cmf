<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRubricTemplateRequest;
use App\Models\Rubric;
use App\Services\Logger;
use App\Support\Permission;
use Illuminate\Http\Request;

class RubricTemplateController extends Controller
{
    public function edit(Rubric $rubric, Request $request)
    {
        $fields = $rubric->fields()->orderBy('position')->get();

        $tags = $this->buildTags($rubric);

        $user = $request->user('admin') ?? $request->user();
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

    private function buildTags(Rubric $rubric): array
    {
        $system = [
            '[title]', '[alias]', '[docid]',
            '[date]', '[date_modified]',
            '[maincontent]',
            '[doc:prev:url]', '[doc:prev:title]',
            '[doc:next:url]', '[doc:next:title]',
            '[if:doc:prev]...[/if:doc:prev]',
            '[if:doc:next]...[/if:doc:next]',
        ];

        $fieldTags = [];

        foreach ($rubric->fields as $field) {
            $fieldTags[] = [
                'tag'   => "[field:{$field->alias}]",
                'title' => $field->title,
            ];
            $fieldTags[] = [
                'tag'   => "[field:{$field->id}]",
                'title' => $field->title . ' (по ID)',
            ];
        }

        $navigation = [];

        try {
            $navItems = \Illuminate\Support\Facades\DB::table('navigations')
                ->select('alias', 'title')
                ->orderBy('title')
                ->get();

            foreach ($navItems as $item) {
                $navigation[] = ['tag' => "[nav:{$item->alias}]", 'title' => $item->title];
            }
        } catch (\Throwable) {
        }

        $blocks = [];

        try {
            $blockItems = \Illuminate\Support\Facades\DB::table('blocks')
                ->select('alias', 'title')
                ->orderBy('title')
                ->get();

            foreach ($blockItems as $item) {
                $blocks[] = ['tag' => "[block:{$item->alias}]", 'title' => $item->title];
            }
        } catch (\Throwable) {
        }

        return compact('system', 'fieldTags', 'navigation', 'blocks');
    }
}
