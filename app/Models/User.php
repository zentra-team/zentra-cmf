<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'group_id',
        'additional_groups',
        'is_active',
        'last_login_at',
        'last_login_ip',
        'last_seen_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'last_login_at'     => 'datetime',
            'additional_groups' => 'array',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(UserGroup::class, 'group_id');
    }

    public function hasPermission(string $permission): bool
    {
        if ($this->group?->hasPermission($permission)) {
            return true;
        }

        $additionalIds = array_filter((array) ($this->additional_groups ?? []));

        if (!empty($additionalIds)) {
            return UserGroup::whereIn('id', $additionalIds)
                ->get()
                ->some(fn (UserGroup $g) => $g->hasPermission($permission));
        }

        return false;
    }

    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }
}
