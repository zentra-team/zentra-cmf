<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ModuleSysNameRequest;
use App\Http\Requests\Admin\ToggleModuleRequest;
use App\Http\Requests\Admin\UpdateModuleRequest;
use App\Http\Requests\Admin\UploadModuleRequest;
use App\Models\Module;
use App\Services\CatalogClient;
use App\Services\ModuleManager;
use App\Services\TagProcessor;
use App\Support\PublicCacheInvalidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleController extends Controller
{
    public function __construct(
        private readonly ModuleManager $manager,
        private readonly TagProcessor $tagProcessor,
        private readonly CatalogClient $catalog,
    ) {
    }

    public function index(): View
    {
        ['installed' => $installed, 'available' => $available] = $this->manager->indexData();

        $excluded = array_merge(
            $installed->pluck('sys_name')->all(),
            array_column($available, 'sys_name'),
        );

        $catalog = array_values(array_filter(
            $this->catalog->modules(),
            fn (array $m) => !empty($m['sys_name']) && !in_array($m['sys_name'], $excluded, true),
        ));

        return view('admin.modules.index', compact('installed', 'available', 'catalog'));
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

    public function catalogInstall(ModuleSysNameRequest $request): JsonResponse
    {
        return $this->catalogDownloadInstall($request->input('sys_name'), false);
    }

    public function catalogUpdate(ModuleSysNameRequest $request): JsonResponse
    {
        return $this->catalogDownloadInstall($request->input('sys_name'), true);
    }

    public function checkCatalogUpdates(): JsonResponse
    {
        $installed = Module::all()->keyBy('sys_name');
        $updates = [];

        foreach ($this->catalog->modules() as $entry) {
            $sysName = $entry['sys_name'] ?? null;
            $version = (string) ($entry['version'] ?? '');

            if (!$sysName || $version === '' || !isset($installed[$sysName])) {
                continue;
            }

            if (version_compare($version, $installed[$sysName]->version, '>')) {
                $updates[$sysName] = ['version' => $version];
            }
        }

        return response()->json($updates);
    }

    private function catalogDownloadInstall(string $sysName, bool $update): JsonResponse
    {
        $entry = collect($this->catalog->modules())->firstWhere('sys_name', $sysName);

        if (!$entry) {
            return response()->json(['ok' => false, 'message' => 'Модуль не найден в каталоге'], 422);
        }

        if (!empty($entry['is_paid'])) {
            return response()->json(['ok' => false, 'message' => 'Платные модули пока недоступны к установке'], 422);
        }

        $url = (string) ($entry['download_url'] ?? '');

        if ($url === '') {
            return response()->json(['ok' => false, 'message' => 'У модуля нет файла для скачивания'], 422);
        }

        $result = $this->manager->installFromUrl($sysName, $url, $entry['checksum'] ?? null, $update);
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
