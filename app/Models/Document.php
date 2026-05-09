<?php

namespace App\Models;

use App\Support\PublicCacheInvalidator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Document extends Model
{
    public const STATUS_DRAFT = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_MODERATION = 2;

    protected $fillable = [
        'rubric_id', 'author_id', 'title', 'alias', 'status', 'position', 'views',
        'nav_item_id', 'breadcrumb_title', 'parent_doc_id',
        'meta_title', 'meta_description', 'meta_robots',
        'og_title', 'og_description', 'og_image',
        'sitemap_changefreq', 'sitemap_priority',
        'public_cache_ttl', 'public_cache_disabled',
        'published_at', 'unpublished_at',
    ];

    protected $casts = [
        'status'                => 'integer',
        'position'              => 'integer',
        'views'                 => 'integer',
        'published_at'          => 'datetime',
        'unpublished_at'        => 'datetime',
        'public_cache_ttl'      => 'integer',
        'public_cache_disabled' => 'boolean',
    ];

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function fields(): HasMany
    {
        return $this->hasMany(DocumentField::class);
    }

    public function navItem(): BelongsTo
    {
        return $this->belongsTo(NavigationItem::class, 'nav_item_id');
    }

    public function parentDoc(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'parent_doc_id');
    }

    public function isScheduled(): bool
    {
        return $this->status === self::STATUS_ACTIVE
            && $this->published_at !== null
            && $this->published_at->isFuture();
    }

    public function statusLabel(): string
    {
        if ($this->isScheduled()) {
            return 'Запланирован';
        }

        return match ($this->status) {
            self::STATUS_ACTIVE     => 'Опубликован',
            self::STATUS_MODERATION => 'На модерации',
            default                 => 'Черновик',
        };
    }

    public function statusClass(): string
    {
        if ($this->isScheduled()) {
            return 'info';
        }

        return match ($this->status) {
            self::STATUS_ACTIVE     => 'success',
            self::STATUS_MODERATION => 'warning',
            default                 => 'secondary',
        };
    }

    public function fieldValue(int $fieldId): ?string
    {
        return $this->fields->firstWhere('field_id', $fieldId)?->value;
    }

    protected static function booted(): void
    {
        $invalidate = function (Document $doc) {
            PublicCacheInvalidator::flushForDocument((int) ($doc->rubric_id ?? 0));
        };
        static::saved($invalidate);
        static::deleted($invalidate);
    }
}
