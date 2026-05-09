<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SavePublicCacheSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'public_cache_enabled'            => 'nullable|in:0,1',
            'public_cache_default_ttl'        => 'nullable|integer|min:0|max:604800',
            'public_cache_query_strategy'     => 'nullable|string|in:ignore_all,include_all,whitelist,blacklist',
            'public_cache_query_blacklist'    => 'nullable|string|max:5000',
            'public_cache_query_whitelist'    => 'nullable|string|max:5000',
            'public_cache_skip_authenticated' => 'nullable|in:0,1',
            'public_cache_skip_with_csrf'     => 'nullable|in:0,1',
            'public_cache_skip_markers'       => 'nullable|string|max:5000',
            'public_cache_send_headers'       => 'nullable|in:0,1',
        ];
    }
}
