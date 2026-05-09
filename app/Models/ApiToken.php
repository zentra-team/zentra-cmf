<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    public const TOKEN_PREFIX = 'zcm_';

    protected $fillable = [
        'name', 'description',
        'token_hash', 'token_prefix',
        'allowed_rubrics', 'rate_limit_per_minute',
        'is_active', 'expires_at', 'secret_rotated_at',
        'last_used_at', 'last_used_ip', 'hits',
        'metadata',
    ];

    protected $casts = [
        'allowed_rubrics'       => 'array',
        'metadata'              => 'array',
        'is_active'             => 'boolean',
        'rate_limit_per_minute' => 'integer',
        'hits'                  => 'integer',
        'expires_at'            => 'datetime',
        'secret_rotated_at'     => 'datetime',
        'last_used_at'          => 'datetime',
    ];

    protected $hidden = ['token_hash'];

    public static function generatePlain(): string
    {
        return self::TOKEN_PREFIX . Str::random(40);
    }

    public static function hashPlain(string $plain): string
    {
        return hash('sha256', $plain);
    }

    public static function buildPrefix(string $plain): string
    {
        $body = substr($plain, strlen(self::TOKEN_PREFIX));
        $head = substr($body, 0, 8);
        $tail = substr($body, -4);

        return self::TOKEN_PREFIX . $head . '...' . $tail;
    }

    public static function findByPlainToken(string $plain): ?self
    {
        if ($plain === '' || !str_starts_with($plain, self::TOKEN_PREFIX)) {
            return null;
        }

        return self::query()->where('token_hash', self::hashPlain($plain))->first();
    }

    public function canAccessRubric(int $rubricId): bool
    {
        $allowed = $this->allowed_rubrics;

        if (empty($allowed)) {
            return true;
        }

        return in_array($rubricId, array_map('intval', $allowed), true);
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isUsable(): bool
    {
        return $this->is_active && !$this->isExpired();
    }

    public function scopeActive(Builder $q): Builder
    {
        return $q->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }
}
