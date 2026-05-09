<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DashboardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $service)
    {
    }

    public function index(): View
    {
        return view('admin.dashboard');
    }

    public function stats(Request $request): JsonResponse
    {
        return response()->json($this->service->buildStats($request));
    }

    public function metric(Request $request): JsonResponse
    {
        $metric = $request->query('metric');
        $value = $this->service->resolveMetric($metric);

        if (!is_null($value)) {
            $request->session()->put("dashboard.metric.{$metric}", $value);
        }

        return response()->json(['value' => $value ?? '—']);
    }

    public function widgets(): JsonResponse
    {
        return response()->json($this->service->buildWidgets());
    }

    public function onlineUsers(): JsonResponse
    {
        return response()->json($this->service->onlineUsers());
    }
}
