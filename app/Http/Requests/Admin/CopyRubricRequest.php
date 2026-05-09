<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class CopyRubricRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255', 'unique:rubrics,title'],
            'alias' => ['nullable', 'string', 'max:100', 'unique:rubrics,alias', 'regex:/^[a-z0-9\-_]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Введите название новой рубрики.',
            'title.unique'   => 'Это название уже занято другой рубрикой.',
            'alias.unique'   => 'Этот префикс уже используется другой рубрикой.',
            'alias.regex'    => 'Префикс URL может содержать только латинские буквы, цифры, дефис и подчёркивание.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'название',
            'alias' => 'префикс URL',
        ];
    }
}
