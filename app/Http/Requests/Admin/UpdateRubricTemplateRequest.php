<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRubricTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return ['template' => 'шаблон'];
    }
}
