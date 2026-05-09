<?php

namespace App\Http\Controllers\Admin;

use App\Fields\RepeaterField;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRubricSubfieldRequest;
use App\Http\Requests\Admin\UpdateRubricSubfieldAliasRequest;
use App\Http\Requests\Admin\UpdateRubricSubfieldRequest;
use App\Models\Rubric;
use App\Models\RubricField;
use App\Services\Logger;
use App\Support\Permission;
use Illuminate\Http\Request;

class RubricSubfieldController extends Controller
{
    public function show(Rubric $rubric, RubricField $field, Request $request)
    {
        abort_if($field->rubric_id !== $rubric->id, 404);
        abort_if($field->type !== 'repeater', 404);

        $subfields = $this->subfields($field);
        $allTypes = $this->allowedTypes();

        $user = $request->user('admin') ?? $request->user();
        $canEdit = $user?->hasPermission(Permission::RUBRICS_EDIT) ?? false;
        $canDelete = $user?->hasPermission(Permission::RUBRICS_DELETE) ?? false;

        return view('admin.rubrics.subfields', compact(
            'rubric',
            'field',
            'subfields',
            'allTypes',
            'canEdit',
            'canDelete',
        ));
    }

    public function store(Rubric $rubric, RubricField $field, StoreRubricSubfieldRequest $request)
    {
        abort_if($field->rubric_id !== $rubric->id, 404);
        abort_if($field->type !== 'repeater', 404);

        $data = $request->validated();
        $subfields = $this->subfields($field);

        $alias = $this->generateAlias($data['title']);
        $base = $alias;
        $i = 2;
        while ($this->aliasExists($subfields, $alias)) {
            $alias = $base . '_' . $i++;
        }

        $subfields[] = [
            'alias'         => $alias,
            'label'         => $data['title'],
            'type'          => $data['type'],
            'default_value' => '',
            'description'   => '',
            'config'        => [],
        ];

        $this->saveSubfields($field, $subfields);
        Logger::adminAction(
            "Добавил подполе «{$data['title']}» в репитер «{$field->title}»",
            'create',
            'rubric_subfield',
            $field->id,
            $alias,
        );

        return response()->json([
            'ok'       => true,
            'subfield' => [
                'idx'   => count($subfields) - 1,
                'alias' => $alias,
                'label' => $data['title'],
                'type'  => $data['type'],
            ],
        ]);
    }

    public function update(Rubric $rubric, RubricField $field, int $idx, UpdateRubricSubfieldRequest $request)
    {
        abort_if($field->rubric_id !== $rubric->id, 404);
        abort_if($field->type !== 'repeater', 404);

        $subfields = $this->subfields($field);

        if (!isset($subfields[$idx])) {
            abort(404);
        }

        $data = $request->validated();

        if (array_key_exists('title', $data)) {
            $subfields[$idx]['label'] = $data['title'];
        }

        foreach (['label', 'type', 'default_value', 'description', 'config'] as $k) {
            if (array_key_exists($k, $data)) {
                $subfields[$idx][$k] = $data[$k];
            }
        }

        $this->saveSubfields($field, $subfields);

        return response()->json(['ok' => true]);
    }

    public function updateAlias(Rubric $rubric, RubricField $field, int $idx, UpdateRubricSubfieldAliasRequest $request)
    {
        abort_if($field->rubric_id !== $rubric->id, 404);
        abort_if($field->type !== 'repeater', 404);

        $subfields = $this->subfields($field);

        if (!isset($subfields[$idx])) {
            abort(404);
        }

        $newAlias = $request->validated()['alias'];

        foreach ($subfields as $i => $sf) {
            if ($i !== $idx && ($sf['alias'] ?? '') === $newAlias) {
                return response()->json([
                    'ok'     => false,
                    'errors' => ['alias' => ['Подполе с таким алиасом уже существует']],
                ], 422);
            }
        }

        $subfields[$idx]['alias'] = $newAlias;
        $this->saveSubfields($field, $subfields);

        return response()->json(['ok' => true, 'alias' => $newAlias]);
    }

    public function reorder(Rubric $rubric, RubricField $field, \App\Http\Requests\Admin\ReorderRubricSubfieldsRequest $request)
    {
        abort_if($field->rubric_id !== $rubric->id, 404);
        abort_if($field->type !== 'repeater', 404);

        $order = $request->validated()['order'];

        $subfields = $this->subfields($field);
        $reordered = [];

        foreach ($order as $i) {
            $i = (int) $i;

            if (isset($subfields[$i])) {
                $reordered[] = $subfields[$i];
            }
        }

        if (count($reordered) !== count($subfields)) {
            return response()->json(['ok' => false, 'message' => 'Order mismatch'], 422);
        }

        $this->saveSubfields($field, $reordered);

        return response()->json(['ok' => true]);
    }

    public function destroy(Rubric $rubric, RubricField $field, int $idx)
    {
        abort_if($field->rubric_id !== $rubric->id, 404);
        abort_if($field->type !== 'repeater', 404);

        $subfields = $this->subfields($field);

        if (!isset($subfields[$idx])) {
            abort(404);
        }

        $removed = $subfields[$idx];
        array_splice($subfields, $idx, 1);

        $this->saveSubfields($field, $subfields);
        Logger::adminAction(
            "Удалил подполе «{$removed['label']}» из репитера «{$field->title}»",
            'delete',
            'rubric_subfield',
            $field->id,
            $removed['alias'] ?? '',
        );

        return response()->json(['ok' => true]);
    }

    private function subfields(RubricField $field): array
    {
        $cfg = is_array($field->config) ? $field->config : [];

        return is_array($cfg['subfields'] ?? null) ? $cfg['subfields'] : [];
    }

    private function saveSubfields(RubricField $field, array $subfields): void
    {
        $cfg = is_array($field->config) ? $field->config : [];
        $cfg['subfields'] = array_values($subfields);
        $field->update(['config' => $cfg]);
    }

    private function aliasExists(array $subfields, string $alias): bool
    {
        foreach ($subfields as $sf) {
            if (($sf['alias'] ?? '') === $alias) {
                return true;
            }
        }

        return false;
    }

    private function generateAlias(string $title): string
    {
        $map = [
            'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo',
            'ж' => 'zh', 'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm',
            'н' => 'n', 'о' => 'o', 'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u',
            'ф' => 'f', 'х' => 'kh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 'щ' => 'shch',
            'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu', 'я' => 'ya',
        ];

        $str = mb_strtolower($title);
        $str = strtr($str, $map);
        $str = preg_replace('/[^a-z0-9]+/', '_', $str);
        $str = trim($str, '_');

        return $str ?: 'subfield';
    }

    private function allowedTypes(): array
    {
        $fm = app(\App\Services\FieldManager::class);
        $out = [];

        foreach (RepeaterField::ALLOWED_SUBTYPES as $t) {
            $instance = $fm->instance($t);

            if ($instance === null) {
                continue;
            }
            $out[$t] = [
                'name' => $instance->getName(),
                'icon' => $instance->getIcon(),
            ];
        }

        return $out;
    }
}
