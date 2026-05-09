<?php

namespace App\Http\Middleware;

use App\Models\UserGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminPermission
{
    public function handle(Request $request, \Closure $next, string $permission): Response
    {
        $user = Auth::guard('admin')->user();

        if ($user && $this->userHasPermission($user, $permission)) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => 'Нет прав доступа'], 403);
        }

        return redirect()->route('admin.dashboard')
            ->with('toast_error', 'Нет прав для выполнения этого действия');
    }

    private function userHasPermission(mixed $user, string $permission): bool
    {
        $group = $user->group;

        if ($group && $group->hasPermission($permission)) {
            return true;
        }

        $additionalIds = array_filter((array) ($user->additional_groups ?? []));

        if (!empty($additionalIds)) {
            return UserGroup::whereIn('id', $additionalIds)
                ->get()
                ->some(fn (UserGroup $g) => $g->hasPermission($permission));
        }

        return false;
    }
}
