<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SendTestEmailRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'test_email'        => ['required', 'email'],
            'MAIL_MAILER'       => ['nullable', 'in:smtp,log'],
            'MAIL_HOST'         => ['nullable', 'string', 'max:255'],
            'MAIL_PORT'         => ['nullable', 'integer', 'between:1,65535'],
            'MAIL_USERNAME'     => ['nullable', 'string', 'max:255'],
            'MAIL_PASSWORD'     => ['nullable', 'string', 'max:255'],
            'MAIL_FROM_ADDRESS' => ['nullable', 'email'],
            'MAIL_FROM_NAME'    => ['nullable', 'string', 'max:255'],
        ];
    }
}
