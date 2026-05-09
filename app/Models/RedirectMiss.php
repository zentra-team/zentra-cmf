<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedirectMiss extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'url', 'hits', 'first_seen_at', 'last_seen_at', 'last_referer',
    ];

    protected $casts = [
        'hits'          => 'integer',
        'first_seen_at' => 'datetime',
        'last_seen_at'  => 'datetime',
    ];
}
