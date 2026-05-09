<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRubricFieldAliasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alias' => [
                'required', 'string', 'max:50', 'regex:/^[a-z0-9_]+$/',
                Rule::unique('rubric_fields')->where('rubric_id', $this->route('rubric')->id)->ignore($this->route('field')->id),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'alias.required' => 'Введите алиас.',
            'alias.regex'    => 'Алиас может содержать только латинские буквы, цифры и подчёркивание.',
            'alias.unique'   => 'Поле с таким алиасом уже есть в этой рубрике.',
        ];
    }

    public function attributes(): array
    {
        return ['alias' => 'алиас'];
    }
}
