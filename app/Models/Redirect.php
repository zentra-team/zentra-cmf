<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use App\Support\PublicCacheInvalidator;

class Redirect extends Model
{
    protected $fillable = [
        'from_url', 'to_url', 'type', 'is_active', 'is_wildcard',
        'priority', 'preserve_query_string', 'expires_at',
        'hits', 'last_hit_at', 'note',
    ];

    protected $casts = [
        'is_active'             => 'boolean',
        'is_wildcard'           => 'boolean',
        'preserve_query_string' => 'boolean',
        'priority'              => 'integer',
        'type'                  => 'integer',
        'hits'                  => 'integer',
        'expires_at'            => 'datetime',
        'last_hit_at'           => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $r) {
            $r->from_url = self::normalizeUrl($r->from_url ?? '');
            $r->to_url = trim($r->to_url ?? '');

            $r->is_wildcard = str_contains($r->from_url, '*');
        });

        $invalidate = fn () => PublicCacheInvalidator::flushPublicHttpCache();
        static::saved($invalidate);
        static::deleted($invalidate);
    }

    public static function normalizeUrl(string $url): string
    {
        $url = trim($url);

        if ($url === '') {
            return '';
        }

        $url = preg_replace('/[?#].*$/', '', $url) ?? $url;

        if (!str_starts_with($url, '/') && !preg_match('#^https?://#i', $url)) {
            $url = '/' . ltrim($url, '/');
        }

        if (strlen($url) > 1) {
            $url = rtrim($url, '/');
        }

        return $url;
    }

    public function compiledPattern(): string
    {
        $parts = explode('*', $this->from_url);
        $escaped = array_map(fn ($p) => preg_quote($p, '#'), $parts);

        return '#^' . implode('(.*)', $escaped) . '$#u';
    }

    public function applyCaptures(array $captures): string
    {
        $to = $this->to_url;

        foreach ($captures as $i => $value) {
            $to = str_replace('$' . ($i + 1), $value, $to);
        }

        return $to;
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
