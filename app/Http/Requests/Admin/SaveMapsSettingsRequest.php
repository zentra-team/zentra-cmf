<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveMapsSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'maps_provider'       => ['required', Rule::in(['yandex', 'google'])],
            'yandex_maps_api_key' => ['nullable', 'string', 'max:200'],
            'google_maps_api_key' => ['nullable', 'string', 'max:200'],
        ];
    }

    public function attributes(): array
    {
        return [
            'maps_provider'       => 'провайдер карт',
            'yandex_maps_api_key' => 'API-ключ Яндекс.Карт',
            'google_maps_api_key' => 'API-ключ Google Maps',
        ];
    }
}
