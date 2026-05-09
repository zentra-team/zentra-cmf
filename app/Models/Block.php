<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Block extends Model
{
    protected $fillable = [
        'group_id', 'title', 'alias', 'description',
        'content', 'is_wysiwyg', 'position',
    ];

    protected $casts = [
        'is_wysiwyg' => 'boolean',
    ];

    public function group(): BelongsTo
    {
        return $this->belongsTo(BlockGroup::class, 'group_id');
    }

    public function tag(): string
    {
        return '[block:' . $this->alias . ']';
    }

    protected static function booted(): void
    {
        $invalidate = fn () => \App\Support\PublicCacheInvalidator::flushPublicHttpCache();
        static::saved($invalidate);
        static::deleted($invalidate);
    }
}
