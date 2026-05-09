<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Layout extends Model
{
    protected $fillable = ['title', 'content'];

    public function rubrics(): HasMany
    {
        return $this->hasMany(Rubric::class);
    }

    public function usedByRubrics(): \Illuminate\Support\Collection
    {
        try {
            return DB::table('rubrics')
                ->where('layout_id', $this->id)
                ->pluck('title');
        } catch (\Throwable) {
            return collect();
        }
    }

    protected static function booted(): void
    {
        $invalidate = fn () => \App\Support\PublicCacheInvalidator::flushPublicHttpCache();
        static::saved($invalidate);
        static::deleted($invalidate);
    }
}
