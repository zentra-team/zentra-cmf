<?php

namespace App\Services;

use App\Models\Layout;
use App\Models\Rubric;
use App\Models\RubricPermission;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RubricService
{
    public function list(): array
    {
        return [
            'rubrics' => Rubric::with('layout')->withCount('fields')->orderBy('position')->orderBy('id')->get(),
            'layouts' => Layout::orderBy('title')->get(['id', 'title']),
        ];
    }

    public function hasLayouts(): bool
    {
        return Layout::count() > 0;
    }

    public function create(array $data): Rubric
    {
        $maxPos = Rubric::max('position') ?? 0;
        $alias  = trim($data['alias'] ?? '') !== '' ? $data['alias'] : null;

        return DB::transaction(function () use ($data, $alias, $maxPos) {
            $rubric = Rubric::create([
                'title'     => $data['title'],
                'alias'     => $alias,
                'layout_id' => $data['layout_id'],
                'color'     => $data['color'] ?? null,
                'position'  => $maxPos + 1,
            ]);

            foreach (UserGroup::where('is_system', false)->pluck('id') as $groupId) {
                RubricPermission::create([
                    'rubric_id'            => $rubric->id,
                    'group_id'             => $groupId,
                    'can_view'             => false,
                    'can_all'              => false,
                    'can_create_moderated' => false,
                    'can_create'           => false,
                    'can_edit_own'         => false,
                    'can_edit_all'         => false,
                    'can_revisions'        => false,
                ]);
            }

            return $rubric;
        });
    }

    public function saveAll(array $rows): void
    {
        $ids = array_column($rows, 'id');

        $rubrics = Rubric::whereIn('id', $ids)->get()->keyBy('id');

        $newTitles  = array_column($rows, 'title');
        $newAliases = array_values(array_filter(array_map(
            fn($r) => trim($r['alias'] ?? '') !== '' ? $r['alias'] : null,
            $rows,
        )));

        $takenTitles = array_flip(
            Rubric::whereIn('title', $newTitles)->whereNotIn('id', $ids)->pluck('title')->all()
        );
        $takenAliases = !empty($newAliases)
            ? array_flip(Rubric::whereIn('alias', $newAliases)->whereNotIn('id', $ids)->pluck('alias')->all())
            : [];

        foreach ($rows as $row) {
            if (isset($takenTitles[$row['title']])) {
                throw new \RuntimeException("Название «{$row['title']}» уже занято другой рубрикой.");
            }

            $newAlias = trim($row['alias'] ?? '') !== '' ? $row['alias'] : null;
            if ($newAlias !== null && isset($takenAliases[$newAlias])) {
                throw new \RuntimeException("Префикс «{$newAlias}» уже используется другой рубрикой.");
            }
        }

        DB::transaction(function () use ($rows, $rubrics) {
            foreach ($rows as $row) {
                $rubric = $rubrics->get($row['id']);
                if (!$rubric) {
                    continue;
                }

                $newAlias = trim($row['alias'] ?? '') !== '' ? $row['alias'] : null;
                $oldAlias = $rubric->alias;

                if ($oldAlias !== $newAlias) {
                    $this->recordAliasChange($rubric->id, $oldAlias, $newAlias);
                }

                $rubric->update([
                    'title'     => $row['title'],
                    'alias'     => $newAlias,
                    'color'     => $row['color'] ?? null,
                    'layout_id' => $row['layout_id'],
                ]);
            }
        });
    }

    public function updateOrder(array $order): void
    {
        $rubrics = Rubric::whereIn('id', array_values($order))->get()->keyBy('id');

        DB::transaction(function () use ($order, $rubrics) {
            foreach ($order as $pos => $id) {
                $rubric = $rubrics->get($id);

                if ($rubric === null) {
                    continue;
                }

                $rubric->position = $pos;
                $rubric->save();
            }
        });
    }

    public function copy(Rubric $rubric, array $data): Rubric
    {
        $maxPos    = Rubric::max('position') ?? 0;
        $copyAlias = trim($data['alias'] ?? '') !== '' ? $data['alias'] : null;

        return DB::transaction(function () use ($rubric, $data, $copyAlias, $maxPos) {
            $copy = Rubric::create([
                'title'       => $data['title'],
                'alias'       => $copyAlias,
                'layout_id'   => $rubric->layout_id,
                'template'    => $rubric->template,
                'description' => $rubric->description,
                'color'       => $rubric->color,
                'position'    => $maxPos + 1,
            ]);

            foreach ($rubric->fields as $field) {
                $copy->fields()->create([
                    'alias'         => $field->alias,
                    'title'         => $field->title,
                    'type'          => $field->type,
                    'position'      => $field->position,
                    'default_value' => $field->default_value,
                    'description'   => $field->description,
                    'config'        => $field->config,
                ]);
            }

            $systemGroupIds = UserGroup::where('is_system', true)->pluck('id')->all();

            foreach ($rubric->permissions as $perm) {
                if (in_array($perm->group_id, $systemGroupIds, true)) {
                    continue;
                }

                RubricPermission::create([
                    'rubric_id'            => $copy->id,
                    'group_id'             => $perm->group_id,
                    'can_view'             => $perm->can_view,
                    'can_all'              => $perm->can_all,
                    'can_create_moderated' => $perm->can_create_moderated,
                    'can_create'           => $perm->can_create,
                    'can_edit_own'         => $perm->can_edit_own,
                    'can_edit_all'         => $perm->can_edit_all,
                    'can_revisions'        => $perm->can_revisions,
                ]);
            }

            return $copy;
        });
    }

    public function nonSystemGroups(): Collection
    {
        return UserGroup::where('is_system', false)->orderBy('name')->get();
    }

    public function nonSystemGroupIds(): array
    {
        return UserGroup::where('is_system', false)->pluck('id')->toArray();
    }

    public function updatePermissions(Rubric $rubric, array $perms): void
    {
        foreach ($this->nonSystemGroupIds() as $groupId) {
            $row               = $perms[$groupId] ?? [];
            $canCreate         = !empty($row['can_create']);
            $canCreateModerated = !empty($row['can_create_moderated']);

            if ($canCreate && $canCreateModerated) {
                $canCreateModerated = false;
            }

            RubricPermission::updateOrCreate(
                ['rubric_id' => $rubric->id, 'group_id' => $groupId],
                [
                    'can_view'             => !empty($row['can_view']),
                    'can_all'              => !empty($row['can_all']),
                    'can_create_moderated' => $canCreateModerated,
                    'can_create'           => $canCreate,
                    'can_edit_own'         => !empty($row['can_edit_own']),
                    'can_edit_all'         => !empty($row['can_edit_all']),
                    'can_revisions'        => !empty($row['can_revisions']),
                ],
            );
        }
    }

    public function buildTemplateTags(Rubric $rubric): array
    {
        $rubric->loadMissing('fields');

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
            $fieldTags[] = ['tag' => "[field:{$field->alias}]", 'title' => $field->title];
            $fieldTags[] = ['tag' => "[field:{$field->id}]", 'title' => $field->title . ' (по ID)'];
        }

        $navigation = [];

        try {
            foreach (DB::table('navigations')->select('alias', 'title')->orderBy('title')->get() as $item) {
                $navigation[] = ['tag' => "[nav:{$item->alias}]", 'title' => $item->title];
            }
        } catch (\Throwable) {
        }

        $blocks = [];

        try {
            foreach (DB::table('blocks')->select('alias', 'title')->orderBy('title')->get() as $item) {
                $blocks[] = ['tag' => "[block:{$item->alias}]", 'title' => $item->title];
            }
        } catch (\Throwable) {
        }

        $children = [
            ['tag' => '[if:has_children]...[/if:has_children]', 'title' => 'Блок показывается если есть дочерние страницы'],
            ['tag' => '[if:no_children]...[/if:no_children]', 'title' => 'Блок показывается если нет дочерних страниц'],
            ['tag' => '[children]...[/children]', 'title' => 'Перебор дочерних документов — шаблон повторяется для каждого'],
            ['tag' => '[doc:title]', 'title' => '↳ название дочернего документа'],
            ['tag' => '[doc:url]', 'title' => '↳ ссылка на дочерний документ'],
            ['tag' => '[doc:date]', 'title' => '↳ дата публикации'],
            ['tag' => '[doc:views]', 'title' => '↳ количество просмотров'],
            ['tag' => '[doc:alias]', 'title' => '↳ алиас дочернего документа'],
            ['tag' => '[doc:rubric]', 'title' => '↳ название рубрики дочернего'],
            ['tag' => '[field:alias]', 'title' => '↳ значение поля (замени alias на алиас нужного поля)'],
        ];

        return compact('system', 'fieldTags', 'navigation', 'blocks', 'children');
    }

    private function recordAliasChange(int $rubricId, ?string $oldAlias, ?string $newAlias): void
    {
        if ($newAlias !== null) {
            DB::table('rubric_alias_history')
                ->where('old_alias', $newAlias)
                ->where('rubric_id', $rubricId)
                ->delete();
        }

        if ($oldAlias !== null && trim($oldAlias) !== '') {
            DB::table('rubric_alias_history')->updateOrInsert(
                ['old_alias' => $oldAlias],
                ['rubric_id' => $rubricId, 'created_at' => now()],
            );
        }
    }
}
