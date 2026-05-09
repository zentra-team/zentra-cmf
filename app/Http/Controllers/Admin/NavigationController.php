<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreNavigationRequest;
use App\Models\Navigation;
use App\Services\Logger;
use App\Services\NavigationService;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NavigationController extends Controller
{
    public function __construct(private NavigationService $navigationService)
    {
    }

    public function docSearch(Request $request): JsonResponse
    {
        $q = trim($request->input('q', ''));

        $docs = $this->navigationService->searchDocuments($q);

        return response()->json($docs);
    }

    public function index(Request $request): View
    {
        $user = $request->user('admin') ?? $request->user();

        $canCreate = $user?->hasPermission(Permission::NAVIGATIONS_CREATE) ?? false;
        $canEdit = $user?->hasPermission(Permission::NAVIGATIONS_EDIT) ?? false;
        $canDelete = $user?->hasPermission(Permission::NAVIGATIONS_DELETE) ?? false;

        ['navigations' => $navigations, 'groups' => $groups] = $this->navigationService->indexData();

        return view('admin.navigations.index', compact('navigations', 'groups', 'canCreate', 'canEdit', 'canDelete'));
    }

    public function store(StoreNavigationRequest $request): JsonResponse
    {
        $nav = $this->navigationService->create($request->validated());

        Logger::adminAction('Создал меню навигации', 'create', 'navigation', $nav->id, $nav->title);

        return response()->json([
            'ok'       => true,
            'message'  => 'Меню создано',
            'redirect' => route('admin.navigations.items', $nav),
        ]);
    }

    public function copy(StoreNavigationRequest $request, Navigation $navigation): JsonResponse
    {
        $copy = $this->navigationService->copy($navigation, $request->validated());

        Logger::adminAction("Скопировал меню «{$navigation->title}» → «{$copy->title}»", 'create', 'navigation', $copy->id, $copy->title);

        return response()->json(['ok' => true, 'message' => 'Меню скопировано (пункты не копируются)']);
    }

    public function destroy(Navigation $navigation): JsonResponse
    {
        [$id, $title] = [$navigation->id, $navigation->title];
        $navigation->delete();
        Logger::adminAction('Удалил меню навигации', 'delete', 'navigation', $id, $title);

        return response()->json(['ok' => true, 'message' => 'Меню удалено']);
    }
}
