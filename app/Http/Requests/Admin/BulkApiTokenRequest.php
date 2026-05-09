<?php

namespace App\Http\Requests\Admin;

use App\Support\Permission;
use Illuminate\Foundation\Http\FormRequest;

class BulkApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('admin') ?? $this->user();
        $action = (string) $this->input('action');

        return match ($action) {
            'activate', 'deactivate' => $user?->hasPermission(Permission::API_TOKENS_EDIT) ?? false,
            'delete'                 => $user?->hasPermission(Permission::API_TOKENS_DELETE) ?? false,
            default                  => false,
        };
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'string', 'in:activate,deactivate,delete'],
            'ids'    => ['required', 'array', 'min:1'],
            'ids.*'  => ['integer', 'exists:api_tokens,id'],
        ];
    }
}
