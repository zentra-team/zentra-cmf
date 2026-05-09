<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Support\PublicCacheInvalidator;

class Navigation extends Model
{
    protected $fillable = [
        'title', 'alias', 'allowed_groups',
        'template_l1', 'link_tpl_l1',
        'template_l2', 'link_tpl_l2',
        'template_l3', 'link_tpl_l3',
        'position',
    ];

    protected $casts = [
        'allowed_groups' => 'array',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(NavigationItem::class)->orderBy('position');
    }

    public function rootItems(): HasMany
    {
        return $this->hasMany(NavigationItem::class)
            ->whereNull('parent_id')
            ->orderBy('position');
    }

    public function tag(): string
    {
        return '[nav:' . $this->alias . ']';
    }

    protected static function booted(): void
    {
        $invalidate = fn () => PublicCacheInvalidator::flushPublicHttpCache();
        static::saved($invalidate);
        static::deleted($invalidate);
    }
}
