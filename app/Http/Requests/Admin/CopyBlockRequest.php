<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CopyBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alias'    => 'required|string|max:100|unique:blocks,alias|regex:/^[a-z0-9_]+$/',
            'group_id' => 'nullable|exists:block_groups,id',
        ];
    }

    public function messages(): array
    {
        return [
            'alias.regex'  => 'Алиас может содержать только латинские буквы, цифры и подчёркивания.',
            'alias.unique' => 'Блок с таким алиасом уже существует.',
        ];
    }
}
