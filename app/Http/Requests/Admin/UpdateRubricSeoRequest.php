<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRubricSeoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $changefreq = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];

        return [
            'sitemap_include'          => 'nullable|in:0,1',
            'sitemap_changefreq'       => 'nullable|string|in:' . implode(',', $changefreq),
            'sitemap_priority'         => 'nullable|numeric|min:0|max:1',
            'sitemap_index_changefreq' => 'nullable|string|in:' . implode(',', $changefreq),
            'sitemap_index_priority'   => 'nullable|numeric|min:0|max:1',
            'public_cache_disabled'    => 'nullable|in:0,1',
            'public_cache_ttl'         => 'nullable|integer|min:0|max:604800',
        ];
    }
}
