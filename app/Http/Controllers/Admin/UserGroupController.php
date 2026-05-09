<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserGroupRequest;
use App\Http\Requests\Admin\UpdateUserGroupInfoRequest;
use App\Http\Requests\Admin\UpdateUserGroupPermissionsRequest;
use App\Models\Rubric;
use App\Models\RubricPermission;
use App\Models\UserGroup;
use App\Services\Logger;
use App\Services\PermissionRegistry;
use Illuminate\Support\Facades\DB;

class UserGroupController extends Controller
{
    public function index()
    {
        $groups = UserGroup::withCount('users')->orderBy('id')->get();

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
        $data = $request->validated();

        $group = UserGroup::create([
            'name'        => $data['name'],
            'permissions' => [],
        ]);

        $this->seedRubricPermissions($group->id);

        Logger::adminAction('Создал группу пользователей', 'create', 'user_group', $group->id, $group->name);

        return redirect()->route('admin.user-groups.edit', $group)
            ->with('success', 'Группа создана. Настройте права доступа.');
    }

    public function edit(UserGroup $userGroup)
    {
        $sections = app(PermissionRegistry::class)->sections();
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

        $data = $request->validated();
        $isDefault = (bool) ($data['is_default'] ?? false);

        DB::transaction(function () use ($userGroup, $data, $isDefault) {
            if ($isDefault) {
                UserGroup::where('id', '!=', $userGroup->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $userGroup->update([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'is_default'  => $isDefault,
            ]);
        });

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
        $baseName = $userGroup->name . ' (копия)';
        $newName = $baseName;
        $counter = 2;
        while (UserGroup::where('name', $newName)->exists()) {
            $newName = $baseName . ' ' . $counter++;
        }

        $copy = DB::transaction(function () use ($userGroup, $newName) {
            $copy = UserGroup::create([
                'name'        => $newName,
                'description' => $userGroup->description,
                'is_default'  => false,
                'permissions' => $userGroup->permissions ?? [],
            ]);

            foreach (RubricPermission::where('group_id', $userGroup->id)->get() as $rp) {
                RubricPermission::create([
                    'rubric_id'            => $rp->rubric_id,
                    'group_id'             => $copy->id,
                    'can_view'             => $rp->can_view,
                    'can_all'              => $rp->can_all,
                    'can_create_moderated' => $rp->can_create_moderated,
                    'can_create'           => $rp->can_create,
                    'can_edit_own'         => $rp->can_edit_own,
                    'can_edit_all'         => $rp->can_edit_all,
                    'can_revisions'        => $rp->can_revisions,
                ]);
            }

            return $copy;
        });

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
        $userGroup->delete();
        Logger::adminAction('Удалил группу пользователей', 'delete', 'user_group', $id, $name);

        return response()->json(['ok' => true, 'message' => 'Группа удалена']);
    }

    private function seedRubricPermissions(int $groupId): void
    {
        foreach (Rubric::pluck('id') as $rubricId) {
            RubricPermission::create([
                'rubric_id'            => $rubricId,
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
    }
}
