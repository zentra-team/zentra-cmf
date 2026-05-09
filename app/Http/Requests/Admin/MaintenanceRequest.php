<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class MaintenanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'type'  => ['required', 'string', 'in:vacuum,vacuum_analyze,vacuum_full,analyze,reindex,reindex_db'],
            'table' => ['nullable', 'string', 'max:100', 'regex:/^[a-zA-Z0-9_]+$/'],
        ];
    }
}
