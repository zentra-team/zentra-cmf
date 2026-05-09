<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CopyRubricRequest;
use App\Http\Requests\Admin\SaveAllRubricsRequest;
use App\Http\Requests\Admin\StoreRubricRequest;
use App\Http\Requests\Admin\UpdateRubricDescriptionRequest;
use App\Http\Requests\Admin\UpdateRubricOrderRequest;
use App\Http\Requests\Admin\UpdateRubricSeoRequest;
use App\Models\Layout;
use App\Models\Rubric;
use App\Models\RubricPermission;
use App\Models\UserGroup;
use App\Services\Logger;
use App\Support\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RubricController extends Controller
{
    public function index(Request $request)
    {
        $rubrics = Rubric::with('layout')->withCount('fields')->orderBy('position')->orderBy('id')->get();
        $layouts = Layout::orderBy('title')->get(['id', 'title']);

        [$canList, $canCreate, $canEdit, $canDelete, $canPermissions] = $this->resolveCaps($request);

        return view('admin.rubrics.index', compact(
            'rubrics',
            'layouts',
            'canList',
            'canCreate',
            'canEdit',
            'canDelete',
            'canPermissions',
        ));
    }

    private function resolveCaps(Request $request): array
    {
        $user = $request->user('admin') ?? $request->user();

        return [
            $user?->hasPermission(Permission::RUBRICS_LIST) ?? false,
            $user?->hasPermission(Permission::RUBRICS_CREATE) ?? false,
            $user?->hasPermission(Permission::RUBRICS_EDIT) ?? false,
            $user?->hasPermission(Permission::RUBRICS_DELETE) ?? false,
            $user?->hasPermission(Permission::RUBRICS_PERMISSIONS) ?? false,
        ];
    }

    public function store(StoreRubricRequest $request)
    {
        if (Layout::count() === 0) {
            return response()->json([
                'ok'      => false,
                'message' => 'Сначала создайте хотя бы один макет - рубрика не может существовать без макета.',
            ], 422);
        }

        $data = $request->validated();

        $maxPos = Rubric::max('position') ?? 0;
        $alias = trim($data['alias'] ?? '') !== '' ? $data['alias'] : null;

        $rubric = DB::transaction(function () use ($data, $alias, $maxPos) {
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

        Logger::adminAction('Создал рубрику', 'create', 'rubric', $rubric->id, $rubric->title);

        return response()->json([
            'ok'       => true,
            'message'  => 'Рубрика создана',
            'redirect' => route('admin.rubrics.fields', $rubric),
        ]);
    }

    public function saveAll(SaveAllRubricsRequest $request)
    {
        $rows = $request->validated()['rubrics'];

        try {
            DB::transaction(function () use ($rows) {
                foreach ($rows as $row) {
                    $rubric = Rubric::findOrFail($row['id']);
                    $newAlias = trim($row['alias'] ?? '') !== '' ? $row['alias'] : null;

                    $titleExists = Rubric::where('title', $row['title'])->where('id', '!=', $rubric->id)->exists();

                    if ($titleExists) {
                        throw new \RuntimeException("Название «{$row['title']}» уже занято другой рубрикой.");
                    }

                    if ($newAlias !== null) {
                        $aliasExists = Rubric::where('alias', $newAlias)->where('id', '!=', $rubric->id)->exists();

                        if ($aliasExists) {
                            throw new \RuntimeException("Префикс «{$newAlias}» уже используется другой рубрикой.");
                        }
                    }

                    $oldAlias = $rubric->alias;

                    if ($oldAlias !== $newAlias) {
                        $this->recordRubricAliasChange($rubric->id, $oldAlias, $newAlias);
                    }

                    $rubric->update([
                        'title'     => $row['title'],
                        'alias'     => $newAlias,
                        'color'     => $row['color'] ?? null,
                        'layout_id' => $row['layout_id'],
                    ]);
                }
            });
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'message' => 'Изменения сохранены']);
    }

    private function recordRubricAliasChange(int $rubricId, ?string $oldAlias, ?string $newAlias): void
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

    public function updateOrder(UpdateRubricOrderRequest $request)
    {
        $data = $request->validated();

        $rubrics = Rubric::whereIn('id', array_values($data['order']))->get()->keyBy('id');
        DB::transaction(function () use ($data, $rubrics) {
            foreach ($data['order'] as $pos => $id) {
                $rubric = $rubrics->get($id);

                if ($rubric === null) {
                    continue;
                }
                $rubric->position = $pos;
                $rubric->save();
            }
        });

        return response()->json(['ok' => true]);
    }

    public function update(Rubric $rubric, UpdateRubricDescriptionRequest $request)
    {
        $data = $request->validated();

        $rubric->update($data);

        Logger::adminAction('Обновил данные рубрики', 'edit', 'rubric', $rubric->id, $rubric->title);

        return response()->json(['ok' => true]);
    }

    public function docsCount(Rubric $rubric)
    {
        return response()->json(['count' => $rubric->docsCount()]);
    }

    public function copy(Rubric $rubric, CopyRubricRequest $request)
    {
        $data = $request->validated();

        $maxPos = Rubric::max('position') ?? 0;

        $copyAlias = trim($data['alias'] ?? '') !== '' ? $data['alias'] : null;

        $copy = DB::transaction(function () use ($rubric, $data, $copyAlias, $maxPos) {
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

        Logger::adminAction("Скопировал рубрику «{$rubric->title}» → «{$copy->title}»", 'create', 'rubric', $copy->id, $copy->title);

        return response()->json([
            'ok'       => true,
            'redirect' => route('admin.rubrics.index'),
        ]);
    }

    public function fieldsMeta(Rubric $rubric)
    {
        $fields = $rubric->fields()->orderBy('position')->get(['id', 'alias', 'title', 'type']);

        return response()->json(['ok' => true, 'fields' => $fields]);
    }

    public function updateApi(Rubric $rubric, \App\Http\Requests\Admin\UpdateRubricApiRequest $request)
    {
        $data = $request->validated();

        $rubric->update([
            'api_enabled'       => $request->boolean('api_enabled', false),
            'api_default_limit' => isset($data['api_default_limit']) && $data['api_default_limit'] !== '' ? (int) $data['api_default_limit'] : null,
            'api_max_limit'     => isset($data['api_max_limit']) && $data['api_max_limit'] !== '' ? (int) $data['api_max_limit'] : null,
        ]);

        Logger::adminAction('Изменил API-настройки рубрики', 'edit', 'rubric', $rubric->id, $rubric->title);

        return response()->json(['ok' => true, 'message' => 'API-настройки рубрики сохранены']);
    }

    public function updateRss(Rubric $rubric, \App\Http\Requests\Admin\UpdateRubricRssRequest $request)
    {
        $data = $request->validated();

        $rubric->update([
            'rss_enabled'              => $request->boolean('rss_enabled', false),
            'rss_title'                => $data['rss_title'] ?: null,
            'rss_description'          => $data['rss_description'] ?: null,
            'rss_limit'                => isset($data['rss_limit']) && $data['rss_limit'] !== '' ? (int) $data['rss_limit'] : null,
            'rss_description_field_id' => $data['rss_description_field_id'] ?: null,
            'rss_image_field_id'       => $data['rss_image_field_id'] ?: null,
            'rss_category_field_id'    => $data['rss_category_field_id'] ?: null,
        ]);

        Logger::adminAction('Изменил RSS рубрики', 'edit', 'rubric', $rubric->id, $rubric->title);

        return response()->json(['ok' => true, 'message' => 'Параметры RSS рубрики сохранены']);
    }

    public function updateSeo(Rubric $rubric, UpdateRubricSeoRequest $request)
    {
        $data = $request->validated();

        $rubric->update([
            'sitemap_include'          => $request->boolean('sitemap_include', true),
            'sitemap_changefreq'       => $data['sitemap_changefreq'] ?: null,
            'sitemap_priority'         => isset($data['sitemap_priority']) && $data['sitemap_priority'] !== '' ? (float) $data['sitemap_priority'] : null,
            'sitemap_index_changefreq' => $data['sitemap_index_changefreq'] ?: null,
            'sitemap_index_priority'   => isset($data['sitemap_index_priority']) && $data['sitemap_index_priority'] !== '' ? (float) $data['sitemap_index_priority'] : null,
            'public_cache_disabled'    => $request->boolean('public_cache_disabled', false),
            'public_cache_ttl'         => isset($data['public_cache_ttl']) && $data['public_cache_ttl'] !== '' ? (int) $data['public_cache_ttl'] : null,
        ]);

        Logger::adminAction('Изменил SEO/Sitemap/Кэш рубрики', 'edit', 'rubric', $rubric->id, $rubric->title);

        return response()->json(['ok' => true, 'message' => 'Параметры SEO рубрики сохранены']);
    }

    public function destroy(Rubric $rubric)
    {
        if ($rubric->hasDocuments()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Нельзя удалить рубрику с документами.',
            ], 422);
        }

        [$id, $title] = [$rubric->id, $rubric->title];
        $rubric->delete();
        Logger::adminAction('Удалил рубрику', 'delete', 'rubric', $id, $title);

        return response()->json(['ok' => true]);
    }
}
