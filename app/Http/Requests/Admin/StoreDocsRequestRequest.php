<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDocsRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => ['required', 'string', 'max:255'],
            'alias'       => ['required', 'string', 'max:100', 'alpha_dash', 'unique:requests,alias'],
            'rubric_id'   => ['nullable', 'exists:rubrics,id'],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'alias.unique' => 'Запрос с таким алиасом уже существует',
        ];
    }
}
