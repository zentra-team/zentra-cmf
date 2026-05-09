<?php

namespace App\Http\Requests\Install;

use Illuminate\Foundation\Http\FormRequest;

class ProcessStep1Request extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'accept' => ['accepted'],
        ];
    }
}
