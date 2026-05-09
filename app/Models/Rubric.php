<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Rubric extends Model
{
    protected $fillable = [
        'title', 'alias', 'layout_id', 'template',
        'description', 'color', 'position',
        'sitemap_include', 'sitemap_changefreq', 'sitemap_priority',
        'sitemap_index_changefreq', 'sitemap_index_priority',
        'rss_enabled', 'rss_title', 'rss_description', 'rss_limit',
        'rss_description_field_id', 'rss_image_field_id', 'rss_category_field_id',
        'public_cache_ttl', 'public_cache_disabled',
        'api_enabled', 'api_default_limit', 'api_max_limit',
    ];

    protected $casts = [
        'position'               => 'integer',
        'sitemap_include'        => 'boolean',
        'sitemap_priority'       => 'float',
        'sitemap_index_priority' => 'float',
        'rss_enabled'            => 'boolean',
        'rss_limit'              => 'integer',
        'public_cache_ttl'       => 'integer',
        'public_cache_disabled'  => 'boolean',
        'api_enabled'            => 'boolean',
        'api_default_limit'      => 'integer',
        'api_max_limit'          => 'integer',
    ];

    public function layout(): BelongsTo
    {
        return $this->belongsTo(Layout::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(RubricField::class)->orderBy('position');
    }

    public function permissions(): HasMany
    {
        return $this->hasMany(RubricPermission::class);
    }

    public function rssDescriptionField(): BelongsTo
    {
        return $this->belongsTo(RubricField::class, 'rss_description_field_id');
    }

    public function rssImageField(): BelongsTo
    {
        return $this->belongsTo(RubricField::class, 'rss_image_field_id');
    }

    public function rssCategoryField(): BelongsTo
    {
        return $this->belongsTo(RubricField::class, 'rss_category_field_id');
    }

    public function docsCount(): int
    {
        try {
            return \Illuminate\Support\Facades\DB::table('documents')
                ->where('rubric_id', $this->id)
                ->count();
        } catch (\Throwable) {
            return 0;
        }
    }

    public function hasDocuments(): bool
    {
        return $this->docsCount() > 0;
    }

    protected static function booted(): void
    {
        $invalidate = function (Rubric $r) {
            \App\Support\PublicCacheInvalidator::flushForDocument((int) $r->id);
        };
        static::saved($invalidate);
        static::deleted($invalidate);
    }
}
