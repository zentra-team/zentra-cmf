<?php

namespace App\Http\Requests\Admin;

use App\Support\Permission;
use Illuminate\Foundation\Http\FormRequest;

class DestroyImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('admin') ?? $this->user();

        return $user?->hasAnyPermission(Permission::uploadAllowingPermissions()) ?? false;
    }

    public function rules(): array
    {
        return [
            'url' => ['required', 'string'],
        ];
    }
}
