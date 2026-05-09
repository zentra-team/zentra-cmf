<?php

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

class ProcessStep3Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'app_url'   => 'required|url',
            'db_host'   => 'required|string',
            'db_port'   => 'required|integer|min:1|max:65535',
            'db_name'   => 'required|string',
            'db_user'   => 'required|string',
            'db_pass'   => 'nullable|string',
            'create_db' => 'nullable|boolean',
            'clean_db'  => 'nullable|boolean',
        ];
    }
}
