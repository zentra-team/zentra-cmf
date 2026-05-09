<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserGroupInfoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_default'  => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'        => 'название',
            'description' => 'описание',
            'is_default'  => 'группа по умолчанию',
        ];
    }
}
