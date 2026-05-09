<?php

namespace App\Models;

use App\Services\ModuleManager;
use Illuminate\Database\Eloquent\Model;

class Module extends Model
{
    protected $table = 'modules';
    protected $primaryKey = 'sys_name';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'sys_name',
        'name',
        'description',
        'version',
        'is_active',
        'github',
        'tag',
        'has_admin_page',
        'has_front',
        'has_settings',
        'config',
        'installed_at',
    ];

    protected $casts = [
        'is_active'      => 'boolean',
        'has_admin_page' => 'boolean',
        'has_front'      => 'boolean',
        'has_settings'   => 'boolean',
        'config'         => 'array',
        'installed_at'   => 'datetime',
    ];

    public function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->config[$key] ?? $default;
    }

    public function setConfig(array $data): void
    {
        $this->config = array_merge($this->config ?? [], $data);
        $this->save();
    }

    public function getTagRegex(): ?string
    {
        if (!$this->tag) {
            return null;
        }

        return ModuleManager::generateTagRegex($this->tag);
    }

    public function getAdminUrl(): string
    {
        return route('admin.modules.dispatch', ['sys_name' => $this->sys_name]);
    }
}
