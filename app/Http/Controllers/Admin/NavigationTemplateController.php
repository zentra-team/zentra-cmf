<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateNavigationTemplateRequest;
use App\Models\Navigation;
use App\Models\UserGroup;
use App\Services\Logger;
use App\Support\Permission;
use Illuminate\Http\Request;

class NavigationTemplateController extends Controller
{
    public function edit(Request $request, Navigation $navigation)
    {
        $user = $request->user('admin') ?? $request->user();

        $canEdit = $user?->hasPermission(Permission::NAVIGATIONS_EDIT) ?? false;

        $groups = UserGroup::where('is_system', false)->orderBy('name')->get();

        return view('admin.navigations.template', compact('navigation', 'groups', 'canEdit'));
    }

    public function update(UpdateNavigationTemplateRequest $request, Navigation $navigation)
    {
        $data = $request->validated();

        $navigation->update($data);

        Logger::adminAction("Обновил шаблон меню «{$navigation->title}»", 'edit', 'navigation', $navigation->id, $navigation->title);

        return response()->json(['ok' => true, 'message' => 'Настройки сохранены']);
    }
}
