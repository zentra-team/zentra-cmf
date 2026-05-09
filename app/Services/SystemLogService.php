<?php

namespace App\Services;

use App\Models\AdminLog;
use App\Models\ErrorLog404;
use App\Models\ErrorLogDb;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SystemLogService
{
    public const OBJECT_LABELS = [
        'block'        => 'Блок',
        'block_group'  => 'Группа блоков',
        'layout'       => 'Макет',
        'rubric'       => 'Рубрика',
        'rubric_field' => 'Поле рубрики',
        'nav_item'     => 'Пункт меню',
        'navigation'   => 'Навигация',
        'asset'        => 'Файл',
        'document'     => 'Документ',
        'request'      => 'Запрос',
        'user'         => 'Пользователь',
        'user_group'   => 'Группа пользователей',
        'settings'     => 'Настройки',
        'cache'        => 'Кэш',
        'database'     => 'База данных',
        'module'       => 'Модуль',
        'platform'     => 'Платформа',
    ];

    public function getObjectLabel(string $type): string
    {
        return self::OBJECT_LABELS[$type] ?? $type;
    }

    public function buildAdminQuery(Request $request): Builder
    {
        $query = AdminLog::orderByDesc('id');

        if ($s = $request->search) {
            $query->where('action', 'ilike', "%{$s}%");
        }

        if ($uid = $request->user_id) {
            $query->where('user_id', $uid);
        }

        if ($type = $request->action_type) {
            $query->where('action_type', $type);
        }

        if ($df = $request->date_from) {
            $query->whereDate('created_at', '>=', $df);
        }

        if ($dt = $request->date_to) {
            $query->whereDate('created_at', '<=', $dt);
        }

        return $query;
    }

    public function build404Query(Request $request): Builder
    {
        $query = ErrorLog404::orderByDesc('id');

        if ($s = $request->search_404) {
            $query->where('url', 'ilike', "%{$s}%");
        }

        if ($df = $request->date_from_404) {
            $query->whereDate('created_at', '>=', $df);
        }

        if ($dt = $request->date_to_404) {
            $query->whereDate('created_at', '<=', $dt);
        }

        return $query;
    }

    public function buildDbQuery(Request $request): Builder
    {
        $query = ErrorLogDb::orderByDesc('id');

        if ($lvl = $request->db_level) {
            $query->where('level', $lvl);
        }

        if ($s = $request->search_db) {
            $query->where('message', 'ilike', "%{$s}%");
        }

        return $query;
    }

    public function clearType(string $type): void
    {
        match ($type) {
            'admin' => AdminLog::truncate(),
            '404'   => ErrorLog404::truncate(),
            'db'    => ErrorLogDb::truncate(),
        };
    }

    public function usersForFilter(): Collection
    {
        return User::orderBy('name')->get(['id', 'name']);
    }

    public function dateTimeFormat(): string
    {
        return Setting::getValue('date_format', 'd.m.Y') . ' ' . Setting::getValue('time_format', 'H:i');
    }

    public function clearAll(): void
    {
        DB::table('admin_logs')->truncate();
        DB::table('error_logs_404')->truncate();
        DB::table('error_logs_db')->truncate();
    }
}
