<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateApiTokenRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => ['required', 'string', 'max:150'],
            'description'           => ['nullable', 'string', 'max:2000'],
            'allowed_rubrics'       => ['nullable', 'array'],
            'allowed_rubrics.*'     => ['integer', 'exists:rubrics,id'],
            'rate_limit_per_minute' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'is_active'             => ['nullable', 'boolean'],
            'expires_at'            => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'                  => 'название',
            'description'           => 'описание',
            'allowed_rubrics'       => 'разрешённые рубрики',
            'rate_limit_per_minute' => 'лимит запросов в минуту',
            'expires_at'            => 'срок действия',
        ];
    }
}
