<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveAllRubricsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'rubrics'             => ['required', 'array'],
            'rubrics.*.id'        => ['required', 'integer', 'exists:rubrics,id'],
            'rubrics.*.title'     => ['required', 'string', 'max:255'],
            'rubrics.*.alias'     => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-_]+$/'],
            'rubrics.*.color'     => ['nullable', 'string', 'max:20'],
            'rubrics.*.layout_id' => ['required', 'integer', 'exists:layouts,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'rubrics.*.title.required'     => 'У каждой рубрики должно быть название.',
            'rubrics.*.alias.regex'        => 'Префикс URL может содержать только латинские буквы, цифры, дефис и подчёркивание.',
            'rubrics.*.layout_id.required' => 'У каждой рубрики должен быть выбран макет.',
            'rubrics.*.layout_id.exists'   => 'Указанный макет не существует.',
        ];
    }

    public function attributes(): array
    {
        return [
            'rubrics.*.title'     => 'название',
            'rubrics.*.alias'     => 'префикс URL',
            'rubrics.*.layout_id' => 'макет',
            'rubrics.*.color'     => 'цвет',
        ];
    }
}
