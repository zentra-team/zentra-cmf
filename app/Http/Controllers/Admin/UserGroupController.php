<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserGroupRequest;
use App\Http\Requests\Admin\UpdateUserGroupInfoRequest;
use App\Http\Requests\Admin\UpdateUserGroupPermissionsRequest;
use App\Models\UserGroup;
use App\Services\Logger;
use App\Services\PermissionRegistry;
use App\Services\UserGroupService;
use App\Models\User;

class UserGroupController extends Controller
{
    public function __construct(private UserGroupService $userGroupService)
    {
    }

    public function index()
    {
        $groups = $this->userGroupService->list();

        /** @var User|null $authUser */
        $authUser = auth('admin')->user();
        $canList = $authUser?->hasPermission('groups.list') ?? false;
        $canCreate = $authUser?->hasPermission('groups.create') ?? false;
        $canEdit = $authUser?->hasPermission('groups.edit') ?? false;
        $canDelete = $authUser?->hasPermission('groups.delete') ?? false;

        return view('admin.users.groups.index', compact(
            'groups',
            'canList',
            'canCreate',
            'canEdit',
            'canDelete',
        ));
    }

    public function store(StoreUserGroupRequest $request)
    {
        $group = $this->userGroupService->create($request->validated());

        Logger::adminAction('Создал группу пользователей', 'create', 'user_group', $group->id, $group->name);

        return redirect()->route('admin.user-groups.edit', $group)
            ->with('success', 'Группа создана. Настройте права доступа.');
    }

    public function edit(UserGroup $userGroup)
    {
        $sections = app(PermissionRegistry::class)->sections();
        
        /** @var User|null $authUser */
        $authUser = auth('admin')->user();
        $canEdit = $authUser?->hasPermission('groups.edit') ?? false;
        $canDelete = $authUser?->hasPermission('groups.delete') ?? false;

        return view('admin.users.groups.edit', compact('userGroup', 'sections', 'canEdit', 'canDelete'));
    }

    public function updateInfo(UpdateUserGroupInfoRequest $request, UserGroup $userGroup)
    {
        if ($userGroup->is_system) {
            return response()->json([
                'ok'      => false,
                'message' => 'Системную группу нельзя редактировать',
            ], 422);
        }

        $this->userGroupService->updateInfo($userGroup, $request->validated());

        Logger::adminAction('Изменил данные группы пользователей', 'edit', 'user_group', $userGroup->id, $userGroup->name);

        return response()->json(['ok' => true, 'message' => 'Данные группы сохранены']);
    }

    public function updatePermissions(UpdateUserGroupPermissionsRequest $request, UserGroup $userGroup)
    {
        if ($userGroup->is_system) {
            return response()->json([
                'ok'      => false,
                'message' => 'Права системной группы нельзя изменить',
            ], 422);
        }

        $userGroup->update(['permissions' => $request->validated()['permissions'] ?? []]);

        Logger::adminAction('Изменил права группы пользователей', 'edit', 'user_group', $userGroup->id, $userGroup->name);

        return response()->json(['ok' => true, 'message' => 'Права группы сохранены']);
    }

    public function duplicate(UserGroup $userGroup)
    {
        $copy = $this->userGroupService->duplicate($userGroup);

        Logger::adminAction("Скопировал группу «{$userGroup->name}»", 'create', 'user_group', $copy->id, $copy->name);

        return response()->json([
            'ok'       => true,
            'message'  => 'Группа скопирована',
            'redirect' => route('admin.user-groups.edit', $copy),
        ]);
    }

    public function destroy(UserGroup $userGroup)
    {
        if ($userGroup->is_system) {
            return response()->json([
                'ok'      => false,
                'message' => 'Системную группу нельзя удалить',
            ], 422);
        }

        if ($userGroup->users()->exists()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Нельзя удалить группу: в ней есть пользователи',
            ], 422);
        }

        [$id, $name] = [$userGroup->id, $userGroup->name];
        $this->userGroupService->delete($userGroup);
        Logger::adminAction('Удалил группу пользователей', 'delete', 'user_group', $id, $name);

        return response()->json(['ok' => true, 'message' => 'Группа удалена']);
    }
}
