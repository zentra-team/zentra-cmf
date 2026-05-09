<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocsRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'alias' => ['required', 'string', 'max:100', 'alpha_dash',
                                  'unique:requests,alias,' . $this->route('docsRequest')?->id],
            'rubric_ids'      => ['nullable', 'array'],
            'rubric_ids.*'    => ['integer', 'exists:rubrics,id'],
            'description'     => ['nullable', 'string'],
            'sort_field'      => ['nullable', 'string', 'max:100'],
            'sort_system'     => ['nullable', 'string', 'max:50'],
            'sort_order'      => ['in:asc,desc'],
            'fetch_mode'      => ['in:global,distributed'],
            'limit'           => ['nullable', 'integer', 'min:1'],
            'show_pagination' => ['boolean'],
            'per_page'        => ['nullable', 'integer', 'min:1'],
            'exclude_current' => ['boolean'],
            'cache_time'      => ['nullable', 'integer', 'min:0'],
            'conditions'      => ['nullable', 'array'],
            'template_main'   => ['nullable', 'string'],
            'template_item'   => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'alias.unique' => 'Запрос с таким алиасом уже существует',
        ];
    }
}
