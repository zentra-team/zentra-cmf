<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RubricPermission extends Model
{
    protected $fillable = [
        'rubric_id', 'group_id',
        'can_view', 'can_all', 'can_create_moderated',
        'can_create', 'can_edit_own', 'can_edit_all', 'can_revisions',
    ];

    protected $casts = [
        'can_view'             => 'boolean',
        'can_all'              => 'boolean',
        'can_create_moderated' => 'boolean',
        'can_create'           => 'boolean',
        'can_edit_own'         => 'boolean',
        'can_edit_all'         => 'boolean',
        'can_revisions'        => 'boolean',
    ];

    public function rubric(): BelongsTo
    {
        return $this->belongsTo(Rubric::class);
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class);
    }
}
