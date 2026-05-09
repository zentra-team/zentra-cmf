<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNavigationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $navigationId = $this->route('navigation')->id;

        return [
            'title'            => 'required|string|max:255|unique:navigations,title,' . $navigationId,
            'alias'            => 'required|string|max:100|unique:navigations,alias,' . $navigationId . '|regex:/^[a-z0-9_]+$/',
            'allowed_groups'   => 'nullable|array',
            'allowed_groups.*' => 'integer|exists:user_groups,id',
            'template_l1'      => 'nullable|string',
            'link_tpl_l1'      => 'nullable|string',
            'template_l2'      => 'nullable|string',
            'link_tpl_l2'      => 'nullable|string',
            'template_l3'      => 'nullable|string',
            'link_tpl_l3'      => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'alias.regex'  => 'Алиас может содержать только латинские буквы, цифры и подчёркивания.',
            'alias.unique' => 'Меню с таким алиасом уже существует.',
            'title.unique' => 'Меню с таким названием уже существует.',
        ];
    }
}
