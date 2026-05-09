<?php

namespace App\Http\Requests\Admin;

use App\Rules\AssignableGroup;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('user');

        $rules = [
            'first_name'          => ['nullable', 'string', 'max:100'],
            'last_name'           => ['nullable', 'string', 'max:100'],
            'email'               => ['required', 'email', 'unique:users,email,' . $userId],
            'group_id'            => ['required', 'exists:user_groups,id', new AssignableGroup()],
            'additional_groups'   => ['nullable', 'array'],
            'additional_groups.*' => ['exists:user_groups,id', new AssignableGroup()],
            'is_active'           => ['boolean'],
        ];

        if ($this->boolean('is_new_user') || $this->filled('password')) {
            $rules['password'] = ['required', 'string', 'min:8'];
            $rules['password_confirmation'] = ['required', 'same:password'];
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'password.required'              => 'Пароль обязателен',
            'password.min'                   => 'Минимум 8 символов',
            'password_confirmation.required' => 'Введите подтверждение пароля',
            'password_confirmation.same'     => 'Пароли не совпадают',
        ];
    }
}
