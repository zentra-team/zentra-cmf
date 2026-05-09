<?php

namespace App\Http\Requests\Admin;

use App\Models\Redirect;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRedirectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'from_url' => Redirect::normalizeUrl((string) $this->input('from_url', '')),
            'to_url'   => trim((string) $this->input('to_url', '')),
        ]);
    }

    public function rules(): array
    {
        $redirectId = $this->route('redirect')?->id ?? $this->route('redirect');

        return [
            'from_url'              => 'required|string|max:500|unique:redirects,from_url,' . $redirectId,
            'to_url'                => 'required|string|max:500|different:from_url',
            'type'                  => 'required|integer|in:301,302',
            'is_active'             => 'boolean',
            'priority'              => 'nullable|integer|min:-1000|max:1000',
            'preserve_query_string' => 'boolean',
            'expires_at'            => 'nullable|date',
            'note'                  => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'from_url.unique'  => 'Редирект с таким исходным URL уже существует.',
            'to_url.different' => 'Целевой URL должен отличаться от исходного.',
        ];
    }
}
