<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email', 'unique:users,email'],
        ];
    }

    public function messages(): array
    {
        return [
            'email.unique' => 'Пользователь с таким email уже существует',
        ];
    }
}
