<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreLayoutRequest;
use App\Http\Requests\Admin\UpdateLayoutRequest;
use App\Models\Layout;
use App\Services\LayoutService;
use App\Services\Logger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LayoutController extends Controller
{
    public function __construct(
        private readonly LayoutService $layoutService,
    ) {
    }

    public function index(Request $request): View
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::guard('admin')->user();
        $canList = $authUser->hasPermission('layouts.list');
        $canCreate = $authUser->hasPermission('layouts.create');
        $canDelete = $authUser->hasPermission('layouts.delete');
        $canEdit = $authUser->hasPermission('layouts.edit');
        $canFiles = $authUser->hasPermission('layouts.files');

        $layouts = $canList ? $this->layoutService->list() : collect();
        $cssFiles = $canList ? $this->layoutService->listAssets('css') : [];
        $jsFiles = $canList ? $this->layoutService->listAssets('js') : [];

        $activeTab = $request->query('tab', 'layouts');

        return view('admin.layouts.index', compact(
            'layouts',
            'cssFiles',
            'jsFiles',
            'activeTab',
            'canList',
            'canCreate',
            'canDelete',
            'canEdit',
            'canFiles',
        ));
    }

    public function store(StoreLayoutRequest $request): RedirectResponse
    {
        $layout = $this->layoutService->create($request->validated());

        Logger::adminAction('Создал макет', 'create', 'layout', $layout->id, $layout->title);

        return redirect()
            ->route('admin.layouts.edit', $layout)
            ->with('toast_success', "Макет «{$layout->title}» создан");
    }

    public function edit(Layout $layout): View
    {
        /** @var \App\Models\User $authUser */
        $authUser = Auth::guard('admin')->user();
        $canEdit = $authUser->hasPermission('layouts.edit');
        $tags = $this->layoutService->availableTags();

        return view('admin.layouts.edit', compact('layout', 'tags', 'canEdit'));
    }

    public function update(UpdateLayoutRequest $request, Layout $layout): JsonResponse
    {
        $this->layoutService->update($layout, [
            'title'   => $request->title,
            'content' => $request->content ?? '',
        ]);

        Logger::adminAction('Редактировал макет', 'edit', 'layout', $layout->id, $layout->title);

        return response()->json(['ok' => true, 'message' => 'Макет сохранён']);
    }

    public function copy(StoreLayoutRequest $request, Layout $layout): JsonResponse
    {
        $copy = $this->layoutService->copy($layout, $request->title);

        Logger::adminAction("Скопировал макет «{$layout->title}» → «{$copy->title}»", 'create', 'layout', $copy->id, $copy->title);

        return response()->json([
            'ok'       => true,
            'redirect' => route('admin.layouts.edit', $copy),
        ]);
    }

    public function destroy(Layout $layout): JsonResponse
    {
        $result = $this->layoutService->delete($layout);

        if (!$result['ok']) {
            return response()->json($result);
        }

        Logger::adminAction('Удалил макет', 'delete', 'layout', $layout->id, $layout->title);

        return response()->json(['ok' => true]);
    }

    public function tags(): JsonResponse
    {
        return response()->json($this->layoutService->availableTags());
    }
}
