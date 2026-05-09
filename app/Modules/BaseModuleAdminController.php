<?php

namespace App\Modules;

use App\Http\Controllers\Controller;
use App\Models\Module;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

abstract class BaseModuleAdminController extends Controller
{
    protected Module $module;

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    abstract public function index(): mixed;

    public function settings(): mixed
    {
        abort(404, 'Страница настроек не реализована в этом модуле');
    }

    public function saveSettings(Request $request): JsonResponse
    {
        return response()->json(['ok' => false, 'message' => 'saveSettings не реализован'], 404);
    }

    public function handleRequest(Request $request, string $path): mixed
    {
        abort(404, "Маршрут «{$path}» не найден в модуле «{$this->module->sys_name}»");
    }

    protected function moduleView(string $view, array $data = []): View
    {
        $namespace = 'module.' . $this->module->sys_name . '.admin';
        $viewsPath = base_path("modules/{$this->module->sys_name}/views/admin");

        if (!view()->exists("{$namespace}::{$view}")) {
            view()->addNamespace($namespace, $viewsPath);
        }

        return view("{$namespace}::{$view}", array_merge($data, [
            'module' => $this->module,
        ]));
    }

    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->module->getConfig($key, $default);
    }

    protected function saveConfig(array $data): void
    {
        $this->module->setConfig($data);
    }

    protected function breadcrumbs(array $extra = []): array
    {
        $crumbs = [
            ['title' => 'Модули', 'url' => route('admin.modules.index')],
            ['title' => $this->module->name, 'url' => $this->module->getAdminUrl()],
        ];

        foreach ($extra as $crumb) {
            $crumbs[] = $crumb;
        }

        return $crumbs;
    }
}
