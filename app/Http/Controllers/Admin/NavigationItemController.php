<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderNavigationItemsRequest;
use App\Http\Requests\Admin\StoreNavigationItemRequest;
use App\Http\Requests\Admin\UpdateNavigationItemRequest;
use App\Models\Navigation;
use App\Models\NavigationItem;
use App\Services\Logger;
use App\Services\NavigationService;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NavigationItemController extends Controller
{
    public function __construct(private NavigationService $navigationService)
    {
    }

    public function index(Request $request, Navigation $navigation): View
    {
        $user = $request->user('admin') ?? $request->user();

        $canEdit = $user?->hasPermission(Permission::NAVIGATIONS_EDIT) ?? false;
        $canDelete = $user?->hasPermission(Permission::NAVIGATIONS_DELETE) ?? false;

        $items = $this->navigationService->itemsTree($navigation);

        return view('admin.navigations.items', compact('navigation', 'items', 'canEdit', 'canDelete'));
    }

    public function store(StoreNavigationItemRequest $request, Navigation $navigation): JsonResponse
    {
        $item = $this->navigationService->createItem($navigation, $request->validated());

        Logger::adminAction("Добавил пункт меню «{$item->title}» в «{$navigation->title}»", 'create', 'nav_item', $item->id, $item->title);

        return response()->json(['ok' => true, 'message' => 'Пункт добавлен', 'id' => $item->id]);
    }

    public function update(UpdateNavigationItemRequest $request, Navigation $navigation, NavigationItem $item): JsonResponse
    {
        abort_if($item->navigation_id !== $navigation->id, 404);

        $data = $request->validated();

        if (isset($data['parent_id']) && $data['parent_id'] == $item->id) {
            unset($data['parent_id']);
        }

        $item->update($data);

        Logger::adminAction("Редактировал пункт меню «{$item->title}» в «{$navigation->title}»", 'edit', 'nav_item', $item->id, $item->title);

        return response()->json(['ok' => true, 'message' => 'Пункт обновлён']);
    }

    public function toggleStatus(Navigation $navigation, NavigationItem $item): JsonResponse
    {
        abort_if($item->navigation_id !== $navigation->id, 404);

        $this->navigationService->toggleItem($item);

        return response()->json([
            'ok'        => true,
            'is_active' => $item->is_active,
        ]);
    }

    public function reorder(ReorderNavigationItemsRequest $request, Navigation $navigation): JsonResponse
    {
        $this->navigationService->reorderItems($navigation, $request->items);

        return response()->json(['ok' => true]);
    }

    public function destroy(Navigation $navigation, NavigationItem $item): JsonResponse
    {
        abort_if($item->navigation_id !== $navigation->id, 404);

        [$id, $title] = [$item->id, $item->title];
        $this->navigationService->deleteItem($item);
        Logger::adminAction("Удалил пункт меню «{$title}» из «{$navigation->title}»", 'delete', 'nav_item', $id, $title);

        return response()->json(['ok' => true, 'message' => 'Пункт удалён']);
    }
}
