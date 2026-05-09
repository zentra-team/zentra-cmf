<?php

namespace App\Http\Requests\Admin;

use App\Services\FieldManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRubricFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['sometimes', 'string', 'max:255'],
            'type'  => ['sometimes', 'string', Rule::in(array_keys(app(FieldManager::class)->all()))],
        ];
    }

    public function messages(): array
    {
        return [
            'type.in' => 'Указан неизвестный тип поля.',
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'название',
            'type'  => 'тип поля',
        ];
    }
}
