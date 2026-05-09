<?php

namespace App\Services;

use App\Models\ApiToken;
use App\Models\Rubric;
use App\Models\Setting;
use Illuminate\Http\Request;

class ApiTokenService
{
    public function list(Request $request): array
    {
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

        return ['tokens' => $tokens, 'stats' => $stats];
    }

    public function create(array $data): ApiToken
    {
        $plain = ApiToken::generatePlain();

        $token = ApiToken::create([
            'name'                  => $data['name'],
            'description'           => $data['description'] ?? null,
            'token_hash'            => ApiToken::hashPlain($plain),
            'token_prefix'          => ApiToken::buildPrefix($plain),
            'allowed_rubrics'       => $data['allowed_rubrics'] ?? null,
            'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? 60,
            'is_active'             => $data['is_active'] ?? true,
            'expires_at'            => $data['expires_at'] ?? null,
        ]);

        $token->plain_token = $plain;

        return $token;
    }

    public function update(ApiToken $token, array $data): void
    {
        $token->update([
            'name'                  => $data['name'],
            'description'           => $data['description'] ?? null,
            'allowed_rubrics'       => $data['allowed_rubrics'] ?? null,
            'rate_limit_per_minute' => $data['rate_limit_per_minute'] ?? 60,
            'is_active'             => $data['is_active'] ?? true,
            'expires_at'            => $data['expires_at'] ?? null,
        ]);
    }

    public function regenerate(ApiToken $token, array $data = []): ApiToken
    {
        $plain = ApiToken::generatePlain();
        $updates = [
            'token_hash'        => ApiToken::hashPlain($plain),
            'token_prefix'      => ApiToken::buildPrefix($plain),
            'secret_rotated_at' => now(),
        ];

        if (!empty($data['expires_at'])) {
            $updates['expires_at'] = $data['expires_at'];
        }

        $token->update($updates);
        $token->plain_token = $plain;

        return $token;
    }

    public function delete(ApiToken $token): void
    {
        $token->delete();
    }

    public function apiEnabled(): bool
    {
        return Setting::getValue('api_enabled', '0') === '1';
    }

    public function rubricsForIndex(): array
    {
        return Rubric::orderBy('position')->orderBy('id')->get(['id', 'alias', 'title'])->all();
    }

    public function rubricsForEdit(array $allowedIds = []): array
    {
        return Rubric::query()
            ->where(function ($q) use ($allowedIds) {
                $q->where('api_enabled', true);

                if (!empty($allowedIds)) {
                    $q->orWhereIn('id', $allowedIds);
                }
            })
            ->orderBy('position')->orderBy('id')
            ->get(['id', 'alias', 'title', 'api_enabled'])
            ->all();
    }

    public function bulk(string $action, array $ids): array
    {
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

        return ['ok' => true, 'message' => $messages[$action], 'count' => $count];
    }
}
