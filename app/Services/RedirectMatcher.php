<?php

namespace App\Services;

use App\Models\Redirect;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RedirectMatcher
{
    private array $patternCache = [];

    public function match(string $path, int $maxHops = 10): ?array
    {
        $path = Redirect::normalizeUrl($path);

        if ($path === '') {
            return null;
        }

        $visited = [];
        $chainIds = [];
        $current = $path;
        $finalType = 301;
        $finalPreserve = true;

        for ($i = 0; $i < $maxHops; $i++) {
            if (isset($visited[$current])) {
                Log::warning('RedirectMatcher: cycle detected - пропускаем редирект целиком', [
                    'origin'   => $path,
                    'cycle_at' => $current,
                    'chain'    => array_keys($visited),
                ]);

                return null;
            }

            $visited[$current] = true;

            $hit = $this->resolveOne($current);

            if ($hit === null) {
                break;
            }

            $chainIds[] = $hit['id'];
            $finalType = $hit['type'];
            $finalPreserve = $hit['preserve_query_string'];

            if (preg_match('#^https?://#i', $hit['to'])) {
                $current = $hit['to'];
                break;
            }

            $current = Redirect::normalizeUrl($hit['to']);
        }

        if (empty($chainIds)) {
            return null;
        }

        if (count($chainIds) >= $maxHops) {
            Log::warning('RedirectMatcher: max hops exceeded', [
                'origin'   => $path,
                'last_url' => $current,
                'chain'    => array_keys($visited),
            ]);
        }

        return [
            'to'                    => $current,
            'type'                  => $finalType,
            'preserve_query_string' => $finalPreserve,
            'ids'                   => $chainIds,
        ];
    }

    private function resolveOne(string $url): ?array
    {
        $direct = Redirect::active()
            ->where('is_wildcard', false)
            ->where('from_url', $url)
            ->first();

        if ($direct !== null) {
            return [
                'id'                    => (int) $direct->id,
                'to'                    => $direct->to_url,
                'type'                  => (int) $direct->type,
                'preserve_query_string' => (bool) $direct->preserve_query_string,
            ];
        }

        $wildcards = Redirect::active()
            ->where('is_wildcard', true)
            ->orderBy('priority', 'desc')
            ->orderBy('id', 'asc')
            ->get();

        foreach ($wildcards as $rule) {
            $pattern = $this->patternCache[$rule->id] ??= $rule->compiledPattern();

            if (preg_match($pattern, $url, $m)) {
                $captures = array_slice($m, 1);

                return [
                    'id'                    => (int) $rule->id,
                    'to'                    => $rule->applyCaptures($captures),
                    'type'                  => (int) $rule->type,
                    'preserve_query_string' => (bool) $rule->preserve_query_string,
                ];
            }
        }

        return null;
    }

    public function recordHits(array $ids): void
    {
        if (empty($ids)) {
            return;
        }

        DB::table('redirects')
            ->whereIn('id', $ids)
            ->update([
                'hits'        => DB::raw('hits + 1'),
                'last_hit_at' => now(),
            ]);
    }
}
