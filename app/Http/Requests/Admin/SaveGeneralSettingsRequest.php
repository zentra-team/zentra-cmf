<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date_format'           => 'required|string|max:50',
            'time_format'           => 'required|string|max:50',
            'timezone'              => 'required|timezone',
            'page_404_id'           => 'nullable|integer|min:1',
            'message_403'           => 'nullable|string',
            'breadcrumbs_separator' => 'nullable|string|max:50',
        ];
    }
}
