<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UserGroup extends Model
{
    protected $fillable = [
        'name',
        'description',
        'is_default',
        'permissions',
    ];

    protected function casts(): array
    {
        return [
            'is_default'  => 'boolean',
            'is_system'   => 'boolean',
            'permissions' => 'array',
        ];
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'group_id');
    }

    public function hasPermission(string $permission): bool
    {
        $perms = $this->permissions ?? [];

        if (in_array('all', $perms)) {
            return true;
        }

        return in_array($permission, $perms);
    }
}
