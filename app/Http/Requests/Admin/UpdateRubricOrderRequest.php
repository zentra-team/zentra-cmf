<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRubricOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order'   => ['required', 'array'],
            'order.*' => ['integer', 'exists:rubrics,id'],
        ];
    }
}
