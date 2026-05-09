<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\LogFileParser;
use App\Services\SystemLogService;
use App\Support\Permission;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Models\User;

class SystemLogController extends Controller
{
    public function __construct(
        private readonly SystemLogService $logService,
        private readonly LogFileParser $logParser,
    ) {
    }

    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::guard('admin')->user();

        $canTabAdmin = $user->hasPermission(Permission::LOGS_TAB_ADMIN);
        $canTab404 = $user->hasPermission(Permission::LOGS_TAB_404);
        $canTabDb = $user->hasPermission(Permission::LOGS_TAB_DB);
        $canTabFramework = $user->hasPermission(Permission::LOGS_TAB_FRAMEWORK);
        $canExport = $user->hasPermission(Permission::LOGS_EXPORT);
        $canClear = $user->hasPermission(Permission::LOGS_CLEAR);

        if (!$canTabAdmin && !$canTab404 && !$canTabDb && !$canTabFramework) {
            abort(403, 'У вас нет доступа ни к одной вкладке раздела «События»');
        }

        $perPage = in_array((int) $request->per_page, [10, 25, 50, 100]) ? (int) $request->per_page : 25;

        $logFiles = $canTabFramework ? $this->logParser->getLogFiles() : [];
        $activeLogFile = $request->log_file ?? ($logFiles[0] ?? null);
        $fwLevelFilter = $request->fw_level ?? '';
        $frameworkLogs = ($canTabFramework && $activeLogFile) ? $this->logParser->parse($activeLogFile, $fwLevelFilter) : [];

        $adminLogs = $canTabAdmin
            ? $this->logService->buildAdminQuery($request)->paginate($perPage, ['*'], 'admin_page')->withQueryString()->appends(['tab' => 'admin'])
            : null;
        $logs404 = $canTab404
            ? $this->logService->build404Query($request)->paginate($perPage, ['*'], 'log404_page')->withQueryString()->appends(['tab' => '404'])
            : null;
        $logsDb = $canTabDb
            ? $this->logService->buildDbQuery($request)->paginate($perPage, ['*'], 'logdb_page')->withQueryString()->appends(['tab' => 'db'])
            : null;

        $users = $canTabAdmin ? $this->logService->usersForFilter() : collect();

        $defaultTab = match (true) {
            $canTabAdmin     => 'admin',
            $canTab404       => '404',
            $canTabDb        => 'db',
            $canTabFramework => 'framework',
        };

        return view('admin.logs.index', compact(
            'adminLogs',
            'logs404',
            'logsDb',
            'users',
            'logFiles',
            'activeLogFile',
            'frameworkLogs',
            'fwLevelFilter',
            'canTabAdmin',
            'canTab404',
            'canTabDb',
            'canTabFramework',
            'canExport',
            'canClear',
            'defaultTab',
        ));
    }

    public function downloadFrameworkLog(string $filename): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::guard('admin')->user();

        if (!$user->hasPermission(Permission::LOGS_TAB_FRAMEWORK)) {
            abort(403);
        }

        $filename = basename($filename);
        $path = storage_path('logs/' . $filename);

        if (!file_exists($path) || !str_ends_with($filename, '.log')) {
            abort(404);
        }

        return response()->download($path);
    }

    public function clearFrameworkLog(string $filename): JsonResponse
    {
        /** @var User $user */
        $user = Auth::guard('admin')->user();

        if (!$user->hasPermission(Permission::LOGS_TAB_FRAMEWORK)) {
            abort(403);
        }

        $filename = basename($filename);
        $path = storage_path('logs/' . $filename);

        if (!file_exists($path) || !str_ends_with($filename, '.log')) {
            return response()->json(['ok' => false, 'message' => 'Файл не найден']);
        }

        file_put_contents($path, '');

        return response()->json(['ok' => true, 'message' => 'Лог очищен']);
    }

    public function clear(string $type): JsonResponse
    {
        $this->ensureTabAccess($type);

        $this->logService->clearType($type);

        return response()->json(['ok' => true, 'message' => 'Журнал очищен']);
    }

    private function ensureTabAccess(string $type): void
    {
        $perm = match ($type) {
            'admin' => Permission::LOGS_TAB_ADMIN,
            '404'   => Permission::LOGS_TAB_404,
            'db'    => Permission::LOGS_TAB_DB,
            default => abort(404),
        };

        /** @var User $user */
        $user = Auth::guard('admin')->user();

        if (!$user->hasPermission($perm)) {
            abort(403);
        }
    }

    public function export(Request $request, string $type): StreamedResponse
    {
        $this->ensureTabAccess($type);

        [$rows, $headers, $filename] = match ($type) {
            'admin' => [
                $this->logService->buildAdminQuery($request)->get(),
                ['ID', 'Дата', 'Пользователь', 'IP', 'Тип', 'Действие', 'Объект'],
                'admin_log_' . now()->format('Y-m-d') . '.csv',
            ],
            '404' => [
                $this->logService->build404Query($request)->get(),
                ['ID', 'Дата', 'IP', 'URL', 'Referer', 'User-Agent'],
                'errors_404_' . now()->format('Y-m-d') . '.csv',
            ],
            'db' => [
                $this->logService->buildDbQuery($request)->get(),
                ['ID', 'Дата', 'Уровень', 'Сообщение', 'Запрос', 'Контекст'],
                'errors_db_' . now()->format('Y-m-d') . '.csv',
            ],
            default => abort(404),
        };

        $dtFormat = $this->logService->dateTimeFormat();

        $callback = function () use ($rows, $headers, $type, $dtFormat): void {
            $out = fopen('php://output', 'w');
            fprintf($out, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($out, $headers, ';');

            foreach ($rows as $row) {
                fputcsv($out, match ($type) {
                    'admin' => [
                        $row->id,
                        $row->created_at?->format($dtFormat),
                        $row->user_name,
                        $row->ip,
                        $row->action_type,
                        $row->action,
                        ($row->object_type ? $this->logService->getObjectLabel($row->object_type) : '') . ($row->object_title ? ': ' . $row->object_title : ''),
                    ],
                    '404' => [
                        $row->id,
                        $row->created_at?->format($dtFormat),
                        $row->ip,
                        $row->url,
                        $row->referer,
                        $row->user_agent,
                    ],
                    'db' => [
                        $row->id,
                        $row->created_at?->format($dtFormat),
                        $row->level,
                        $row->message,
                        $row->query,
                        $row->context,
                    ],
                }, ';');
            }

            fclose($out);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
