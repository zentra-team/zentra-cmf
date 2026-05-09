<?php

namespace App\Http\Requests\Admin;

use App\Support\Permission;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRubricPermissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('admin') ?? $this->user();

        return (bool) ($user?->hasPermission(Permission::RUBRICS_ACCESS)
            && $user?->hasPermission(Permission::RUBRICS_PERMISSIONS));
    }

    public function rules(): array
    {
        return [
            'perms'                        => ['nullable', 'array'],
            'perms.*'                      => ['array'],
            'perms.*.can_view'             => ['nullable', 'boolean'],
            'perms.*.can_all'              => ['nullable', 'boolean'],
            'perms.*.can_create_moderated' => ['nullable', 'boolean'],
            'perms.*.can_create'           => ['nullable', 'boolean'],
            'perms.*.can_edit_own'         => ['nullable', 'boolean'],
            'perms.*.can_edit_all'         => ['nullable', 'boolean'],
            'perms.*.can_revisions'        => ['nullable', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $perms = (array) $this->input('perms', []);

            foreach ($perms as $groupId => $row) {
                if (!empty($row['can_create']) && !empty($row['can_create_moderated'])) {
                    $validator->errors()->add(
                        "perms.{$groupId}.can_create",
                        'Нельзя одновременно включать «Создавать с проверкой» и «Создавать без проверки» для одной группы.',
                    );
                }
            }
        });
    }
}
