<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Support\PublicCacheInvalidator;

class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'key';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = ['key', 'value'];

    public static function getValue(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);

        return $setting !== null ? $setting->value : $default;
    }

    public static function setValue(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => (string) $value]);
    }

    public static function allAsArray(): array
    {
        return static::pluck('value', 'key')->toArray();
    }

    protected static function booted(): void
    {
        $invalidate = fn () => PublicCacheInvalidator::flushPublicHttpCache();
        static::saved($invalidate);
        static::deleted($invalidate);
    }
}
