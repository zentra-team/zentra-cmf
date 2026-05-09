<?php

namespace App\Http\Requests\Admin;

use App\Fields\RepeaterField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRubricSubfieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'         => ['sometimes', 'string', 'max:120'],
            'label'         => ['sometimes', 'nullable', 'string', 'max:120'],
            'type'          => ['sometimes', 'string', Rule::in(RepeaterField::ALLOWED_SUBTYPES)],
            'default_value' => ['sometimes', 'nullable', 'string'],
            'description'   => ['sometimes', 'nullable', 'string'],
            'config'        => ['sometimes', 'nullable', 'array'],
        ];
    }
}
