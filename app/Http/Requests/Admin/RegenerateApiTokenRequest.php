<?php

namespace App\Http\Requests\Admin;

use App\Support\Permission;
use Illuminate\Foundation\Http\FormRequest;

class RegenerateApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('admin') ?? $this->user();

        return $user?->hasPermission(Permission::API_TOKENS_EDIT) ?? false;
    }

    public function rules(): array
    {
        return [
            'expires_at' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'expires_at' => 'срок действия',
        ];
    }
}
