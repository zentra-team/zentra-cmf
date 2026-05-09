<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class MediaFile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'uuid',
        'kind',
        'storage_path',
        'original_name',
        'mime',
        'size',
        'uploaded_by',
        'created_at',
    ];

    protected $casts = [
        'size'       => 'integer',
        'created_at' => 'datetime',
    ];

    public static function boot(): void
    {
        parent::boot();
        static::creating(function (MediaFile $f) {
            if (empty($f->uuid)) {
                $f->uuid = (string) Str::uuid();
            }

            if (empty($f->created_at)) {
                $f->created_at = now();
            }
        });
    }

    public function absolutePath(): string
    {
        return storage_path('app/' . $this->storage_path);
    }

    public function publicUrl(): string
    {
        return '/media/' . $this->uuid;
    }
}
