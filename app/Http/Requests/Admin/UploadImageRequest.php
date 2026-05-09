<?php

namespace App\Http\Requests\Admin;

use App\Support\Permission;
use Illuminate\Foundation\Http\FormRequest;

class UploadImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user('admin') ?? $this->user();

        return $user?->hasAnyPermission(Permission::uploadAllowingPermissions()) ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file', 'image', 'mimes:jpeg,jpg,png,gif,webp,svg', 'max:10240'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.image' => 'Файл должен быть изображением',
            'file.mimes' => 'Допустимые форматы: jpeg, png, gif, webp, svg',
            'file.max'   => 'Максимальный размер файла - 10 МБ',
        ];
    }
}
