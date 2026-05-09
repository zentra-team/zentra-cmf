<?php

namespace App\Services;

use App\Models\Rubric;
use App\Models\RubricPermission;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class UserGroupService
{
    public function list(): Collection
    {
        return UserGroup::withCount('users')->orderBy('id')->get();
    }

    public function create(array $data): UserGroup
    {
        $group = UserGroup::create([
            'name'        => $data['name'],
            'permissions' => [],
        ]);

        $this->seedRubricPermissions($group);

        return $group;
    }

    public function updateInfo(UserGroup $group, array $data): void
    {
        $isDefault = (bool) ($data['is_default'] ?? false);

        DB::transaction(function () use ($group, $data, $isDefault) {
            if ($isDefault) {
                UserGroup::where('id', '!=', $group->id)
                    ->where('is_default', true)
                    ->update(['is_default' => false]);
            }

            $group->update([
                'name'        => $data['name'],
                'description' => $data['description'] ?? null,
                'is_default'  => $isDefault,
            ]);
        });
    }

    public function duplicate(UserGroup $group): UserGroup
    {
        $baseName = $group->name . ' (копия)';
        $newName = $baseName;
        $counter = 2;
        while (UserGroup::where('name', $newName)->exists()) {
            $newName = $baseName . ' ' . $counter++;
        }

        return DB::transaction(function () use ($group, $newName) {
            $copy = UserGroup::create([
                'name'        => $newName,
                'description' => $group->description,
                'is_default'  => false,
                'permissions' => $group->permissions ?? [],
            ]);

            foreach (RubricPermission::where('group_id', $group->id)->get() as $rp) {
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
    }

    public function seedRubricPermissions(UserGroup $group): void
    {
        foreach (Rubric::pluck('id') as $rubricId) {
            RubricPermission::create([
                'rubric_id'            => $rubricId,
                'group_id'             => $group->id,
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

    public function delete(UserGroup $group): void
    {
        $group->delete();
    }
}
