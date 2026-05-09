<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRubricSubfieldAliasRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'alias' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_]*$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'alias.regex' => 'Алиас может содержать только латинские буквы, цифры и подчёркивания, начинаться с буквы.',
        ];
    }

    public function attributes(): array
    {
        return ['alias' => 'алиас подполя'];
    }
}
