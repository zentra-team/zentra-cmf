<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkRedirectRequest;
use App\Http\Requests\Admin\StoreRedirectRequest;
use App\Http\Requests\Admin\UpdateRedirectRequest;
use App\Models\Redirect;
use App\Models\RedirectMiss;
use App\Services\Logger;
use App\Services\RedirectMatcher;
use App\Services\RedirectService;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RedirectController extends Controller
{
    public function __construct(private readonly RedirectService $redirectService) {}

    public function index(Request $request)
    {
        [$canList, $canCreate, $canEdit, $canDelete, $canMisses] = $this->resolveCaps($request);

        $redirects       = $this->redirectService->buildQuery($request)->paginate(50)->withQueryString();
        $stats           = $this->redirectService->stats($canMisses);
        $potentialCycles = $this->redirectService->detectPotentialCycles($redirects->getCollection()->all());
        $logMissesEnabled = $this->redirectService->logMissesEnabled();

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
            'type'                  => $this->redirectService->defaultType(),
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
        $data                           = $request->validated();
        $data['is_active']              = $request->boolean('is_active', true);
        $data['preserve_query_string']  = $request->boolean('preserve_query_string', true);
        $data['priority']               = (int) ($data['priority'] ?? 0);

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
        $data                          = $request->validated();
        $data['is_active']             = $request->boolean('is_active', true);
        $data['preserve_query_string'] = $request->boolean('preserve_query_string', true);
        $data['priority']              = (int) ($data['priority'] ?? 0);

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
        $ids    = $request->input('ids', []);
        $result = $this->redirectService->bulk($action, $ids);

        Logger::adminAction(
            "Массовое действие над редиректами ({$action})",
            $action === 'delete' ? 'delete' : 'edit',
            'redirect',
            null,
            "Затронуто: {$result['count']}",
        );

        return response()->json(['ok' => true, 'message' => $result['message'], 'count' => $result['count']]);
    }

    public function inspect(Request $request, RedirectMatcher $matcher): JsonResponse
    {
        $url = trim((string) $request->input('url', ''));

        if ($url === '') {
            return response()->json(['ok' => false, 'message' => 'Укажите URL для проверки'], 422);
        }

        $match = $matcher->match($url, $this->redirectService->maxHops());

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

        if (!$this->redirectService->logMissesEnabled()) {
            return view('admin.redirects.misses', [
                'misses'    => null,
                'enabled'   => false,
                'canCreate' => $canCreate,
                'canDelete' => $canDelete,
            ]);
        }

        $misses = $this->redirectService->buildMissesQuery($request)->paginate(50)->withQueryString();

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
        $count = $this->redirectService->clearMisses();
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
}
