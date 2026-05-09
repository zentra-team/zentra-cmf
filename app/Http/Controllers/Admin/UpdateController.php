<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PlatformUpdateChecker;
use App\Services\PlatformUpdater;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UpdateController extends Controller
{
    public function __construct(
        private PlatformUpdateChecker $checker,
        private PlatformUpdater $updater,
    ) {
    }

    public function check(Request $request): JsonResponse
    {
        $force = (bool) $request->query('force', false);
        $update = $this->checker->check($force);

        return response()->json([
            'current' => $this->checker->currentVersion(),
            'update'  => $update,
        ]);
    }

    public function perform(Request $request): JsonResponse
    {
        $update = $this->checker->check();

        if (!$update) {
            return response()->json(['ok' => false, 'message' => 'Нет доступных обновлений'], 422);
        }

        $result = $this->updater->update($update['download_url'], $update['version']);

        if ($result['ok']) {
            $this->checker->forget();
        }

        return response()->json($result, $result['ok'] ? 200 : 422);
    }
}
