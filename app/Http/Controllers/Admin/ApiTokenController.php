<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkApiTokenRequest;
use App\Http\Requests\Admin\StoreApiTokenRequest;
use App\Http\Requests\Admin\UpdateApiTokenRequest;
use App\Models\ApiToken;
use App\Models\Rubric;
use App\Models\Setting;
use App\Services\Logger;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiTokenController extends Controller
{
    public function index(Request $request)
    {
        [$canList, $canCreate, $canEdit, $canDelete] = $this->resolveCaps($request);

        $query = ApiToken::query();

        if ($q = trim((string) $request->input('q', ''))) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'ilike', "%{$q}%")
                  ->orWhere('description', 'ilike', "%{$q}%")
                  ->orWhere('token_prefix', 'ilike', "%{$q}%");
            });
        }

        $statusFilter = $request->input('status');

        if ($statusFilter === 'active') {
            $query->where('is_active', true)->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()));
        }

        if ($statusFilter === 'inactive') {
            $query->where('is_active', false);
        }

        if ($statusFilter === 'expired') {
            $query->whereNotNull('expires_at')->where('expires_at', '<=', now());
        }

        $sort = $request->input('sort', 'created');
        $dir = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $sortMap = [
            'created' => 'created_at',
            'name'    => 'name',
            'last'    => 'last_used_at',
            'hits'    => 'hits',
            'expires' => 'expires_at',
        ];
        $query->orderBy($sortMap[$sort] ?? 'created_at', $dir);

        $tokens = $query->paginate(50)->withQueryString();

        $stats = [
            'total'   => ApiToken::count(),
            'active'  => ApiToken::active()->count(),
            'expired' => ApiToken::whereNotNull('expires_at')->where('expires_at', '<=', now())->count(),
        ];

        $rubricsList = Rubric::orderBy('position')->orderBy('id')->get(['id', 'alias', 'title'])->all();
        $apiEnabled = Setting::getValue('api_enabled', '0') === '1';

        return view('admin.api-tokens.index', compact(
            'tokens',
            'stats',
            'rubricsList',
            'apiEnabled',
            'canList',
            'canCreate',
            'canEdit',
            'canDelete',
        ));
    }

    public function create(Request $request)
    {
        [, $canCreate] = $this->resolveCaps($request);

        if (!$canCreate) {
            abort(403);
        }

        $rubricsList = Rubric::where('api_enabled', true)
            ->orderBy('position')->orderBy('id')
            ->get(['id', 'alias', 'title', 'api_enabled'])
            ->all();

        $defaults = [
            'name'                  => '',
            'description'           => '',
            'allowed_rubrics'       => [],
            'rate_limit_per_minute' => 60,
            'is_active'             => true,
            'expires_at'            => now()->addMonth()->format('Y-m-d\TH:i'),
        ];

        return view('admin.api-tokens.edit', [
            'token'       => null,
            'defaults'    => $defaults,
            'rubricsList' => $rubricsList,
            'plainToken'  => null,
        ]);
    }

    public function store(StoreApiTokenRequest $request): JsonResponse
    {
        $data = $request->validated();

        $plain = ApiToken::generatePlain();

        $token = ApiToken::create([
            'name'                  => $data['name'],
            'description'           => $data['description'] ?? null,
            'token_hash'            => ApiToken::hashPlain($plain),
            'token_prefix'          => ApiToken::buildPrefix($plain),
            'allowed_rubrics'       => $data['allowed_rubrics'] ?? null,
            'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? 60,
            'is_active'             => $request->boolean('is_active', true),
            'expires_at'            => $data['expires_at'] ?? null,
        ]);

        Logger::adminAction('Создал API-токен', 'create', 'api_token', $token->id, $token->name);

        return response()->json([
            'ok'          => true,
            'message'     => 'Токен создан',
            'plain_token' => $plain,
            'token_id'    => $token->id,
            'redirect'    => route('admin.api-tokens.edit', $token),
        ]);
    }

    public function edit(Request $request, ApiToken $apiToken)
    {
        [, , $canEdit] = $this->resolveCaps($request);

        $allowedIds = array_map('intval', $apiToken->allowed_rubrics ?? []);
        $rubricsList = Rubric::query()
            ->where(function ($q) use ($allowedIds) {
                $q->where('api_enabled', true);

                if (!empty($allowedIds)) {
                    $q->orWhereIn('id', $allowedIds);
                }
            })
            ->orderBy('position')->orderBy('id')
            ->get(['id', 'alias', 'title', 'api_enabled'])
            ->all();

        $defaults = [
            'name'                  => $apiToken->name,
            'description'           => $apiToken->description,
            'allowed_rubrics'       => $apiToken->allowed_rubrics ?? [],
            'rate_limit_per_minute' => $apiToken->rate_limit_per_minute,
            'is_active'             => $apiToken->is_active,
            'expires_at'            => $apiToken->expires_at?->format('Y-m-d\TH:i'),
        ];

        $plainToken = $request->session()->pull('api_token_plain.' . $apiToken->id);

        return view('admin.api-tokens.edit', [
            'token'       => $apiToken,
            'defaults'    => $defaults,
            'rubricsList' => $rubricsList,
            'canEdit'     => $canEdit,
            'plainToken'  => $plainToken,
        ]);
    }

    public function update(UpdateApiTokenRequest $request, ApiToken $apiToken): JsonResponse
    {
        $data = $request->validated();

        $apiToken->update([
            'name'                  => $data['name'],
            'description'           => $data['description'] ?? null,
            'allowed_rubrics'       => $data['allowed_rubrics'] ?? null,
            'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? 60,
            'is_active'             => $request->boolean('is_active', true),
            'expires_at'            => $data['expires_at'] ?? null,
        ]);

        Logger::adminAction('Редактировал API-токен', 'edit', 'api_token', $apiToken->id, $apiToken->name);

        return response()->json(['ok' => true, 'message' => 'Токен обновлён']);
    }

    public function regenerate(\App\Http\Requests\Admin\RegenerateApiTokenRequest $request, ApiToken $apiToken): JsonResponse
    {
        $validated = $request->validated();

        $plain = ApiToken::generatePlain();
        $updates = [
            'token_hash'        => ApiToken::hashPlain($plain),
            'token_prefix'      => ApiToken::buildPrefix($plain),
            'secret_rotated_at' => now(),
        ];

        if (!empty($validated['expires_at'])) {
            $updates['expires_at'] = $validated['expires_at'];
        }

        $apiToken->update($updates);

        Logger::adminAction('Перегенерировал API-токен', 'edit', 'api_token', $apiToken->id, $apiToken->name);

        return response()->json([
            'ok'                => true,
            'message'           => 'Сгенерирован новый токен',
            'plain_token'       => $plain,
            'prefix'            => $apiToken->token_prefix,
            'expires_at'        => $apiToken->expires_at?->format('d.m.Y H:i'),
            'secret_rotated_at' => $apiToken->secret_rotated_at?->format('d.m.Y H:i'),
        ]);
    }

    public function destroy(ApiToken $apiToken): JsonResponse
    {
        $id = $apiToken->id;
        $name = $apiToken->name;
        $apiToken->delete();
        Logger::adminAction('Удалил API-токен', 'delete', 'api_token', $id, $name);

        return response()->json(['ok' => true, 'message' => 'Токен удалён']);
    }

    public function bulk(BulkApiTokenRequest $request): JsonResponse
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        $count = match ($action) {
            'activate'   => ApiToken::whereIn('id', $ids)->update(['is_active' => true]),
            'deactivate' => ApiToken::whereIn('id', $ids)->update(['is_active' => false]),
            'delete'     => ApiToken::whereIn('id', $ids)->delete(),
        };

        $messages = [
            'activate'   => "Активировано токенов: {$count}",
            'deactivate' => "Деактивировано токенов: {$count}",
            'delete'     => "Удалено токенов: {$count}",
        ];

        Logger::adminAction(
            "Массовое действие над API-токенами ({$action})",
            $action === 'delete' ? 'delete' : 'edit',
            'api_token',
            null,
            "Затронуто: {$count}",
        );

        return response()->json(['ok' => true, 'message' => $messages[$action], 'count' => $count]);
    }

    private function resolveCaps(Request $request): array
    {
        $user = $request->user('admin') ?? $request->user();

        return [
            $user?->hasPermission(Permission::API_TOKENS_LIST) ?? false,
            $user?->hasPermission(Permission::API_TOKENS_CREATE) ?? false,
            $user?->hasPermission(Permission::API_TOKENS_EDIT) ?? false,
            $user?->hasPermission(Permission::API_TOKENS_DELETE) ?? false,
        ];
    }
}
