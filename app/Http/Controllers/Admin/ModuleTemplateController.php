<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\ModuleTemplates;
use App\Services\ModuleManager;
use App\Support\PublicCacheInvalidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ModuleTemplateController extends Controller
{
    public function __construct(
        private readonly ModuleManager $manager,
        private readonly ModuleTemplates $templates,
    ) {
    }

    public function index(string $sysName): View
    {
        $module = $this->module($sysName);

        return view('admin.modules.templates', [
            'module'    => $module,
            'templates' => $this->templates->templates($sysName),
        ]);
    }

    public function show(string $sysName, string $view): JsonResponse
    {
        $this->module($sysName);

        $default = $this->templates->defaultContent($sysName, $view);

        if ($default === null) {
            return response()->json(['ok' => false, 'message' => 'Шаблон не найден'], 404);
        }

        return response()->json([
            'ok'         => true,
            'content'    => $this->templates->effectiveContent($sysName, $view),
            'default'    => $default,
            'overridden' => $this->templates->isOverridden($sysName, $view),
        ]);
    }

    public function save(Request $request, string $sysName, string $view): JsonResponse
    {
        $this->module($sysName);

        try {
            $this->templates->saveOverride($sysName, $view, (string) $request->input('content', ''));
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'message' => $e->getMessage()], 422);
        }

        PublicCacheInvalidator::flushPublicHttpCache();

        return response()->json(['ok' => true, 'message' => 'Шаблон сохранён']);
    }

    public function reset(string $sysName, string $view): JsonResponse
    {
        $this->module($sysName);

        $this->templates->resetOverride($sysName, $view);
        PublicCacheInvalidator::flushPublicHttpCache();

        return response()->json(['ok' => true, 'message' => 'Шаблон сброшен к стандартному']);
    }

    private function module(string $sysName): \App\Models\Module
    {
        $module = $this->manager->findModule($sysName);

        if (!$module) {
            abort(404, "Модуль «{$sysName}» не установлен");
        }

        return $module;
    }
}
