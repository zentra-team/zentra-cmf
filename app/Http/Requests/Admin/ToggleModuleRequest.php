<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ToggleModuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'sys_name' => ['required', 'string', 'alpha_dash', 'max:64'],
            'active'   => ['required', 'boolean'],
        ];
    }
}
