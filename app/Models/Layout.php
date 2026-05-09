<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use App\Support\PublicCacheInvalidator;

class Layout extends Model
{
    protected $fillable = ['title', 'content'];

    public function rubrics(): HasMany
    {
        return $this->hasMany(Rubric::class);
    }

    public function usedByRubrics(): Collection
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
        $invalidate = fn () => PublicCacheInvalidator::flushPublicHttpCache();
        static::saved($invalidate);
        static::deleted($invalidate);
    }
}
