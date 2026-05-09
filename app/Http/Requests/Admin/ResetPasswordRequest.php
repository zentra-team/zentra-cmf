<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ResetPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'token'                 => 'required',
            'email'                 => 'required|email',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'token.required'                 => 'Токен сброса пароля отсутствует.',
            'email.required'                 => 'Укажите email.',
            'email.email'                    => 'Некорректный формат email.',
            'password.required'              => 'Укажите новый пароль.',
            'password.min'                   => 'Пароль должен содержать не менее 8 символов.',
            'password.confirmed'             => 'Пароли не совпадают.',
            'password_confirmation.required' => 'Подтвердите пароль.',
        ];
    }
}
