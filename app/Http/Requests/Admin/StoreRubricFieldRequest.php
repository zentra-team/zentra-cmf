<?php

namespace App\Http\Requests\Admin;

use App\Services\FieldManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRubricFieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'type'  => ['required', 'string', Rule::in(array_keys(app(FieldManager::class)->all()))],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Введите название поля.',
            'type.required'  => 'Выберите тип поля.',
            'type.in'        => 'Указан неизвестный тип поля.',
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
