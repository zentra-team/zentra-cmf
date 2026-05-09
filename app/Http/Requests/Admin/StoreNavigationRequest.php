<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreNavigationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255|unique:navigations,title',
            'alias' => 'required|string|max:100|unique:navigations,alias|regex:/^[a-z0-9_]+$/',
        ];
    }

    public function messages(): array
    {
        return [
            'alias.regex'  => 'Алиас может содержать только латинские буквы, цифры и подчёркивания.',
            'alias.unique' => 'Меню с таким алиасом уже существует.',
            'title.unique' => 'Меню с таким названием уже существует.',
        ];
    }
}
