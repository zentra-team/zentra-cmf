<?php

namespace App\Services;

use App\Models\Redirect;
use App\Models\RedirectMiss;
use App\Models\Setting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class RedirectService
{
    public function buildQuery(Request $request): Builder
    {
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

        $sort    = $request->input('sort', 'created');
        $dir     = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $sortMap = [
            'created'  => 'created_at',
            'hits'     => 'hits',
            'last_hit' => 'last_hit_at',
            'priority' => 'priority',
            'from'     => 'from_url',
        ];

        $query->orderBy($sortMap[$sort] ?? 'created_at', $dir);

        return $query;
    }

    public function stats(bool $canMisses): array
    {
        return [
            'total'     => Redirect::count(),
            'active'    => Redirect::where('is_active', true)->count(),
            'wildcards' => Redirect::where('is_wildcard', true)->count(),
            'expired'   => Redirect::whereNotNull('expires_at')->where('expires_at', '<=', now())->count(),
            'misses'    => $canMisses ? RedirectMiss::count() : 0,
        ];
    }

    public function defaultType(): int
    {
        return (int) Setting::getValue('redirects_default_type', '301');
    }

    public function logMissesEnabled(): bool
    {
        return Setting::getValue('redirects_log_misses', '0') === '1';
    }

    public function maxHops(): int
    {
        return max(1, (int) Setting::getValue('redirects_max_hops', '10'));
    }

    public function bulk(string $action, array $ids): array
    {
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

        return ['count' => $count, 'message' => $messages[$action]];
    }

    public function buildMissesQuery(Request $request): Builder
    {
        $query = RedirectMiss::query();

        if ($q = trim((string) $request->input('q', ''))) {
            $query->where('url', 'ilike', "%{$q}%");
        }

        $sort    = $request->input('sort', 'last');
        $dir     = $request->input('dir') === 'asc' ? 'asc' : 'desc';
        $sortMap = [
            'last'  => 'last_seen_at',
            'first' => 'first_seen_at',
            'hits'  => 'hits',
            'url'   => 'url',
        ];

        $query->orderBy($sortMap[$sort] ?? 'last_seen_at', $dir);

        return $query;
    }

    public function clearMisses(): int
    {
        return RedirectMiss::query()->delete();
    }

    public function detectPotentialCycles(array $redirects): array
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
