<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class SaveSeoSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url_suffix'       => 'nullable|string|in:,.htm,.html',
            'analytics_google' => 'nullable|string|max:50',
            'analytics_yandex' => 'nullable|string|max:20|regex:/^\d+$/',
            'head_code'        => 'nullable|string',
            'body_code'        => 'nullable|string',
            'robots_txt'       => 'nullable|string',

            'redirects_enabled'           => 'nullable|in:0,1',
            'redirects_use_alias_history' => 'nullable|in:0,1',
            'redirects_track_hits'        => 'nullable|in:0,1',
            'redirects_log_misses'        => 'nullable|in:0,1',
            'redirects_default_type'      => 'nullable|integer|in:301,302',
            'redirects_max_hops'          => 'nullable|integer|min:1|max:50',

            'sitemap_enabled'                => 'nullable|in:0,1',
            'sitemap_cache_ttl'              => 'nullable|integer|min:0|max:86400',
            'sitemap_include_homepage'       => 'nullable|in:0,1',
            'sitemap_include_rubric_indexes' => 'nullable|in:0,1',
            'sitemap_lastmod_source'         => 'nullable|string|in:updated_at,created_at,published_at',
            'sitemap_default_changefreq'     => 'nullable|string|in:always,hourly,daily,weekly,monthly,yearly,never',
            'sitemap_default_priority'       => 'nullable|numeric|min:0|max:1',
            'sitemap_max_urls_per_file'      => 'nullable|integer|min:100|max:50000',

            'rss_enabled'                => 'nullable|in:0,1',
            'rss_default_limit'          => 'nullable|integer|min:1|max:500',
            'rss_cache_ttl'              => 'nullable|integer|min:0|max:86400',
            'rss_description_max_length' => 'nullable|integer|min:0|max:5000',
            'rss_site_feed_enabled'      => 'nullable|in:0,1',
            'rss_site_feed_title'        => 'nullable|string|max:255',
            'rss_site_feed_description'  => 'nullable|string|max:1000',
            'rss_site_feed_limit'        => 'nullable|integer|min:1|max:500',

            'api_enabled'            => 'nullable|in:0,1',
            'api_domain'             => 'nullable|string|max:255',
            'api_url_prefix'         => 'nullable|string|max:50',
            'api_default_per_page'   => 'nullable|integer|min:1|max:1000',
            'api_max_per_page'       => 'nullable|integer|min:1|max:1000',
            'api_cache_ttl'          => 'nullable|integer|min:0|max:86400',
            'api_default_rate_limit' => 'nullable|integer|min:0|max:100000',
        ];
    }

    public function messages(): array
    {
        return [
            'analytics_yandex.regex' => 'Номер счётчика Яндекс.Метрики должен содержать только цифры (например: 12345678).',
        ];
    }
}
