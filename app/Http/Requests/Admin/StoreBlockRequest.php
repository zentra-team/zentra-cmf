<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'alias'       => 'required|string|max:100|unique:blocks,alias|regex:/^[a-z0-9_]+$/',
            'description' => 'nullable|string|max:500',
            'group_id'    => 'nullable|exists:block_groups,id',
            'is_wysiwyg'  => 'boolean',
            'content'     => 'nullable|string',
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
