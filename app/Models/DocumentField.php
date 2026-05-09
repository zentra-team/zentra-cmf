<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentField extends Model
{
    public $timestamps = false;

    protected $fillable = ['document_id', 'field_id', 'value'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    public function field(): BelongsTo
    {
        return $this->belongsTo(RubricField::class, 'field_id');
    }
}
