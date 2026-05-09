<?php

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

class ProcessStep4Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'                  => 'required|string|max:100',
            'email'                 => 'required|email|max:255',
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ];
    }
}
