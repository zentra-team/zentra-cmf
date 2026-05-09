<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CopyRubricRequest;
use App\Http\Requests\Admin\SaveAllRubricsRequest;
use App\Http\Requests\Admin\StoreRubricRequest;
use App\Http\Requests\Admin\UpdateRubricDescriptionRequest;
use App\Http\Requests\Admin\UpdateRubricOrderRequest;
use App\Http\Requests\Admin\UpdateRubricSeoRequest;
use App\Models\Rubric;
use App\Services\Logger;
use App\Services\RubricService;
use App\Support\Permission;
use Illuminate\Http\Request;
use App\Http\Requests\Admin\UpdateRubricApiRequest;
use App\Http\Requests\Admin\UpdateRubricRssRequest;

class RubricController extends Controller
{
    public function __construct(private readonly RubricService $rubricService) {}

    public function index(Request $request)
    {
        ['rubrics' => $rubrics, 'layouts' => $layouts] = $this->rubricService->list();

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

    public function store(StoreRubricRequest $request)
    {
        if (!$this->rubricService->hasLayouts()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Сначала создайте хотя бы один макет - рубрика не может существовать без макета.',
            ], 422);
        }

        $rubric = $this->rubricService->create($request->validated());

        Logger::adminAction('Создал рубрику', 'create', 'rubric', $rubric->id, $rubric->title);

        return response()->json([
            'ok'       => true,
            'message'  => 'Рубрика создана',
            'redirect' => route('admin.rubrics.fields', $rubric),
        ]);
    }

    public function saveAll(SaveAllRubricsRequest $request)
    {
        try {
            $this->rubricService->saveAll($request->validated()['rubrics']);
        } catch (\RuntimeException $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'message' => 'Изменения сохранены']);
    }

    public function updateOrder(UpdateRubricOrderRequest $request)
    {
        $this->rubricService->updateOrder($request->validated()['order']);

        return response()->json(['ok' => true]);
    }

    public function update(Rubric $rubric, UpdateRubricDescriptionRequest $request)
    {
        $rubric->update($request->validated());

        Logger::adminAction('Обновил данные рубрики', 'edit', 'rubric', $rubric->id, $rubric->title);

        return response()->json(['ok' => true]);
    }

    public function docsCount(Rubric $rubric)
    {
        return response()->json(['count' => $rubric->docsCount()]);
    }

    public function copy(Rubric $rubric, CopyRubricRequest $request)
    {
        $copy = $this->rubricService->copy($rubric, $request->validated());

        Logger::adminAction("Скопировал рубрику «{$rubric->title}» → «{$copy->title}»", 'create', 'rubric', $copy->id, $copy->title);

        return response()->json([
            'ok'       => true,
            'redirect' => route('admin.rubrics.index'),
        ]);
    }

    public function fieldsMeta(Rubric $rubric)
    {
        return response()->json([
            'ok'     => true,
            'fields' => $rubric->fields()->orderBy('position')->get(['id', 'alias', 'title', 'type']),
        ]);
    }

    public function updateApi(Rubric $rubric, UpdateRubricApiRequest $request)
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

    public function updateRss(Rubric $rubric, UpdateRubricRssRequest $request)
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
            return response()->json(['ok' => false, 'message' => 'Нельзя удалить рубрику с документами.'], 422);
        }

        [$id, $title] = [$rubric->id, $rubric->title];
        $rubric->delete();
        Logger::adminAction('Удалил рубрику', 'delete', 'rubric', $id, $title);

        return response()->json(['ok' => true]);
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
}
