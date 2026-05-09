<?php

namespace App\Models;

use App\Fields\FieldInterface;
use App\Services\FieldManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RubricField extends Model
{
    protected $fillable = [
        'rubric_id', 'alias', 'title', 'type',
        'position',
        'default_value', 'description', 'config',
        'in_api',
    ];

    protected $casts = [
        'position' => 'integer',
        'config'   => 'array',
        'in_api'   => 'boolean',
    ];

    protected static function booted(): void
    {
        $invalidate = function (RubricField $field) {
            $rubricId = (int) ($field->rubric_id ?? 0);

            if ($rubricId > 0) {
                \App\Support\PublicCacheInvalidator::flushForDocument($rubricId);
            }
        };
        static::saved($invalidate);
        static::deleted($invalidate);
    }

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function fieldInstance(): ?FieldInterface
    {
        return app(FieldManager::class)->instance($this->type);
    }

    public function typeName(): string
    {
        return $this->fieldInstance()?->getName() ?? $this->type;
    }
}
