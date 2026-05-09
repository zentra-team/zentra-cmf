<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ErrorLog404 extends Model
{
    public $timestamps = false;

    protected $table = 'error_logs_404';

    protected $fillable = ['ip', 'url', 'referer', 'user_agent'];

    protected $casts = [
        'created_at' => 'datetime',
    ];
}
