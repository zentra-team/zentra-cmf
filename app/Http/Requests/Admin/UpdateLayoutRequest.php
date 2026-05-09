<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'   => ['required', 'string', 'max:255', Rule::unique('layouts', 'title')->ignore($this->route('layout'))],
            'content' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'Макет с таким названием уже существует',
        ];
    }
}
