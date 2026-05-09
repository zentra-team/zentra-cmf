<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DocsRequest extends Model
{
    protected $table = 'requests';

    protected $fillable = [
        'title',
        'alias',
        'description',
        'rubric_ids',
        'sort_field',
        'sort_system',
        'sort_order',
        'fetch_mode',
        'limit',
        'show_pagination',
        'per_page',
        'exclude_current',
        'cache_time',
        'conditions',
        'template_main',
        'template_item',
    ];

    protected function casts(): array
    {
        return [
            'show_pagination' => 'boolean',
            'exclude_current' => 'boolean',
            'conditions'      => 'array',
            'rubric_ids'      => 'array',
        ];
    }

    public function rubrics()
    {
        $ids = $this->rubric_ids ?? [];

        return Rubric::whereIn('id', $ids)->get();
    }

    public function getFirstRubricIdAttribute(): ?int
    {
        return ($this->rubric_ids ?? [])[0] ?? null;
    }

    public function getFirstRubricAttribute(): ?Rubric
    {
        $id = $this->first_rubric_id;

        return $id ? Rubric::find($id) : null;
    }

    public function tag(): string
    {
        return '[request:' . $this->alias . ']';
    }
}
