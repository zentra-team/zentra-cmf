<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateBlockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $blockId = $this->route('block')?->id ?? $this->route('block');

        return [
            'title'       => 'required|string|max:255',
            'alias'       => 'required|string|max:100|unique:blocks,alias,' . $blockId . '|regex:/^[a-z0-9_]+$/',
            'description' => 'nullable|string|max:500',
            'group_id'    => 'nullable|exists:block_groups,id',
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
