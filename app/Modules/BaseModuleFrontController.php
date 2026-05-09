<?php

namespace App\Modules;

use App\Models\Module;
use Illuminate\Http\Request;

abstract class BaseModuleFrontController
{
    protected Module $module;

    protected array $context = [];

    public function __construct(Module $module)
    {
        $this->module = $module;
    }

    public function setContext(array $context): void
    {
        $this->context = $context;
    }

    abstract public function handle(string $params = ''): string;

    public function handleRequest(Request $request, string $path): mixed
    {
        abort(404, "Маршрут «{$path}» не найден в модуле «{$this->module->sys_name}»");
    }

    protected function moduleView(string $view, array $data = []): string
    {
        app(\App\Modules\ModuleTemplates::class)->registerFrontNamespace($this->module->sys_name);

        return view("module.{$this->module->sys_name}.front::{$view}", array_merge($data, [
            'module' => $this->module,
        ]))->render();
    }

    protected function getConfig(string $key, mixed $default = null): mixed
    {
        return $this->module->getConfig($key, $default);
    }
}
