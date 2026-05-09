<?php

namespace App\Services;

use App\Models\Module;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ModuleManager
{
    private string $modulesPath;

    public function __construct()
    {
        $this->modulesPath = base_path('modules');
    }

    public function indexData(): array
    {
        $installed    = Module::orderBy('name')->get();
        $diskModules  = $this->scanDisk();
        $installedKeys = $installed->pluck('sys_name')->toArray();
        $available = array_filter(
            $diskModules,
            fn ($info) => !in_array($info['sys_name'], $installedKeys, true),
        );

        return ['installed' => $installed, 'available' => $available];
    }

    public function scanDisk(): array
    {
        $modules = [];

        foreach (glob($this->modulesPath . '/*/module.json') ?: [] as $file) {
            $sysName = basename(dirname($file));
            $info = $this->readModuleJson($sysName);

            if ($info) {
                $modules[$info['sys_name']] = $info;
            }
        }

        return $modules;
    }

    public function readModuleJson(string $sysName): ?array
    {
        $file = "{$this->modulesPath}/{$sysName}/module.json";

        if (!file_exists($file)) {
            return null;
        }

        $data = json_decode(file_get_contents($file), true);

        if (!is_array($data)) {
            return null;
        }

        foreach (['sys_name', 'name', 'version'] as $field) {
            if (empty($data[$field])) {
                return null;
            }
        }

        if (!empty($data['tag']) && empty($data['tag_regex'])) {
            $data['tag_regex'] = self::generateTagRegex($data['tag']);
        }

        return $data;
    }

    public static function generateTagRegex(string $tag): string
    {
        $name = trim($tag, '[]');
        $escaped = preg_quote($name, '#');

        return "#\\[{$escaped}([^\\]]*)\\]#";
    }

    public function validateStructure(string $sysName): array
    {
        $errors = [];
        $path = "{$this->modulesPath}/{$sysName}";

        if (!is_dir($path)) {
            return ['ok' => false, 'errors' => ['Директория модуля не найдена']];
        }

        $info = $this->readModuleJson($sysName);

        if (!$info) {
            $errors[] = 'Файл module.json отсутствует или повреждён';
        } elseif ($info['sys_name'] !== $sysName) {
            $errors[] = "sys_name в module.json ({$info['sys_name']}) не совпадает с именем директории ({$sysName})";
        }

        return ['ok' => empty($errors), 'errors' => $errors];
    }

    public function install(string $sysName): array
    {
        $validation = $this->validateStructure($sysName);

        if (!$validation['ok']) {
            return ['ok' => false, 'message' => implode('; ', $validation['errors'])];
        }

        $info = $this->readModuleJson($sysName);

        if (Module::find($sysName)) {
            return ['ok' => false, 'message' => 'Модуль уже установлен. Используйте «Переустановить».'];
        }

        try {
            Module::create([
                'sys_name'       => $info['sys_name'],
                'name'           => $info['name'],
                'description'    => $info['description'] ?? null,
                'version'        => $info['version'],
                'is_active'      => true,
                'github'         => $info['github'] ?? null,
                'tag'            => $info['tag'] ?? null,
                'has_admin_page' => (bool) ($info['has_admin_page'] ?? false),
                'has_front'      => (bool) ($info['has_front'] ?? false),
                'has_settings'   => (bool) ($info['has_settings'] ?? false),
                'config'         => [],
                'installed_at'   => now(),
            ]);

            $this->runMigrations($sysName);

            Logger::adminAction(
                "Установил модуль «{$info['name']}» v{$info['version']}",
                'create',
                'module',
                null,
                $info['name'],
            );

            return ['ok' => true, 'message' => "Модуль «{$info['name']}» v{$info['version']} успешно установлен"];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Ошибка установки: ' . $e->getMessage()];
        }
    }

    public function uninstall(string $sysName): array
    {
        $module = Module::find($sysName);

        if (!$module) {
            return ['ok' => false, 'message' => 'Модуль не найден в базе данных'];
        }

        try {
            $this->rollbackMigrations($sysName);
            $name = $module->name;
            $module->delete();

            Cache::forget("module_update_{$sysName}");

            Logger::adminAction("Удалил модуль «{$name}»", 'delete', 'module', null, $name);

            return ['ok' => true, 'message' => "Модуль «{$name}» удалён"];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Ошибка удаления: ' . $e->getMessage()];
        }
    }

    public function reinstall(string $sysName): array
    {
        $info = $this->readModuleJson($sysName);

        if (!$info) {
            return ['ok' => false, 'message' => 'Не удалось прочитать module.json'];
        }

        $module = Module::find($sysName);

        if (!$module) {
            return $this->install($sysName);
        }

        try {
            $oldVersion = $module->version;

            $module->update([
                'name'           => $info['name'],
                'description'    => $info['description'] ?? null,
                'version'        => $info['version'],
                'github'         => $info['github'] ?? null,
                'tag'            => $info['tag'] ?? null,
                'has_admin_page' => (bool) ($info['has_admin_page'] ?? false),
                'has_front'      => (bool) ($info['has_front'] ?? false),
                'has_settings'   => (bool) ($info['has_settings'] ?? false),
            ]);

            $this->runMigrations($sysName);
            Cache::forget("module_update_{$sysName}");

            $msg = $info['version'] !== $oldVersion
                ? "Модуль «{$info['name']}» обновлён с v{$oldVersion} до v{$info['version']}"
                : "Модуль «{$info['name']}» переустановлен (v{$info['version']})";

            Logger::adminAction($msg, 'edit', 'module', null, $info['name']);

            return ['ok' => true, 'message' => $msg];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Ошибка переустановки: ' . $e->getMessage()];
        }
    }

    public function findModule(string $sysName): ?Module
    {
        return Module::find($sysName);
    }

    public function findActiveModule(string $sysName): ?Module
    {
        return Module::where('sys_name', $sysName)
            ->where('is_active', true)
            ->where('has_front', true)
            ->first();
    }

    public function toggle(string $sysName, bool $active): void
    {
        Module::where('sys_name', $sysName)->update(['is_active' => $active]);
    }

    public function uploadArchive(UploadedFile $file): array
    {
        $originalName = $file->getClientOriginalName();
        $isTarGz = str_ends_with($originalName, '.tar.gz');
        $isZip = str_ends_with($originalName, '.zip');

        if (!$isTarGz && !$isZip) {
            return ['ok' => false, 'message' => 'Поддерживаются только .zip и .tar.gz архивы'];
        }

        $tempDir = storage_path('app/temp');

        if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
            return ['ok' => false, 'message' => 'Не удалось создать временную директорию'];
        }

        $ext = $isTarGz ? 'tar.gz' : 'zip';
        $tempFile = $tempDir . '/module_upload_' . uniqid() . '.' . $ext;
        $file->move($tempDir, basename($tempFile));

        return $this->extractAndPrepare($tempFile, $isTarGz);
    }

    public function checkForUpdate(Module $module): ?array
    {
        if (!$module->github) {
            return null;
        }

        $cacheKey = "module_update_{$module->sys_name}";

        return Cache::remember($cacheKey, 3600, function () use ($module) {
            try {
                $headers = [
                    'User-Agent' => 'Zentra-CMF/1.0',
                    'Accept'     => 'application/vnd.github.v3+json',
                ];

                $token = config('services.github.token');

                if ($token) {
                    $headers['Authorization'] = "token {$token}";
                }

                $response = Http::withHeaders($headers)
                    ->timeout(10)
                    ->get("https://api.github.com/repos/{$module->github}/releases/latest");

                if (!$response->ok()) {
                    return null;
                }

                $data = $response->json();
                $latestVersion = ltrim($data['tag_name'] ?? '', 'v');

                if (!$latestVersion) {
                    return null;
                }

                if (version_compare($latestVersion, $module->version, '>')) {
                    return [
                        'version'      => $latestVersion,
                        'tag'          => $data['tag_name'],
                        'zipball_url'  => $data['zipball_url'] ?? null,
                        'changelog'    => $data['body'] ?? '',
                        'published_at' => $data['published_at'] ?? null,
                    ];
                }

                return null;
            } catch (\Throwable) {
                return null;
            }
        });
    }

    public function checkAllUpdates(): array
    {
        $updates = [];

        foreach (Module::whereNotNull('github')->get() as $module) {
            $update = $this->checkForUpdate($module);

            if ($update) {
                $updates[$module->sys_name] = $update;
            }
        }

        return $updates;
    }

    public function downloadFromGitHub(string $repo, string $tag): array
    {
        try {
            $headers = [
                'User-Agent' => 'Zentra-CMF/1.0',
                'Accept'     => 'application/vnd.github.v3+json',
            ];

            $token = config('services.github.token');

            if ($token) {
                $headers['Authorization'] = "token {$token}";
            }

            $metaResponse = Http::withHeaders($headers)
                ->timeout(10)
                ->get("https://api.github.com/repos/{$repo}/releases/tags/{$tag}");

            if (!$metaResponse->ok()) {
                $zipUrl = "https://github.com/{$repo}/archive/refs/tags/{$tag}.zip";
            } else {
                $zipUrl = $metaResponse->json()['zipball_url']
                    ?? "https://github.com/{$repo}/archive/refs/tags/{$tag}.zip";
            }

            $tempDir = storage_path('app/temp');

            if (!is_dir($tempDir) && !mkdir($tempDir, 0755, true) && !is_dir($tempDir)) {
                return ['ok' => false, 'message' => 'Не удалось создать временную директорию'];
            }

            $tempFile = $tempDir . '/gh_' . md5($repo . $tag) . '.zip';

            $response = Http::withHeaders($headers)->timeout(120)->get($zipUrl);

            if (!$response->ok()) {
                return ['ok' => false, 'message' => 'Не удалось скачать архив с GitHub'];
            }

            file_put_contents($tempFile, $response->body());

            return $this->extractAndPrepare($tempFile, false);
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Ошибка скачивания: ' . $e->getMessage()];
        }
    }

    public function runMigrations(string $sysName): void
    {
        $migrationsPath = base_path("modules/{$sysName}/migrations");

        if (!is_dir($migrationsPath)) {
            return;
        }

        $files = glob($migrationsPath . '/*.php') ?: [];
        sort($files);

        $module = Module::find($sysName);
        $ran = $module ? (array) ($module->config['migrations'] ?? []) : [];
        $newRan = [];

        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);

            if (in_array($name, $ran, true)) {
                continue;
            }

            $migration = require $file;

            if (is_object($migration) && method_exists($migration, 'up')) {
                $migration->up();
            }

            $newRan[] = $name;
        }

        if ($module !== null && !empty($newRan)) {
            $config = $module->config ?? [];
            $config['migrations'] = array_values(array_unique(array_merge($ran, $newRan)));
            $module->update(['config' => $config]);
        }
    }

    public function rollbackMigrations(string $sysName): void
    {
        $migrationsPath = base_path("modules/{$sysName}/migrations");

        if (!is_dir($migrationsPath)) {
            return;
        }

        $files = array_reverse(glob($migrationsPath . '/*.php') ?: []);

        foreach ($files as $file) {
            try {
                $migration = require $file;

                if (is_object($migration) && method_exists($migration, 'down')) {
                    $migration->down();
                }
            } catch (\Throwable) {
            }
        }
    }

    private function extractAndPrepare(string $archivePath, bool $isTarGz): array
    {
        $tempExtract = storage_path('app/temp/extract_' . uniqid());

        try {
            if (!mkdir($tempExtract, 0755, true) && !is_dir($tempExtract)) {
                return ['ok' => false, 'message' => 'Не удалось создать временную директорию для распаковки'];
            }

            if ($isTarGz) {
                $result = $this->extractTarGz($archivePath, $tempExtract);
            } else {
                $result = $this->extractZip($archivePath, $tempExtract);
            }

            if (!$result['ok']) {
                return $result;
            }

            $modulePath = $this->findModuleRoot($tempExtract);

            if (!$modulePath) {
                return ['ok' => false, 'message' => 'Файл module.json не найден в архиве'];
            }

            $info = json_decode(file_get_contents($modulePath . '/module.json'), true);

            if (!$info || empty($info['sys_name']) || empty($info['name']) || empty($info['version'])) {
                return ['ok' => false, 'message' => 'Некорректный module.json в архиве'];
            }

            $sysName = $info['sys_name'];
            $targetPath = "{$this->modulesPath}/{$sysName}";

            $existing = Module::find($sysName);

            $this->copyDirectory($modulePath, $targetPath);

            return [
                'ok'       => true,
                'sys_name' => $sysName,
                'name'     => $info['name'],
                'version'  => $info['version'],
                'existing' => $existing ? [
                    'version'       => $existing->version,
                    'is_newer'      => version_compare($info['version'], $existing->version, '>'),
                    'same_or_older' => version_compare($info['version'], $existing->version, '<='),
                ] : null,
            ];
        } catch (\Throwable $e) {
            return ['ok' => false, 'message' => 'Ошибка распаковки: ' . $e->getMessage()];
        } finally {
            $this->removeDirectory($tempExtract);
            @unlink($archivePath);
        }
    }

    private function findModuleRoot(string $extractPath): ?string
    {
        if (file_exists($extractPath . '/module.json')) {
            return $extractPath;
        }

        foreach (glob($extractPath . '/*/module.json') ?: [] as $found) {
            return dirname($found);
        }

        return null;
    }

    private function extractZip(string $file, string $dest): array
    {
        $zip = new \ZipArchive();

        if ($zip->open($file) !== true) {
            return ['ok' => false, 'message' => 'Не удалось открыть ZIP архив'];
        }

        $zip->extractTo($dest);
        $zip->close();

        return ['ok' => true];
    }

    private function extractTarGz(string $file, string $dest): array
    {
        if (!function_exists('exec')) {
            return ['ok' => false, 'message' => 'Функция exec() отключена на сервере. Используйте .zip архив модуля.'];
        }

        $cmd = sprintf('tar -xzf %s -C %s 2>&1', escapeshellarg($file), escapeshellarg($dest));
        $output = [];
        exec($cmd, $output, $code);

        if ($code !== 0) {
            return ['ok' => false, 'message' => 'Ошибка распаковки tar.gz: ' . implode(' ', $output)];
        }

        return ['ok' => true];
    }

    private function copyDirectory(string $src, string $dst): void
    {
        if (!is_dir($dst) && !mkdir($dst, 0755, true) && !is_dir($dst)) {
            throw new \RuntimeException("Не удалось создать директорию: {$dst}");
        }

        foreach (scandir($src) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $srcItem = "{$src}/{$item}";
            $dstItem = "{$dst}/{$item}";
            is_dir($srcItem) ? $this->copyDirectory($srcItem, $dstItem) : copy($srcItem, $dstItem);
        }
    }

    private function removeDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = "{$path}/{$item}";
            is_dir($full) ? $this->removeDirectory($full) : unlink($full);
        }

        rmdir($path);
    }
}
