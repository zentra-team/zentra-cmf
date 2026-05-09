<?php

namespace App\Http\Requests\Admin;

use App\Fields\RepeaterField;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRubricSubfieldRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:120'],
            'type'  => ['required', 'string', Rule::in(RepeaterField::ALLOWED_SUBTYPES)],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'название подполя',
            'type'  => 'тип подполя',
        ];
    }
}
