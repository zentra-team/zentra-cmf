<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreRubricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'     => ['required', 'string', 'max:255', 'unique:rubrics,title'],
            'alias'     => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9\-_]+$/', 'unique:rubrics,alias'],
            'layout_id' => ['required', 'integer', 'exists:layouts,id'],
            'color'     => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'alias.unique'       => 'Этот префикс уже используется другой рубрикой.',
            'alias.regex'        => 'Только строчные латинские буквы, цифры, дефис и подчёркивание.',
            'layout_id.required' => 'Выберите макет - рубрика не может существовать без макета.',
            'layout_id.exists'   => 'Указанный макет не существует.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title'     => 'название',
            'alias'     => 'префикс URL',
            'layout_id' => 'макет',
            'color'     => 'цвет',
        ];
    }
}
