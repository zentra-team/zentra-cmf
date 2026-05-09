<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BlockGroup extends Model
{
    protected $fillable = ['title', 'description', 'position'];

    public function blocks(): HasMany
    {
        return $this->hasMany(Block::class, 'group_id')->orderBy('position');
    }
}
