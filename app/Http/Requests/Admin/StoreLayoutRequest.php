<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreLayoutRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:layouts,title',
        ];
    }

    public function messages(): array
    {
        return [
            'title.unique' => 'Макет с таким названием уже существует',
        ];
    }
}
