<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NavigationItem extends Model
{
    protected $fillable = [
        'navigation_id', 'parent_id', 'title', 'url', 'target',
        'css_class', 'css_id', 'css_style', 'description', 'image', 'icon', 'extra_html',
        'position', 'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function navigation(): BelongsTo
    {
        return $this->belongsTo(Navigation::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(NavigationItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(NavigationItem::class, 'parent_id')->orderBy('position');
    }

    public function allChildren(): HasMany
    {
        return $this->children()->with('allChildren');
    }
}
