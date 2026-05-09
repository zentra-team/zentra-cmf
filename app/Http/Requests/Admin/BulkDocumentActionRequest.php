<?php

namespace App\Http\Requests\Admin;

use App\Support\Permission;
use Illuminate\Foundation\Http\FormRequest;

class BulkDocumentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('admin') ?? $this->user();
        $action = (string) $this->input('action');

        return match ($action) {
            'delete'            => $user?->hasPermission(Permission::DOCUMENTS_DELETE) ?? false,
            'activate', 'draft' => $user?->hasPermission(Permission::DOCUMENTS_EDIT) ?? false,
            default             => false,
        };
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:delete,activate,draft'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:documents,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'action.in'    => 'Неизвестное массовое действие.',
            'ids.min'      => 'Не выбраны документы.',
            'ids.*.exists' => 'Один из выбранных документов не существует.',
        ];
    }
}
