<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateNavigationTemplateRequest;
use App\Models\Navigation;
use App\Services\Logger;
use App\Services\NavigationService;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NavigationTemplateController extends Controller
{
    public function __construct(private readonly NavigationService $navigationService)
    {
    }

    public function edit(Request $request, Navigation $navigation): View
    {
        $user    = $request->user('admin') ?? $request->user();
        $canEdit = $user?->hasPermission(Permission::NAVIGATIONS_EDIT) ?? false;
        $groups  = $this->navigationService->nonSystemGroups();

        return view('admin.navigations.template', compact('navigation', 'groups', 'canEdit'));
    }

    public function update(UpdateNavigationTemplateRequest $request, Navigation $navigation): JsonResponse
    {
        $navigation->update($request->validated());

        Logger::adminAction("Обновил шаблон меню «{$navigation->title}»", 'edit', 'navigation', $navigation->id, $navigation->title);

        return response()->json(['ok' => true, 'message' => 'Настройки сохранены']);
    }
}
