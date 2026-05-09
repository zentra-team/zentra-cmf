<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModuleSysNameRequest;
use App\Http\Requests\Admin\ToggleModuleRequest;
use App\Http\Requests\Admin\UpdateModuleRequest;
use App\Http\Requests\Admin\UploadModuleRequest;
use App\Models\Module;
use App\Services\ModuleManager;
use App\Services\TagProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use App\Support\PublicCacheInvalidator;

class ModuleController extends Controller
{
    public function __construct(
        private readonly ModuleManager $manager,
        private readonly TagProcessor $tagProcessor,
    ) {
    }

    public function index(): View
    {
        ['installed' => $installed, 'available' => $available] = $this->manager->indexData();

        return view('admin.modules.index', compact('installed', 'available'));
    }

    public function install(ModuleSysNameRequest $request): JsonResponse
    {
        $result = $this->manager->install($request->input('sys_name'));
        $this->flushTagCache();

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function uninstall(ModuleSysNameRequest $request): JsonResponse
    {
        $result = $this->manager->uninstall($request->input('sys_name'));
        $this->flushTagCache();

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function reinstall(ModuleSysNameRequest $request): JsonResponse
    {
        $result = $this->manager->reinstall($request->input('sys_name'));
        $this->flushTagCache();

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function toggle(ToggleModuleRequest $request): JsonResponse
    {
        $module = $this->manager->findModule($request->input('sys_name'));

        if (!$module) {
            return response()->json(['ok' => false, 'message' => 'Модуль не найден'], 422);
        }

        $this->manager->toggle($request->input('sys_name'), (bool) $request->input('active'));
        $this->flushTagCache();

        $state = $request->boolean('active') ? 'включён' : 'выключен';

        return response()->json(['ok' => true, 'message' => "Модуль «{$module->name}» {$state}"]);
    }

    public function upload(UploadModuleRequest $request): JsonResponse
    {
        $result = $this->manager->uploadArchive($request->file('archive'));

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function checkUpdates(): JsonResponse
    {
        $updates = $this->manager->checkAllUpdates();

        return response()->json($updates);
    }

    public function update(UpdateModuleRequest $request): JsonResponse
    {
        $download = $this->manager->downloadFromGitHub(
            $request->input('repo'),
            $request->input('tag'),
        );

        if (!$download['ok']) {
            return response()->json($download, 422);
        }

        $result = $this->manager->reinstall($request->input('sys_name'));
        $this->flushTagCache();

        return response()->json($result, $result['ok'] ? 200 : 422);
    }

    public function adminDispatch(Request $request, string $sysName, string $path = ''): mixed
    {
        $module = $this->manager->findModule($sysName);

        if (!$module) {
            abort(404, "Модуль «{$sysName}» не установлен");
        }

        if (!$module->is_active) {
            abort(403, "Модуль «{$module->name}» выключен");
        }

        if (!$module->has_admin_page) {
            abort(404, "Модуль «{$module->name}» не имеет страницы управления");
        }

        $controller = $this->loadModuleController($sysName, 'AdminController', $module, 500);
        $pathClean = trim($path, '/');
        $method = $request->method();

        return match (true) {
            $pathClean === '' && $method === 'GET'          => $controller->index(),
            $pathClean === 'settings' && $method === 'GET'  => $controller->settings(),
            $pathClean === 'settings' && $method === 'POST' => $controller->saveSettings($request),
            default                                         => $controller->handleRequest($request, $pathClean),
        };
    }

    public function frontDispatch(Request $request, string $sysName, string $path = ''): mixed
    {
        $module = $this->manager->findActiveModule($sysName);

        if (!$module) {
            abort(404);
        }

        $controller = $this->loadModuleController($sysName, 'FrontController', $module, 404);
        $pathClean = trim($path, '/');

        return $controller->handleRequest($request, $pathClean);
    }

    private function loadModuleController(string $sysName, string $type, Module $module, int $failCode): object
    {
        if (!preg_match('/^[a-z][a-z0-9_]{0,63}$/', $sysName)) {
            abort($failCode, 'Недопустимое имя модуля');
        }

        $file = base_path("modules/{$sysName}/Controllers/{$type}.php");

        if (!file_exists($file)) {
            abort($failCode, "{$type} модуля «{$sysName}» не найден на диске");
        }

        try {
            require_once $file;
        } catch (\Throwable $e) {
            abort(500, "Ошибка загрузки контроллера модуля «{$sysName}»: " . $e->getMessage());
        }

        $className = "Modules\\{$sysName}\\Controllers\\{$type}";

        if (!class_exists($className)) {
            abort($failCode, "Класс {$className} не определён в файле контроллера");
        }

        return new $className($module);
    }

    private function flushTagCache(): void
    {
        $this->tagProcessor->flushCache();
        PublicCacheInvalidator::flushPublicHttpCache();
    }
}
