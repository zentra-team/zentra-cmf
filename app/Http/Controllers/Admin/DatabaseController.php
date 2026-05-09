<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BackupDatabaseRequest;
use App\Http\Requests\Admin\MaintenanceRequest;
use App\Http\Requests\Admin\RestoreUploadRequest;
use App\Services\DatabaseService;
use App\Services\Logger;
use App\Support\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class DatabaseController extends Controller
{
    public function __construct(private readonly DatabaseService $service)
    {
    }

    public function index(Request $request): View
    {
        $user = $request->user('admin') ?? $request->user();

        $canBackup = $user?->hasPermission(Permission::DB_BACKUP) ?? false;
        $canRestore = $user?->hasPermission(Permission::DB_RESTORE) ?? false;
        $canOptimize = $user?->hasPermission(Permission::DB_OPTIMIZE) ?? false;

        return view('admin.database.index', [
            ...$this->service->getIndexData(),
            'canBackup'   => $canBackup,
            'canRestore'  => $canRestore,
            'canOptimize' => $canOptimize,
        ]);
    }

    public function backup(BackupDatabaseRequest $request): JsonResponse|BinaryFileResponse
    {
        $validated = $request->validated();
        $result = $this->service->createDump($validated['filename'], !empty($validated['save_local']));

        if (!$result['ok']) {
            return response()->json(['ok' => false, 'message' => $result['error']], 422);
        }

        return response()->download($result['tmpPath'], $result['filename'], [
            'Content-Type' => 'application/gzip',
        ])->deleteFileAfterSend(true);
    }

    public function restoreUpload(RestoreUploadRequest $request): JsonResponse
    {
        $result = $this->service->restoreFromUploadedFile($request->file('backup_file'));

        if (!$result['ok']) {
            return response()->json(['ok' => false, 'message' => $result['message']], 422);
        }

        Logger::adminAction('Восстановление БД из загруженного файла: ' . $result['originalName'], 'other', 'database');

        return response()->json(['ok' => true, 'message' => 'База данных успешно восстановлена из ' . $result['originalName']]);
    }

    public function restoreFromServer(string $filename): JsonResponse
    {
        if (!$this->validFilename($filename)) {
            abort(422, 'Недопустимое имя файла');
        }

        return response()->json($this->service->restoreFromLocalBackup($filename));
    }

    public function download(string $filename): BinaryFileResponse
    {
        if (!$this->validFilename($filename)) {
            abort(422, 'Недопустимое имя файла');
        }

        $path = $this->service->backupPath($filename);

        if (!file_exists($path)) {
            abort(404);
        }

        return response()->download($path, basename($filename));
    }

    public function deleteBackup(string $filename): JsonResponse
    {
        if (!$this->validFilename($filename)) {
            return response()->json(['ok' => false, 'message' => 'Недопустимое имя файла'], 422);
        }

        return response()->json($this->service->deleteBackup($filename));
    }

    private function validFilename(string $filename): bool
    {
        return (bool) preg_match('/^[a-zA-Z0-9_.\-]+\.sql(\.gz)?$/', $filename);
    }

    public function stats(): JsonResponse
    {
        try {
            return response()->json(['ok' => true, ...$this->service->getStats()]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function tables(): JsonResponse
    {
        try {
            return response()->json(['ok' => true, 'tables' => $this->service->getTablesList()]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()]);
        }
    }

    public function maintenance(MaintenanceRequest $request): JsonResponse
    {
        $validated = $request->validated();

        try {
            $result = $this->service->runMaintenance($validated['type'], $validated['table'] ?? null);

            if ($result['ok']) {
                Logger::adminAction(
                    "Обслуживание БД: {$result['label']}" . ($result['target'] !== 'вся БД' ? " [{$result['target']}]" : ''),
                    'other',
                    'database',
                );
            }

            return response()->json(['ok' => $result['ok'], 'message' => $result['message']]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => 'Ошибка: ' . $e->getMessage()]);
        }
    }
}
