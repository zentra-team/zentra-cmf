<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderRubricSubfieldsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'order' => 'порядок подполей',
        ];
    }
}
