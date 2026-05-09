<?php

namespace App\Services;

use App\Support\Format;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DatabaseService
{
    private string $backupDir = 'backups';

    public function getIndexData(): array
    {
        $backups = $this->getBackupList();
        $uploadLimitBytes = Format::phpIniSize(ini_get('upload_max_filesize'));
        $postLimitBytes = Format::phpIniSize(ini_get('post_max_size'));
        $effectiveLimit = min($uploadLimitBytes, $postLimitBytes);
        $diskFreeBytes = @disk_free_space(storage_path()) ?: null;

        return [
            'dbSize'         => $this->getDatabaseSize(),
            'backups'        => $backups,
            'lastBackup'     => $backups->first(),
            'phpUploadLimit' => Format::fileSize($effectiveLimit),
            'effectiveLimit' => $effectiveLimit,
            'diskFree'       => $diskFreeBytes !== null ? Format::fileSize((int) $diskFreeBytes) : null,
            'diskFreeColor'  => match (true) {
                $diskFreeBytes === null          => null,
                $diskFreeBytes >= 3 * 1024 ** 3 => '#5cbf8c',
                $diskFreeBytes >= 1 * 1024 ** 3 => '#c8a840',
                default                          => '#e06060',
            },
        ];
    }

    public function createDump(string $rawFilename, bool $saveLocal): array
    {
        $filename = $this->sanitizeFilename($rawFilename);
        $tmpPath = sys_get_temp_dir() . '/' . $filename;

        $result = $this->runPgDump($tmpPath);

        if ($result['error']) {
            return ['ok' => false, 'error' => 'Ошибка создания дампа: ' . $result['error']];
        }

        if (!file_exists($tmpPath) || filesize($tmpPath) === 0) {
            return ['ok' => false, 'error' => 'pg_dump завершился без ошибок, но файл не создан или пуст. Проверьте параметры подключения к БД.'];
        }

        if ($saveLocal) {
            $dir = storage_path('app/' . $this->backupDir);
            $dest = $dir . '/' . $filename;

            if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
                @unlink($tmpPath);

                return ['ok' => false, 'error' => "Не удалось создать директорию storage/app/{$this->backupDir}/. Проверьте права доступа."];
            }

            if (!is_writable($dir)) {
                @unlink($tmpPath);

                return ['ok' => false, 'error' => "Директория storage/app/{$this->backupDir}/ недоступна для записи."];
            }

            if (!copy($tmpPath, $dest)) {
                @unlink($tmpPath);

                return ['ok' => false, 'error' => "Не удалось скопировать файл в storage/app/{$this->backupDir}/. Путь: {$dest}"];
            }
        }

        return ['ok' => true, 'tmpPath' => $tmpPath, 'filename' => $filename];
    }

    public function restoreFromUploadedFile(\Illuminate\Http\UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        $tmpPath = $file->getPathname();

        if (str_ends_with($originalName, '.gz')) {
            $unpackedPath = $tmpPath . '_unpacked.sql';
            $error = $this->gunzipFile($tmpPath, $unpackedPath);

            if ($error) {
                @unlink($tmpPath);

                return ['ok' => false, 'message' => 'Ошибка распаковки архива. Убедитесь, что файл не повреждён.'];
            }

            $sqlPath = $unpackedPath;
        } else {
            $sqlPath = $tmpPath;
        }

        $validation = $this->validateDumpFile($sqlPath);

        if (!$validation['ok']) {
            if ($sqlPath !== $tmpPath) {
                @unlink($sqlPath);
            }

            return ['ok' => false, 'message' => $validation['message']];
        }

        $result = $this->runPsql($sqlPath);

        if ($sqlPath !== $tmpPath) {
            @unlink($sqlPath);
        }

        if ($result['error']) {
            return ['ok' => false, 'message' => 'Ошибка восстановления: ' . $result['error']];
        }

        return ['ok' => true, 'originalName' => $originalName];
    }

    public function restoreFromLocalBackup(string $filename): array
    {
        $filename = basename($filename);
        $path = $this->backupPath($filename);

        if (!file_exists($path)) {
            return ['ok' => false, 'message' => 'Файл не найден'];
        }

        if (str_ends_with($filename, '.gz')) {
            $unpackedPath = sys_get_temp_dir() . '/' . $filename . '_unpacked.sql';
            $error = $this->gunzipFile($path, $unpackedPath);

            if ($error) {
                return ['ok' => false, 'message' => 'Ошибка распаковки: ' . $error];
            }

            $result = $this->runPsql($unpackedPath);
            @unlink($unpackedPath);
        } else {
            $tmpPath = sys_get_temp_dir() . '/' . $filename;
            copy($path, $tmpPath);
            $result = $this->runPsql($tmpPath);
            @unlink($tmpPath);
        }

        if ($result['error']) {
            return ['ok' => false, 'message' => 'Ошибка восстановления: ' . $result['error']];
        }

        return ['ok' => true, 'message' => 'База данных восстановлена из ' . $filename];
    }

    public function deleteBackup(string $filename): array
    {
        $path = $this->backupPath(basename($filename));

        if (!file_exists($path)) {
            return ['ok' => false, 'message' => 'Файл не найден'];
        }

        unlink($path);

        return ['ok' => true, 'message' => 'Резервная копия удалена'];
    }

    public function backupPath(string $filename): string
    {
        return storage_path('app/' . $this->backupDir . '/' . basename($filename));
    }

    public function getStats(): array
    {
        $row = DB::selectOne("
            SELECT
                pg_size_pretty(pg_database_size(current_database())) AS db_size,
                version() AS pg_version,
                (SELECT count(*) FROM pg_stat_activity
                 WHERE datname = current_database()) AS connections,
                (SELECT round(100.0 * sum(blks_hit) / nullif(sum(blks_hit + blks_read), 0), 1)
                 FROM pg_stat_database WHERE datname = current_database()) AS cache_hit,
                (SELECT count(*) FROM information_schema.tables
                 WHERE table_schema = 'public' AND table_type = 'BASE TABLE') AS table_count,
                (SELECT coalesce(sum(n_live_tup), 0) FROM pg_stat_user_tables) AS total_rows,
                (SELECT coalesce(sum(n_dead_tup), 0) FROM pg_stat_user_tables) AS dead_rows,
                (SELECT count(*) FROM pg_stat_user_tables
                 WHERE n_dead_tup > greatest(n_live_tup, 1) * 0.1) AS tables_need_vacuum,
                (SELECT coalesce(sum(xact_commit + xact_rollback), 0)
                 FROM pg_stat_database WHERE datname = current_database()) AS transactions,
                (SELECT coalesce(sum(tup_returned + tup_fetched), 0)
                 FROM pg_stat_database WHERE datname = current_database()) AS tuples_returned
        ");

        preg_match('/PostgreSQL\s+[\d.]+/', $row->pg_version ?? '', $m);

        return [
            'db_size'            => $row->db_size,
            'pg_version'         => $m[0] ?? ($row->pg_version ?? '—'),
            'connections'        => (int) $row->connections,
            'cache_hit'          => $row->cache_hit !== null ? number_format((float) $row->cache_hit, 1) . '%' : '—',
            'table_count'        => (int) $row->table_count,
            'total_rows'         => number_format((int) $row->total_rows, 0, '.', ' '),
            'dead_rows'          => (int) $row->dead_rows,
            'dead_rows_fmt'      => number_format((int) $row->dead_rows, 0, '.', ' '),
            'tables_need_vacuum' => (int) $row->tables_need_vacuum,
            'transactions'       => number_format((int) $row->transactions, 0, '.', ' '),
        ];
    }

    public function getTablesList(): array
    {
        return DB::select("
            SELECT
                t.relname AS name,
                coalesce(s.n_live_tup, 0) AS live_rows,
                coalesce(s.n_dead_tup, 0) AS dead_rows,
                pg_size_pretty(pg_table_size(t.oid)) AS data_size,
                pg_size_pretty(pg_indexes_size(t.oid)) AS index_size,
                pg_size_pretty(pg_total_relation_size(t.oid)) AS total_size,
                pg_total_relation_size(t.oid) AS total_bytes,
                to_char(coalesce(s.last_vacuum, s.last_autovacuum), 'DD.MM.YY HH24:MI') AS last_vacuum,
                CASE WHEN s.last_vacuum IS NOT NULL THEN 'manual'
                     WHEN s.last_autovacuum IS NOT NULL THEN 'auto'
                     ELSE NULL END AS vacuum_type,
                to_char(coalesce(s.last_analyze, s.last_autoanalyze), 'DD.MM.YY HH24:MI') AS last_analyze,
                CASE WHEN s.last_analyze IS NOT NULL THEN 'manual'
                     WHEN s.last_autoanalyze IS NOT NULL THEN 'auto'
                     ELSE NULL END AS analyze_type,
                coalesce(s.seq_scan, 0) AS seq_scan,
                coalesce(s.idx_scan, 0) AS idx_scan
            FROM pg_class t
            JOIN pg_namespace n ON n.oid = t.relnamespace
            LEFT JOIN pg_stat_user_tables s ON s.relid = t.oid
            WHERE n.nspname = 'public' AND t.relkind = 'r'
            ORDER BY pg_total_relation_size(t.oid) DESC NULLS LAST
        ");
    }

    public function runMaintenance(string $type, ?string $table): array
    {
        $quotedTable = null;

        if ($table) {
            $exists = DB::selectOne(
                "SELECT 1 AS ok FROM information_schema.tables
                 WHERE table_schema = 'public' AND table_name = ? AND table_type = 'BASE TABLE'",
                [$table],
            );

            if (!$exists) {
                return ['ok' => false, 'message' => 'Таблица не найдена'];
            }

            $quotedTable = '"' . str_replace('"', '', $table) . '"';
        }

        if ($type === 'reindex' && !$table) {
            return ['ok' => false, 'message' => 'Укажите таблицу для REINDEX TABLE'];
        }

        $dbName = '"' . str_replace('"', '', config('database.connections.pgsql.database')) . '"';

        $sql = match ($type) {
            'vacuum'         => $quotedTable ? "VACUUM {$quotedTable}" : 'VACUUM',
            'vacuum_analyze' => $quotedTable ? "VACUUM ANALYZE {$quotedTable}" : 'VACUUM ANALYZE',
            'vacuum_full'    => $quotedTable ? "VACUUM FULL {$quotedTable}" : 'VACUUM FULL',
            'analyze'        => $quotedTable ? "ANALYZE {$quotedTable}" : 'ANALYZE',
            'reindex'        => "REINDEX TABLE {$quotedTable}",
            'reindex_db'     => "REINDEX DATABASE {$dbName}",
        };

        DB::statement($sql);

        if ($type === 'vacuum_full') {
            DB::statement($quotedTable ? "VACUUM ANALYZE {$quotedTable}" : 'VACUUM ANALYZE');
        }

        $labels = [
            'vacuum'         => 'VACUUM',
            'vacuum_analyze' => 'VACUUM ANALYZE',
            'vacuum_full'    => 'VACUUM FULL',
            'analyze'        => 'ANALYZE',
            'reindex'        => 'REINDEX TABLE',
            'reindex_db'     => 'REINDEX DATABASE',
        ];

        return [
            'ok'      => true,
            'message' => $labels[$type] . ' выполнен: ' . ($table ?? 'вся БД'),
            'label'   => $labels[$type],
            'target'  => $table ?? 'вся БД',
        ];
    }

    public function getBackupList(): Collection
    {
        $dir = storage_path('app/' . $this->backupDir);
        $files = array_merge(
            glob($dir . '/*.sql.gz') ?: [],
            glob($dir . '/*.sql') ?: [],
            glob($dir . '/*.dump') ?: [],
        );

        return collect($files)
            ->map(function ($path) {
                $size = filesize($path);
                $mtime = filemtime($path);

                return (object) [
                    'name'     => basename($path),
                    'size'     => Format::fileSize($size),
                    'size_raw' => $size,
                    'date'     => date('d.m.Y H:i', $mtime),
                    'mtime'    => $mtime,
                ];
            })
            ->sortByDesc('mtime')
            ->values();
    }

    private function getDatabaseSize(): string
    {
        try {
            $db = config('database.connections.pgsql.database');
            $size = DB::selectOne('SELECT pg_size_pretty(pg_database_size(?)) AS size', [$db]);

            return $size?->size ?? '—';
        } catch (\Throwable) {
            return '—';
        }
    }

    private function runPhpDump(string $outputPath): array
    {
        $sqlPath = $outputPath . '.tmp.sql';
        $error   = (new PhpPgDumper())->dumpToFile($sqlPath);

        if ($error) {
            return ['error' => $error];
        }

        if (!file_exists($sqlPath) || filesize($sqlPath) === 0) {
            @unlink($sqlPath);

            return ['error' => 'PHP-дамп создал пустой файл'];
        }

        // Try PHP gzip first (no exec needed)
        if (function_exists('gzopen')) {
            $gz = @gzopen($outputPath, 'wb9');

            if ($gz !== false) {
                $fh = fopen($sqlPath, 'rb');
                while (!feof($fh)) {
                    gzwrite($gz, fread($fh, 65536));
                }
                fclose($fh);
                gzclose($gz);
                @unlink($sqlPath);

                return ['error' => null, 'compressed' => true];
            }
        }

        // Try gzip binary
        $gzip = $this->findBinary('gzip');

        if ($gzip) {
            $cmd = sprintf('%s -c %s > %s 2>&1', escapeshellarg($gzip), escapeshellarg($sqlPath), escapeshellarg($outputPath));
            exec($cmd, $out, $code);
            @unlink($sqlPath);

            if ($code !== 0) {
                return ['error' => 'Ошибка gzip: ' . implode("\n", $out)];
            }

            return ['error' => null, 'compressed' => true];
        }

        rename($sqlPath, $outputPath);

        return ['error' => null, 'compressed' => false];
    }

    private function getPgDumpMajorVersion(string $binary): ?int
    {
        if (!function_exists('shell_exec')) {
            return null;
        }

        $out = (string) (shell_exec(escapeshellarg($binary) . ' --version 2>/dev/null') ?? '');

        if (preg_match('/\b(\d+)\.\d+/', $out, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    private function runPgDump(string $outputPath): array
    {
        $serverVersion = $this->getServerMajorVersion();
        $binary        = $this->findBinary('pg_dump', $serverVersion);

        // Fall back to PHP dump if no binary or version mismatch
        if (!$binary) {
            return $this->runPhpDump($outputPath);
        }

        if ($serverVersion !== null) {
            $dumpVersion = $this->getPgDumpMajorVersion($binary);

            if ($dumpVersion !== null && $dumpVersion !== $serverVersion) {
                return $this->runPhpDump($outputPath);
            }
        }

        if (!function_exists('exec')) {
            return $this->runPhpDump($outputPath);
        }

        $config = $this->dbConfig();

        $sqlPath = $outputPath . '.tmp.sql';
        $errPath = $sqlPath . '.err';

        $cmd = sprintf(
            'PGPASSWORD=%s %s --host=%s --port=%s --username=%s --no-password --format=plain %s > %s 2> %s',
            escapeshellarg($config['password']),
            escapeshellarg($binary),
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['database']),
            escapeshellarg($sqlPath),
            escapeshellarg($errPath),
        );

        exec($cmd, $output, $exitCode);

        if ($exitCode !== 0) {
            @unlink($sqlPath);
            $errMsg = file_exists($errPath) ? trim((string) file_get_contents($errPath)) : '';
            @unlink($errPath);

            return ['error' => $errMsg ?: 'pg_dump завершился с ошибкой (код ' . $exitCode . ')'];
        }

        @unlink($errPath);

        if (!file_exists($sqlPath) || filesize($sqlPath) === 0) {
            @unlink($sqlPath);

            return ['error' => 'pg_dump завершился без ошибок, но файл пустой'];
        }

        $gzip = $this->findBinary('gzip');

        if (!$gzip) {
            rename($sqlPath, $outputPath);

            return ['error' => null, 'compressed' => false];
        }

        $cmd2 = sprintf(
            '%s -c %s > %s 2>&1',
            escapeshellarg($gzip),
            escapeshellarg($sqlPath),
            escapeshellarg($outputPath),
        );

        exec($cmd2, $output2, $exitCode2);
        @unlink($sqlPath);

        if ($exitCode2 !== 0) {
            return ['error' => 'Ошибка gzip: ' . implode("\n", $output2)];
        }

        return ['error' => null, 'compressed' => true];
    }

    private function runPsql(string $inputPath): array
    {
        if (!function_exists('exec')) {
            return ['error' => 'Функция exec() отключена на сервере. Восстановление недоступно.'];
        }

        $config = $this->dbConfig();
        $binary = $this->findBinary('psql');

        if (!$binary) {
            return ['error' => 'psql не найден в системе'];
        }

        $cmd = sprintf(
            'PGPASSWORD=%s %s --host=%s --port=%s --username=%s --dbname=%s < %s 2>&1',
            escapeshellarg($config['password']),
            escapeshellarg($binary),
            escapeshellarg($config['host']),
            escapeshellarg($config['port']),
            escapeshellarg($config['username']),
            escapeshellarg($config['database']),
            escapeshellarg($inputPath),
        );

        exec($cmd, $output, $exitCode);

        return $exitCode === 0
            ? ['error' => null]
            : ['error' => implode("\n", $output)];
    }

    private function gunzipFile(string $gzPath, string $outPath): ?string
    {
        if (!function_exists('exec')) {
            return 'Функция exec() отключена на сервере.';
        }

        $gzip = $this->findBinary('gzip');

        if (!$gzip) {
            return 'gzip не найден в системе';
        }

        $cmd = sprintf('%s -dc %s > %s 2>&1', escapeshellarg($gzip), escapeshellarg($gzPath), escapeshellarg($outPath));
        exec($cmd, $output, $exitCode);

        return $exitCode === 0 ? null : implode("\n", $output);
    }

    private function validateDumpFile(string $path): array
    {
        $fh = @fopen($path, 'rb');

        if (!$fh) {
            return ['ok' => false, 'message' => 'Не удалось прочитать файл.'];
        }

        $header = fread($fh, 2048);
        fclose($fh);

        if ($header === false || strlen(trim($header)) === 0) {
            return ['ok' => false, 'message' => 'Файл пустой.'];
        }

        $isPgDump = str_contains($header, 'PostgreSQL database dump')
            || str_contains($header, 'pg_dump')
            || (str_starts_with(ltrim($header), '--') && str_contains($header, 'SET '));

        if (!$isPgDump) {
            return ['ok' => false, 'message' => 'Файл не является резервной копией PostgreSQL. Загрузите файл, созданный через pg_dump.'];
        }

        return ['ok' => true];
    }

    private function sanitizeFilename(string $filename): string
    {
        foreach (['.sql.gz', '.sql', '.dump'] as $ext) {
            if (str_ends_with($filename, $ext)) {
                $filename = substr($filename, 0, -strlen($ext));
                break;
            }
        }

        return $filename . '.sql.gz';
    }

    private function dbConfig(): array
    {
        return [
            'host'     => config('database.connections.pgsql.host', 'localhost'),
            'port'     => config('database.connections.pgsql.port', '5432'),
            'username' => config('database.connections.pgsql.username', 'postgres'),
            'password' => config('database.connections.pgsql.password', ''),
            'database' => config('database.connections.pgsql.database', 'postgres'),
        ];
    }

    private function getServerMajorVersion(): ?int
    {
        try {
            $row = DB::selectOne('SHOW server_version_num');
            $num = (int) ($row->server_version_num ?? 0);

            return $num > 0 ? (int) floor($num / 10000) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function findBinary(string $name, ?int $preferMajorVersion = null): ?string
    {
        $versionPaths = $preferMajorVersion !== null ? [
            "/usr/lib/postgresql/{$preferMajorVersion}/bin/{$name}",
            "/usr/pgsql-{$preferMajorVersion}/bin/{$name}",
            "/opt/homebrew/opt/postgresql@{$preferMajorVersion}/bin/{$name}",
            "/usr/local/opt/postgresql@{$preferMajorVersion}/bin/{$name}",
        ] : [];

        $genericPaths = [
            "/usr/lib/postgresql/17/bin/{$name}",
            "/usr/lib/postgresql/16/bin/{$name}",
            "/usr/lib/postgresql/15/bin/{$name}",
            "/usr/lib/postgresql/14/bin/{$name}",
            "/usr/pgsql-17/bin/{$name}",
            "/usr/pgsql-16/bin/{$name}",
            "/usr/pgsql-15/bin/{$name}",
            "/usr/pgsql-14/bin/{$name}",
            "/usr/local/pgsql/bin/{$name}",
            "/opt/homebrew/opt/postgresql@17/bin/{$name}",
            "/opt/homebrew/opt/postgresql@16/bin/{$name}",
            "/opt/homebrew/opt/postgresql@15/bin/{$name}",
            "/opt/homebrew/opt/postgresql@14/bin/{$name}",
            "/usr/local/opt/postgresql@17/bin/{$name}",
            "/usr/local/opt/postgresql@16/bin/{$name}",
            "/usr/local/opt/postgresql@15/bin/{$name}",
            "/usr/local/opt/postgresql@14/bin/{$name}",
            "/opt/homebrew/bin/{$name}",
            "/usr/local/bin/{$name}",
            "/usr/bin/{$name}",
            "/bin/{$name}",
        ];

        foreach (array_unique(array_merge($versionPaths, $genericPaths)) as $p) {
            if (file_exists($p) && is_executable($p)) {
                return $p;
            }
        }

        if (!function_exists('shell_exec')) {
            return null;
        }

        $which = trim(shell_exec('which ' . escapeshellarg($name) . ' 2>/dev/null') ?? '');

        return $which ?: null;
    }
}
