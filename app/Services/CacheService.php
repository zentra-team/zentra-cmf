<?php

namespace App\Services;

use App\Support\Format;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class CacheService
{
    public function cacheStats(): array
    {
        $driver = config('cache.default', 'file');

        return match ($driver) {
            'file'     => $this->fileCacheStats(),
            'database' => $this->databaseCacheStats(),
            'redis'    => $this->redisCacheStats(),
            'array'    => [
                'driver' => 'array',
                'label'  => 'В памяти процесса',
                'value'  => '—',
                'hint'   => 'Тестовый драйвер: данные живут только в рамках одного запроса. Для продакшен окружения выберите "Файлы на сервере", "База данных" или "Redis".',
            ],
            'null' => [
                'driver' => 'null',
                'label'  => 'Отключён',
                'value'  => '—',
                'hint'   => 'Кеш выключён - ничего не сохраняется.',
            ],
            default => [
                'driver' => $driver,
                'label'  => $driver,
                'value'  => '—',
                'hint'   => null,
            ],
        };
    }

    public function sessionStats(): array
    {
        $driver = config('session.driver', 'file');

        return match ($driver) {
            'file'     => $this->fileSessionStats(),
            'database' => $this->databaseSessionStats(),
            'redis'    => $this->redisSessionStats(),
            'cookie'   => [
                'driver'    => 'cookie',
                'label'     => 'Cookie',
                'value'     => '—',
                'count'     => null,
                'supported' => false,
                'hint'      => 'Сессии хранятся у клиента в cookie, серверная очистка невозможна',
            ],
            'array' => [
                'driver'    => 'array',
                'label'     => 'В памяти процесса',
                'value'     => '—',
                'count'     => null,
                'supported' => false,
                'hint'      => 'Тестовый драйвер: сессия пропадёт после запроса. Для прод выберите file, database или redis.',
            ],
            'null' => [
                'driver'    => 'null',
                'label'     => 'Отключён',
                'value'     => '—',
                'count'     => null,
                'supported' => false,
                'hint'      => 'Сессии не сохраняются.',
            ],
            default => [
                'driver'    => $driver,
                'label'     => $driver,
                'value'     => '—',
                'count'     => null,
                'supported' => false,
                'hint'      => null,
            ],
        };
    }

    public function clearSessions(): array
    {
        $driver = config('session.driver', 'file');

        return match ($driver) {
            'file'     => $this->clearFileSessions(),
            'database' => $this->clearDatabaseSessions(),
            'redis'    => $this->clearRedisSessions(),
            default    => [
                'supported' => false,
                'driver'    => $driver,
                'count'     => 0,
                'message'   => "Драйвер сессий «{$driver}» не поддерживает серверную очистку",
            ],
        };
    }

    private function fileCacheStats(): array
    {
        $path = storage_path('framework/cache/data');

        return [
            'driver' => 'file',
            'label'  => 'Файлы',
            'value'  => Format::fileSize($this->dirSize($path)),
            'hint'   => null,
        ];
    }

    private function fileSessionStats(): array
    {
        $path = config('session.files') ?: storage_path('framework/sessions');

        return [
            'driver'    => 'file',
            'label'     => 'Файлы',
            'value'     => Format::fileSize($this->dirSize($path)),
            'count'     => $this->countRegularFiles($path),
            'supported' => true,
            'hint'      => null,
        ];
    }

    private function clearFileSessions(): array
    {
        $path = config('session.files') ?: storage_path('framework/sessions');
        $count = 0;

        if (is_dir($path)) {
            foreach (glob($path . '/*') ?: [] as $file) {
                if (is_file($file)) {
                    @unlink($file);
                    $count++;
                }
            }
        }

        return [
            'supported' => true,
            'driver'    => 'file',
            'count'     => $count,
            'message'   => "Удалено сессий: {$count}",
        ];
    }

    private function databaseCacheStats(): array
    {
        $table = config('cache.stores.database.table', 'cache');

        try {
            $count = (int) DB::table($table)->count();

            return [
                'driver' => 'database',
                'label'  => 'База данных',
                'value'  => $count . ' ' . $this->pluralize($count, ['запись', 'записи', 'записей']),
                'hint'   => "Таблица {$table}",
            ];
        } catch (\Throwable $e) {
            return [
                'driver' => 'database',
                'label'  => 'База данных',
                'value'  => '—',
                'hint'   => 'Таблица недоступна: ' . $e->getMessage(),
            ];
        }
    }

    private function databaseSessionStats(): array
    {
        $table = config('session.table', 'sessions');

        try {
            $count = (int) DB::table($table)->count();

            return [
                'driver'    => 'database',
                'label'     => 'База данных',
                'value'     => $count . ' ' . $this->pluralize($count, ['сессия', 'сессии', 'сессий']),
                'count'     => $count,
                'supported' => true,
                'hint'      => "Таблица {$table}",
            ];
        } catch (\Throwable $e) {
            return [
                'driver'    => 'database',
                'label'     => 'База данных',
                'value'     => '—',
                'count'     => null,
                'supported' => true,
                'hint'      => 'Таблица недоступна: ' . $e->getMessage(),
            ];
        }
    }

    private function clearDatabaseSessions(): array
    {
        $table = config('session.table', 'sessions');

        try {
            $count = (int) DB::table($table)->delete();

            return [
                'supported' => true,
                'driver'    => 'database',
                'count'     => $count,
                'message'   => "Удалено сессий из таблицы {$table}: {$count}",
            ];
        } catch (\Throwable $e) {
            return [
                'supported' => false,
                'driver'    => 'database',
                'count'     => 0,
                'message'   => 'Ошибка очистки сессий: ' . $e->getMessage(),
            ];
        }
    }

    private function redisCacheStats(): array
    {
        $conn = config('cache.stores.redis.connection', 'cache');

        try {
            $size = (int) Redis::connection($conn)->dbsize();

            return [
                'driver' => 'redis',
                'label'  => "Redis ({$conn})",
                'value'  => $size . ' ' . $this->pluralize($size, ['ключ', 'ключа', 'ключей']),
                'hint'   => null,
            ];
        } catch (\Throwable $e) {
            return [
                'driver' => 'redis',
                'label'  => 'Redis',
                'value'  => '—',
                'hint'   => 'Недоступно: ' . $e->getMessage(),
            ];
        }
    }

    private function redisSessionStats(): array
    {
        $conn = config('session.connection') ?: 'default';
        $shared = $this->redisSharedWithCache($conn);

        try {
            $size = (int) Redis::connection($conn)->dbsize();

            return [
                'driver'    => 'redis',
                'label'     => "Redis ({$conn})",
                'value'     => $size . ' ' . $this->pluralize($size, ['ключ', 'ключа', 'ключей']),
                'count'     => $size,
                'supported' => true,
                'shared'    => $shared,
                'hint'      => $shared
                    ? 'Общее подключение с кэшем приложения - очистка затронет и его'
                    : null,
            ];
        } catch (\Throwable $e) {
            return [
                'driver'    => 'redis',
                'label'     => 'Redis',
                'value'     => '—',
                'count'     => null,
                'supported' => false,
                'shared'    => $shared,
                'hint'      => 'Недоступно: ' . $e->getMessage(),
            ];
        }
    }

    private function clearRedisSessions(): array
    {
        $conn = config('session.connection') ?: 'default';

        try {
            $size = (int) Redis::connection($conn)->dbsize();
            Redis::connection($conn)->flushdb();

            return [
                'supported' => true,
                'driver'    => 'redis',
                'count'     => $size,
                'message'   => "Очищено Redis DB ({$conn}): {$size} " . $this->pluralize($size, ['ключ', 'ключа', 'ключей']),
            ];
        } catch (\Throwable $e) {
            return [
                'supported' => false,
                'driver'    => 'redis',
                'count'     => 0,
                'message'   => 'Ошибка очистки Redis: ' . $e->getMessage(),
            ];
        }
    }

    private function redisSharedWithCache(string $sessionConn): bool
    {
        if (config('cache.default') !== 'redis') {
            return false;
        }

        $cacheConn = config('cache.stores.redis.connection', 'cache');

        return $sessionConn === $cacheConn;
    }

    private function dirSize(string $path): int
    {
        if (!is_dir($path)) {
            return 0;
        }

        $bytes = 0;
        $it = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($it as $file) {
            if ($file->getFilename() === '.gitignore') {
                continue;
            }

            $bytes += $file->getSize();
        }

        return $bytes;
    }

    private function countRegularFiles(string $path): int
    {
        return is_dir($path) ? count(glob($path . '/*') ?: []) : 0;
    }

    private function pluralize(int $n, array $forms): string
    {
        $n = abs($n) % 100;
        $n1 = $n % 10;

        if ($n > 10 && $n < 20) {
            return $forms[2];
        }

        if ($n1 > 1 && $n1 < 5) {
            return $forms[1];
        }

        if ($n1 === 1) {
            return $forms[0];
        }

        return $forms[2];
    }
}
