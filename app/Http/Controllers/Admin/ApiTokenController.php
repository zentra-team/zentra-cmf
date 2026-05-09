<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkApiTokenRequest;
use App\Http\Requests\Admin\StoreApiTokenRequest;
use App\Http\Requests\Admin\UpdateApiTokenRequest;
use App\Models\ApiToken;
use App\Services\ApiTokenService;
use App\Services\Logger;
use App\Support\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApiTokenController extends Controller
{
    public function __construct(private ApiTokenService $apiTokenService)
    {
    }

    public function index(Request $request): View
    {
        [$canList, $canCreate, $canEdit, $canDelete] = $this->resolveCaps($request);

        ['tokens' => $tokens, 'stats' => $stats] = $this->apiTokenService->list($request);

        $rubricsList = $this->apiTokenService->rubricsForIndex();
        $apiEnabled  = $this->apiTokenService->apiEnabled();

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

    public function create(Request $request): View
    {
        [, $canCreate] = $this->resolveCaps($request);

        if (!$canCreate) {
            abort(403);
        }

        $rubricsList = $this->apiTokenService->rubricsForEdit();

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
        $data['is_active'] = $request->boolean('is_active', true);

        $token = $this->apiTokenService->create($data);

        Logger::adminAction('Создал API-токен', 'create', 'api_token', $token->id, $token->name);

        return response()->json([
            'ok'          => true,
            'message'     => 'Токен создан',
            'plain_token' => $token->plain_token,
            'token_id'    => $token->id,
            'redirect'    => route('admin.api-tokens.edit', $token),
        ]);
    }

    public function edit(Request $request, ApiToken $apiToken): View
    {
        [, , $canEdit] = $this->resolveCaps($request);

        $allowedIds  = array_map('intval', $apiToken->allowed_rubrics ?? []);
        $rubricsList = $this->apiTokenService->rubricsForEdit($allowedIds);

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
        $data['is_active'] = $request->boolean('is_active', true);

        $this->apiTokenService->update($apiToken, $data);

        Logger::adminAction('Редактировал API-токен', 'edit', 'api_token', $apiToken->id, $apiToken->name);

        return response()->json(['ok' => true, 'message' => 'Токен обновлён']);
    }

    public function regenerate(\App\Http\Requests\Admin\RegenerateApiTokenRequest $request, ApiToken $apiToken): JsonResponse
    {
        $validated = $request->validated();

        $apiToken = $this->apiTokenService->regenerate($apiToken, $validated);

        Logger::adminAction('Перегенерировал API-токен', 'edit', 'api_token', $apiToken->id, $apiToken->name);

        return response()->json([
            'ok'                => true,
            'message'           => 'Сгенерирован новый токен',
            'plain_token'       => $apiToken->plain_token,
            'prefix'            => $apiToken->token_prefix,
            'expires_at'        => $apiToken->expires_at?->format('d.m.Y H:i'),
            'secret_rotated_at' => $apiToken->secret_rotated_at?->format('d.m.Y H:i'),
        ]);
    }

    public function destroy(ApiToken $apiToken): JsonResponse
    {
        $id = $apiToken->id;
        $name = $apiToken->name;
        $this->apiTokenService->delete($apiToken);
        Logger::adminAction('Удалил API-токен', 'delete', 'api_token', $id, $name);

        return response()->json(['ok' => true, 'message' => 'Токен удалён']);
    }

    public function bulk(BulkApiTokenRequest $request): JsonResponse
    {
        $action = $request->input('action');
        $ids = $request->input('ids', []);

        $result = $this->apiTokenService->bulk($action, $ids);

        Logger::adminAction(
            "Массовое действие над API-токенами ({$action})",
            $action === 'delete' ? 'delete' : 'edit',
            'api_token',
            null,
            "Затронуто: {$result['count']}",
        );

        return response()->json($result);
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
