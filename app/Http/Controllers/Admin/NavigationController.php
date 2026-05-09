<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNavigationRequest;
use App\Models\Document;
use App\Models\Navigation;
use App\Models\Setting;
use App\Models\UserGroup;
use App\Services\Logger;
use App\Support\DocumentUrl;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NavigationController extends Controller
{
    public function docSearch(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));
        $suffix = Setting::getValue('url_suffix', '');

        $docs = Document::select('documents.id', 'documents.title', 'documents.alias', 'documents.status', 'rubrics.alias as rubric_alias')
            ->leftJoin('rubrics', 'rubrics.id', '=', 'documents.rubric_id')
            ->when($q !== '', fn ($query) => $query->where('documents.title', 'ilike', '%' . $q . '%'))
            ->orderByRaw('CASE WHEN documents.status = ? THEN 0 ELSE 1 END', [Document::STATUS_ACTIVE])
            ->orderBy('documents.title')
            ->limit(10)
            ->get()
            ->map(fn ($doc) => [
                'title'    => $doc->title,
                'url'      => DocumentUrl::build($doc->rubric_alias, $doc->alias, $suffix),
                'is_draft' => $doc->status !== Document::STATUS_ACTIVE,
            ]);

        return response()->json($docs);
    }

    public function index(Request $request)
    {
        $user = $request->user('admin') ?? $request->user();

        $canCreate = $user?->hasPermission(Permission::NAVIGATIONS_CREATE) ?? false;
        $canEdit = $user?->hasPermission(Permission::NAVIGATIONS_EDIT) ?? false;
        $canDelete = $user?->hasPermission(Permission::NAVIGATIONS_DELETE) ?? false;

        $navigations = Navigation::orderBy('position')->get();
        $groups = UserGroup::orderBy('name')->get();

        return view('admin.navigations.index', compact('navigations', 'groups', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function store(StoreNavigationRequest $request)
    {
        $data = $request->validated();

        $data['position'] = Navigation::max('position') + 1;
        $nav = Navigation::create($data);

        Logger::adminAction('Создал меню навигации', 'create', 'navigation', $nav->id, $nav->title);

        return response()->json([
            'ok'       => true,
            'message'  => 'Меню создано',
            'redirect' => route('admin.navigations.items', $nav),
        ]);
    }

    public function copy(StoreNavigationRequest $request, Navigation $navigation)
    {
        $data = $request->validated();

        $copy = Navigation::create([
            'title'          => $data['title'],
            'alias'          => $data['alias'],
            'allowed_groups' => $navigation->allowed_groups,
            'template_l1'    => $navigation->template_l1,
            'link_tpl_l1'    => $navigation->link_tpl_l1,
            'template_l2'    => $navigation->template_l2,
            'link_tpl_l2'    => $navigation->link_tpl_l2,
            'template_l3'    => $navigation->template_l3,
            'link_tpl_l3'    => $navigation->link_tpl_l3,
            'position'       => Navigation::max('position') + 1,
        ]);

        Logger::adminAction("Скопировал меню «{$navigation->title}» → «{$copy->title}»", 'create', 'navigation', $copy->id, $copy->title);

        return response()->json(['ok' => true, 'message' => 'Меню скопировано (пункты не копируются)']);
    }

    public function destroy(Navigation $navigation)
    {
        [$id, $title] = [$navigation->id, $navigation->title];
        $navigation->delete();
        Logger::adminAction('Удалил меню навигации', 'delete', 'navigation', $id, $title);

        return response()->json(['ok' => true, 'message' => 'Меню удалено']);
    }
}
