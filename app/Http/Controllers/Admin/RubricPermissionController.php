<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRubricPermissionRequest;
use App\Models\Rubric;
use App\Models\RubricPermission;
use App\Models\UserGroup;
use App\Support\Permission;
use Illuminate\Http\Request;

class RubricPermissionController extends Controller
{
    public function edit(Rubric $rubric, Request $request)
    {
        $groups = UserGroup::where('is_system', false)->orderBy('name')->get();

        $rubric->loadMissing('permissions');
        $permissions = $rubric->permissions->keyBy('group_id');

        $user = $request->user('admin') ?? $request->user();
        $canEdit = $user?->hasPermission(Permission::RUBRICS_PERMISSIONS) ?? false;

        return view('admin.rubrics.permissions', compact('rubric', 'groups', 'permissions', 'canEdit'));
    }

    public function update(Rubric $rubric, UpdateRubricPermissionRequest $request)
    {
        $data = $request->validated();
        $perms = $data['perms'] ?? [];

        $groups = UserGroup::where('is_system', false)->pluck('id')->toArray();

        foreach ($groups as $groupId) {
            $row = $perms[$groupId] ?? [];

            $canCreate = !empty($row['can_create']);
            $canCreateModerated = !empty($row['can_create_moderated']);

            if ($canCreate && $canCreateModerated) {
                $canCreateModerated = false;
            }

            RubricPermission::updateOrCreate(
                ['rubric_id' => $rubric->id, 'group_id' => $groupId],
                [
                    'can_view'             => !empty($row['can_view']),
                    'can_all'              => !empty($row['can_all']),
                    'can_create_moderated' => $canCreateModerated,
                    'can_create'           => $canCreate,
                    'can_edit_own'         => !empty($row['can_edit_own']),
                    'can_edit_all'         => !empty($row['can_edit_all']),
                    'can_revisions'        => !empty($row['can_revisions']),
                ],
            );
        }

        return redirect()->back()->with('toast_success', 'Права сохранены');
    }
}
