<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderNavigationItemsRequest;
use App\Http\Requests\Admin\StoreNavigationItemRequest;
use App\Http\Requests\Admin\UpdateNavigationItemRequest;
use App\Models\Navigation;
use App\Models\NavigationItem;
use App\Services\Logger;
use App\Support\Permission;
use Illuminate\Http\Request;

class NavigationItemController extends Controller
{
    public function index(Request $request, Navigation $navigation)
    {
        $user = $request->user('admin') ?? $request->user();

        $canEdit = $user?->hasPermission(Permission::NAVIGATIONS_EDIT) ?? false;
        $canDelete = $user?->hasPermission(Permission::NAVIGATIONS_DELETE) ?? false;

        $items = $navigation->rootItems()->with('allChildren')->get();

        return view('admin.navigations.items', compact('navigation', 'items', 'canEdit', 'canDelete'));
    }

    public function store(StoreNavigationItemRequest $request, Navigation $navigation)
    {
        $data = $request->validated();

        $parentId = $data['parent_id'] ?? null;
        $afterId = $data['after_id'] ?? null;

        if ($afterId) {
            $after = NavigationItem::find($afterId);
            $position = $after ? $after->position + 1 : 0;
        } else {
            $position = NavigationItem::where('navigation_id', $navigation->id)
                ->where('parent_id', $parentId)
                ->max('position') + 1;
        }

        $item = NavigationItem::create([
            'navigation_id' => $navigation->id,
            'parent_id'     => $parentId,
            'title'         => $data['title'],
            'url'           => $data['url'] ?? null,
            'target'        => $data['target'] ?? '_self',
            'css_class'     => $data['css_class'] ?? null,
            'css_id'        => $data['css_id'] ?? null,
            'css_style'     => $data['css_style'] ?? null,
            'description'   => $data['description'] ?? null,
            'image'         => $data['image'] ?? null,
            'icon'          => $data['icon'] ?? null,
            'extra_html'    => $data['extra_html'] ?? null,
            'position'      => $position,
            'is_active'     => true,
        ]);

        Logger::adminAction("Добавил пункт меню «{$item->title}» в «{$navigation->title}»", 'create', 'nav_item', $item->id, $item->title);

        return response()->json(['ok' => true, 'message' => 'Пункт добавлен', 'id' => $item->id]);
    }

    public function update(UpdateNavigationItemRequest $request, Navigation $navigation, NavigationItem $item)
    {
        $data = $request->validated();

        if (isset($data['parent_id']) && $data['parent_id'] == $item->id) {
            unset($data['parent_id']);
        }

        $item->update($data);

        Logger::adminAction("Редактировал пункт меню «{$item->title}»", 'edit', 'nav_item', $item->id, $item->title);

        return response()->json(['ok' => true, 'message' => 'Пункт обновлён']);
    }

    public function toggleStatus(Navigation $navigation, NavigationItem $item)
    {
        $item->update(['is_active' => !$item->is_active]);

        return response()->json([
            'ok'        => true,
            'is_active' => $item->is_active,
        ]);
    }

    public function reorder(ReorderNavigationItemsRequest $request, Navigation $navigation)
    {
        foreach ($request->items as $row) {
            NavigationItem::where('id', $row['id'])
                ->where('navigation_id', $navigation->id)
                ->update([
                    'parent_id' => $row['parent_id'],
                    'position'  => $row['position'],
                ]);
        }

        return response()->json(['ok' => true]);
    }

    public function destroy(Navigation $navigation, NavigationItem $item)
    {
        [$id, $title] = [$item->id, $item->title];
        $item->delete();
        Logger::adminAction("Удалил пункт меню «{$title}» из «{$navigation->title}»", 'delete', 'nav_item', $id, $title);

        return response()->json(['ok' => true, 'message' => 'Пункт удалён']);
    }
}
