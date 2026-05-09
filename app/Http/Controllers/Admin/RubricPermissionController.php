<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRubricPermissionRequest;
use App\Models\Rubric;
use App\Services\RubricService;
use App\Support\Permission;
use Illuminate\Http\Request;

class RubricPermissionController extends Controller
{
    public function __construct(private readonly RubricService $rubricService) {}

    public function edit(Rubric $rubric, Request $request)
    {
        $groups = $this->rubricService->nonSystemGroups();

        $rubric->loadMissing('permissions');
        $permissions = $rubric->permissions->keyBy('group_id');

        $user    = $request->user('admin') ?? $request->user();
        $canEdit = $user?->hasPermission(Permission::RUBRICS_PERMISSIONS) ?? false;

        return view('admin.rubrics.permissions', compact('rubric', 'groups', 'permissions', 'canEdit'));
    }

    public function update(Rubric $rubric, UpdateRubricPermissionRequest $request)
    {
        $this->rubricService->updatePermissions($rubric, $request->validated()['perms'] ?? []);

        return redirect()->back()->with('toast_success', 'Права сохранены');
    }
}
