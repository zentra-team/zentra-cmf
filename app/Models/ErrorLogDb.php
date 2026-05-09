<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLogDb extends Model
{
    public $timestamps = false;

    protected $table = 'error_logs_db';

    protected $fillable = ['level', 'message', 'query', 'context'];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
