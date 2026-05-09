<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BackupDatabaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'filename'   => ['required', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_\-\.]+$/'],
            'save_local' => ['nullable', 'boolean'],
        ];
    }
}
