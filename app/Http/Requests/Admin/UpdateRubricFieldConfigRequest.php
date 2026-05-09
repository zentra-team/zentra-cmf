<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRubricFieldConfigRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'default_value' => ['nullable', 'string'],
            'description'   => ['nullable', 'string'],
            'config'        => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        return [
            'default_value' => 'значение по умолчанию',
            'description'   => 'описание',
            'config'        => 'настройки поля',
        ];
    }
}
