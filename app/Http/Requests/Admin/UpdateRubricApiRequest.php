<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRubricApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'api_enabled'       => ['nullable', 'boolean'],
            'api_default_limit' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'api_max_limit'     => ['nullable', 'integer', 'min:1', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'api_enabled'       => 'API включён',
            'api_default_limit' => 'размер страницы по умолчанию',
            'api_max_limit'     => 'максимальный размер страницы',
        ];
    }
}
