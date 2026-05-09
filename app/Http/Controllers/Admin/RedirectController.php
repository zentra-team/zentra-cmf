<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkRedirectRequest;
use App\Http\Requests\Admin\StoreRedirectRequest;
use App\Http\Requests\Admin\UpdateRedirectRequest;
use App\Models\Redirect;
use App\Models\RedirectMiss;
use App\Models\Setting;
use App\Services\Logger;
use App\Services\RedirectMatcher;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function index(Request $request)
    {
        [$canList, $canCreate, $canEdit, $canDelete, $canMisses] = $this->resolveCaps($request);

        $query = Redirect::query();

        if ($q = trim((string) $request->input('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('from_url', 'ilike', "%{$q}%")
                  ->orWhere('to_url', 'ilike', "%{$q}%")
                  ->orWhere('note', 'ilike', "%{$q}%");
            });
        }

        $statusFilter = $request->input('status');

        if ($statusFilter === 'active') {
            $query->where('is_active', true);
        }

        if ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($statusFilter === 'expired') {
            $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
        }

        $kindFilter = $request->input('kind');

        if ($kindFilter === 'direct') {
            $query->where('is_wildcard', false);
        }

        if ($kindFilter === 'wildcard') {
            $query->where('is_wildcard', true);
        }

        $typeFilter = (int) $request->input('type', 0);

        if (in_array($typeFilter, [301, 302], true)) {
            $query->where('type', $typeFilter);
        }

        $sort = $request->input('sort', 'created');
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $sortMap = [
            'created'  => 'created_at',
            'hits'     => 'hits',
            'last_hit' => 'last_hit_at',
            'priority' => 'priority',
            'from'     => 'from_url',
        ];
        $query->orderBy($sortMap[$sort] ?? 'created_at', $dir);

        $redirects = $query->paginate(50)->withQueryString();

        $stats = [
            'total'     => Redirect::count(),
            'active'    => Redirect::where('is_active', true)->count(),
            'wildcards' => Redirect::where('is_wildcard', true)->count(),
            'expired'   => Redirect::whereNotNull('expires_at')->where('expires_at', '<=', now())->count(),
            'misses'    => $canMisses ? RedirectMiss::count() : 0,
        ];

        $potentialCycles = $this->detectPotentialCycles($redirects->getCollection()->all());

        $logMissesEnabled = Setting::getValue('redirects_log_misses', '0') === '1';

        return view('admin.redirects.index', compact(
            'redirects',
            'stats',
            'potentialCycles',
            'logMissesEnabled',
            'canList',
            'canCreate',
            'canEdit',
            'canDelete',
            'canMisses',
        ));
    }

    public function create(Request $request)
    {
        [, $canCreate] = $this->resolveCaps($request);

        if (!$canCreate) {
            abort(403);
        }

        $defaults = [
            'from_url'              => $request->input('from_url', ''),
            'to_url'                => '',
            'type'                  => (int) Setting::getValue('redirects_default_type', '301'),
            'is_active'             => true,
            'priority'              => 0,
            'preserve_query_string' => true,
            'expires_at'            => null,
            'note'                  => '',
        ];

        return view('admin.redirects.edit', [
            'redirect'  => null,
            'defaults'  => $defaults,
            'canEdit'   => true,
            'canDelete' => false,
        ]);
    }

    public function store(StoreRedirectRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['preserve_query_string'] = $request->boolean('preserve_query_string', true);
        $data['priority'] = (int) ($data['priority'] ?? 0);

        $redirect = Redirect::create($data);

        Logger::adminAction('Создал редирект', 'create', 'redirect', $redirect->id, $redirect->from_url . ' → ' . $redirect->to_url);

        return response()->json([
            'ok'       => true,
            'message'  => 'Редирект создан',
            'redirect' => route('admin.redirects.edit', $redirect),
        ]);
    }

    public function edit(Request $request, Redirect $redirect)
    {
        [, , $canEdit, $canDelete] = $this->resolveCaps($request);

        return view('admin.redirects.edit', [
            'redirect' => $redirect,
            'defaults' => $redirect->only([
                'from_url', 'to_url', 'type', 'is_active', 'priority',
                'preserve_query_string', 'expires_at', 'note',
            ]),
            'canEdit'   => $canEdit,
            'canDelete' => $canDelete,
        ]);
    }

    public function update(UpdateRedirectRequest $request, Redirect $redirect): JsonResponse
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active', true);
        $data['preserve_query_string'] = $request->boolean('preserve_query_string', true);
        $data['priority'] = (int) ($data['priority'] ?? 0);

        $redirect->update($data);

        Logger::adminAction('Редактировал редирект', 'edit', 'redirect', $redirect->id, $redirect->from_url . ' → ' . $redirect->to_url);

        return response()->json(['ok' => true, 'message' => 'Редирект обновлён']);
    }

    public function destroy(Redirect $redirect): JsonResponse
    {
        [$id, $from, $to] = [$redirect->id, $redirect->from_url, $redirect->to_url];
        $redirect->delete();
        Logger::adminAction('Удалил редирект', 'delete', 'redirect', $id, $from . ' → ' . $to);

        return response()->json(['ok' => true, 'message' => 'Редирект удалён']);
    }

    public function bulk(BulkRedirectRequest $request): JsonResponse
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        $count = match ($action) {
            'activate'   => Redirect::whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => Redirect::whereIn('id', $ids)->update(['is_active' => false]),
            'delete'     => Redirect::whereIn('id', $ids)->delete(),
        };

        $messages = [
            'activate'   => "Активировано редиректов: {$count}",
            'deactivate' => "Деактивировано редиректов: {$count}",
            'delete'     => "Удалено редиректов: {$count}",
        ];

        Logger::adminAction(
            "Массовое действие над редиректами ({$action})",
            $action === 'delete' ? 'delete' : 'edit',
            'redirect',
            null,
            "Затронуто: {$count}",
        );

        return response()->json(['ok' => true, 'message' => $messages[$action], 'count' => $count]);
    }

    public function inspect(Request $request, RedirectMatcher $matcher): JsonResponse
    {
        $url = trim((string) $request->input('url', ''));

        if ($url === '') {
            return response()->json(['ok' => false, 'message' => 'Укажите URL для проверки'], 422);
        }

        $maxHops = max(1, (int) Setting::getValue('redirects_max_hops', '10'));
        $match = $matcher->match($url, $maxHops);

        if ($match === null) {
            return response()->json([
                'ok'      => true,
                'matched' => false,
                'message' => 'Ни один редирект не сработает для этого URL',
            ]);
        }

        return response()->json([
            'ok'      => true,
            'matched' => true,
            'to'      => $match['to'],
            'type'    => $match['type'],
            'hops'    => count($match['ids']),
            'ids'     => $match['ids'],
        ]);
    }

    public function misses(Request $request)
    {
        [, $canCreate, , $canDelete, $canMisses] = $this->resolveCaps($request);

        if (!$canMisses) {
            abort(403);
        }

        if (Setting::getValue('redirects_log_misses', '0') !== '1') {
            return view('admin.redirects.misses', [
                'misses'    => null,
                'enabled'   => false,
                'canCreate' => $canCreate,
                'canDelete' => $canDelete,
            ]);
        }

        $query = RedirectMiss::query();

        if ($q = trim((string) $request->input('q', ''))) {
            $query->where('url', 'ilike', "%{$q}%");
        }

        $sort = $request->input('sort', 'last');
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $sortMap = [
            'last'  => 'last_seen_at',
            'first' => 'first_seen_at',
            'hits'  => 'hits',
            'url'   => 'url',
        ];
        $query->orderBy($sortMap[$sort] ?? 'last_seen_at', $dir);

        $misses = $query->paginate(50)->withQueryString();

        return view('admin.redirects.misses', [
            'misses'    => $misses,
            'enabled'   => true,
            'canCreate' => $canCreate,
            'canDelete' => $canDelete,
        ]);
    }

    public function destroyMiss(Request $request, RedirectMiss $miss): JsonResponse
    {
        [$id, $url] = [$miss->id, $miss->url];
        $miss->delete();
        Logger::adminAction('Удалил запись из журнала битых ссылок', 'delete', 'redirect', $id, $url);

        return response()->json(['ok' => true, 'message' => 'Запись удалена']);
    }

    public function clearMisses(Request $request): JsonResponse
    {
        $count = RedirectMiss::query()->delete();
        Logger::adminAction("Очистил журнал битых ссылок ({$count})", 'delete', 'redirect', null, 'Битые ссылки');

        return response()->json(['ok' => true, 'message' => "Удалено записей: {$count}"]);
    }

    private function resolveCaps(Request $request): array
    {
        $user = $request->user('admin') ?? $request->user();

        return [
            $user?->hasPermission(Permission::REDIRECTS_LIST) ?? false,
            $user?->hasPermission(Permission::REDIRECTS_CREATE) ?? false,
            $user?->hasPermission(Permission::REDIRECTS_EDIT) ?? false,
            $user?->hasPermission(Permission::REDIRECTS_DELETE) ?? false,
            $user?->hasPermission(Permission::REDIRECTS_MISSES_VIEW) ?? false,
        ];
    }

    private function detectPotentialCycles(array $redirects): array
    {
        $activeFromUrls = Redirect::active()
            ->where('is_wildcard', false)
            ->pluck('from_url')
            ->flip()
            ->toArray();

        $flagged = [];

        foreach ($redirects as $r) {
            if (!$r->is_active) {
                continue;
            }
            $to = Redirect::normalizeUrl($r->to_url);

            if ($to === '' || preg_match('#^https?://#i', $to)) {
                continue;
            }

            if (isset($activeFromUrls[$to])) {
                $flagged[$r->id] = true;
            }
        }

        return $flagged;
    }
}
