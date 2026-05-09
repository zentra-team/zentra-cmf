<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function buildStats(Request $request): array
    {
        $data = [];

        try {
            $version = DB::selectOne('SELECT version()');

            preg_match('/PostgreSQL\s+([\d.]+)/i', $version->version ?? '', $m);
            $data['pg_version'] = $m[1] ?? '—';
        } catch (\Throwable) {
            $data['pg_version'] = '—';
        }

        foreach (['rubrics', 'requests', 'layouts', 'modules', 'users'] as $table) {
            try {
                $data[$table] = DB::table($table)->count();
            } catch (\Throwable) {
                $data[$table] = '—';
            }
        }

        try {
            $data['errors_404'] = DB::table('error_logs_404')->count();
        } catch (\Throwable) {
            $data['errors_404'] = 0;
        }

        try {
            $data['errors_sql'] = DB::table('error_logs_db')->count();
        } catch (\Throwable) {
            $data['errors_sql'] = 0;
        }

        $lazyKeys = [
            'db_size'        => 'db_size',
            'cache_size'     => 'cache_size',
            'stat_documents' => 'documents',
            'log_events'     => 'log_events',
        ];

        foreach ($lazyKeys as $sessionKey => $dataKey) {
            $cached = $request->session()->get("dashboard.metric.{$sessionKey}");

            if ($cached !== null) {
                $data["cached_{$dataKey}"] = $cached;
            }
        }

        return $data;
    }

    public function buildWidgets(): array
    {
        $data = [];

        $settingsUrl = route('admin.settings');
        $flagDefs = [
            'sitemap'      => ['key' => 'sitemap_enabled', 'default' => '1', 'label' => 'Sitemap', 'icon' => 'bi-diagram-3', 'url' => $settingsUrl . '?tab=seo#sitemapEnabled'],
            'rss'          => ['key' => 'rss_enabled', 'default' => '0', 'label' => 'RSS', 'icon' => 'bi-rss', 'url' => $settingsUrl . '?tab=seo#rssEnabled'],
            'api'          => ['key' => 'api_enabled', 'default' => '0', 'label' => 'API', 'icon' => 'bi-braces', 'url' => route('admin.api-tokens.index')],
            'public_cache' => ['key' => 'public_cache_enabled', 'default' => '0', 'label' => 'HTTP-кеш', 'icon' => 'bi-lightning-charge', 'url' => $settingsUrl . '?tab=cache#cacheCards'],
            'redirects'    => ['key' => 'redirects_enabled', 'default' => '1', 'label' => 'Редиректы', 'icon' => 'bi-signpost-split', 'url' => route('admin.redirects.index')],
        ];
        $data['feature_flags'] = array_values(array_map(function ($def) {
            try {
                $val = Setting::getValue($def['key'], $def['default']);
            } catch (\Throwable) {
                $val = $def['default'];
            }

            return ['label' => $def['label'], 'icon' => $def['icon'], 'enabled' => $val === '1', 'url' => $def['url']];
        }, $flagDefs));

        try {
            $rows = DB::table('documents')
                ->selectRaw('status, COUNT(*) as cnt')
                ->groupBy('status')
                ->get()
                ->keyBy('status');
            $data['doc_statuses'] = [
                'active'     => (int) ($rows[1]->cnt ?? 0),
                'draft'      => (int) ($rows[0]->cnt ?? 0),
                'moderation' => (int) ($rows[2]->cnt ?? 0),
                'total'      => (int) array_sum(array_column($rows->toArray(), 'cnt')),
            ];
        } catch (\Throwable) {
            $data['doc_statuses'] = ['active' => 0, 'draft' => 0, 'moderation' => 0, 'total' => 0];
        }

        try {
            $since = now()->subDays(13)->startOfDay();
            $rows = DB::table('documents')
                ->selectRaw("(created_at AT TIME ZONE 'UTC')::date AS day, COUNT(*) AS cnt")
                ->where('created_at', '>=', $since)
                ->groupByRaw("(created_at AT TIME ZONE 'UTC')::date")
                ->orderBy('day')
                ->get();

            $byDay = $rows->keyBy('day');
            $days = [];

            for ($i = 13; $i >= 0; $i--) {
                $d = now()->subDays($i)->format('Y-m-d');
                $days[] = ['date' => $d, 'count' => (int) ($byDay[$d]->cnt ?? 0)];
            }

            $data['doc_activity'] = $days;
        } catch (\Throwable) {
            $data['doc_activity'] = [];
        }

        try {
            $data['top_404'] = DB::table('redirect_misses')
                ->orderByDesc('hits')
                ->limit(5)
                ->get(['url', 'hits', 'last_seen_at'])
                ->toArray();
        } catch (\Throwable) {
            $data['top_404'] = [];
        }

        try {
            $data['recent_logs'] = DB::table('admin_logs')
                ->orderByDesc('created_at')
                ->limit(10)
                ->get(['user_name', 'action', 'action_type', 'object_type', 'object_title', 'created_at'])
                ->toArray();
        } catch (\Throwable) {
            $data['recent_logs'] = [];
        }

        try {
            $data['api_stats'] = [
                'active_tokens' => DB::table('api_tokens')->where('is_active', true)->count(),
                'total_tokens'  => DB::table('api_tokens')->count(),
                'total_hits'    => (int) DB::table('api_tokens')->sum('hits'),
            ];
        } catch (\Throwable) {
            $data['api_stats'] = ['active_tokens' => 0, 'total_tokens' => 0, 'total_hits' => 0];
        }

        try {
            $modDocs = DB::table('documents')
                ->leftJoin('rubrics', 'documents.rubric_id', '=', 'rubrics.id')
                ->where('documents.status', 2)
                ->orderBy('documents.updated_at')
                ->limit(6)
                ->select('documents.id', 'documents.title', 'documents.updated_at', 'rubrics.title as rubric_name')
                ->get()->toArray();
            $data['urgent_moderation'] = [
                'count' => DB::table('documents')->where('status', 2)->count(),
                'docs'  => $modDocs,
            ];
        } catch (\Throwable) {
            $data['urgent_moderation'] = ['count' => 0, 'docs' => []];
        }

        try {
            $since = now()->subDay();
            $data['urgent_errors'] = [
                'db_count'  => DB::table('error_logs_db')->where('created_at', '>=', $since)->count(),
                'db_recent' => DB::table('error_logs_db')
                    ->where('created_at', '>=', $since)
                    ->orderByDesc('created_at')
                    ->limit(5)
                    ->get(['level', 'message', 'created_at'])->toArray(),
                '404_count' => DB::table('error_logs_404')->where('created_at', '>=', $since)->count(),
            ];
        } catch (\Throwable) {
            $data['urgent_errors'] = ['db_count' => 0, 'db_recent' => [], '404_count' => 0];
        }

        try {
            $data['urgent_misses'] = [
                'count' => DB::table('redirect_misses')->count(),
                'top'   => DB::table('redirect_misses')
                    ->orderByDesc('hits')
                    ->limit(6)
                    ->get(['url', 'hits'])->toArray(),
            ];
        } catch (\Throwable) {
            $data['urgent_misses'] = ['count' => 0, 'top' => []];
        }

        try {
            $data['recent_docs'] = DB::table('documents')
                ->leftJoin('rubrics', 'documents.rubric_id', '=', 'rubrics.id')
                ->orderByDesc('documents.updated_at')
                ->limit(6)
                ->select('documents.id', 'documents.title', 'documents.status', 'documents.updated_at', 'rubrics.title as rubric_name')
                ->get()
                ->toArray();
        } catch (\Throwable) {
            $data['recent_docs'] = [];
        }

        return $data;
    }

    public function onlineUsers(): Collection
    {
        $threshold = now()->subMinutes(15);

        return DB::table('users')
            ->whereNotNull('last_seen_at')
            ->where('last_seen_at', '>=', $threshold)
            ->orderByDesc('last_seen_at')
            ->get(['id', 'name', 'last_seen_at'])
            ->map(fn ($u) => [
                'id'           => $u->id,
                'name'         => $u->name,
                'last_seen_at' => Carbon::parse($u->last_seen_at)->timestamp,
            ]);
    }

    public function resolveMetric(string $metric): mixed
    {
        return match ($metric) {
            'db_size'        => $this->getDbSize(),
            'cache_size'     => $this->getCacheSize(),
            'stat_documents' => $this->getDocumentCount(),
            'log_events'     => $this->getLogEventCount(),
            default          => null,
        };
    }

    private function getDbSize(): string
    {
        try {
            $dbName = config('database.connections.pgsql.database');
            $result = DB::selectOne('SELECT pg_size_pretty(pg_database_size(?)) AS size', [$dbName]);

            return $result->size ?? '—';
        } catch (\Throwable) {
            return '—';
        }
    }

    private function getCacheSize(): string
    {
        $cachePath = storage_path('framework/cache');

        if (!is_dir($cachePath)) {
            return '0 B';
        }

        $bytes = $this->dirSize($cachePath);

        return $this->formatBytes($bytes);
    }

    private function getDocumentCount(): int|string
    {
        try {
            return DB::table('documents')->count();
        } catch (\Throwable) {
            return '—';
        }
    }

    private function getLogEventCount(): int|string
    {
        try {
            return DB::table('admin_logs')->count();
        } catch (\Throwable) {
            return '—';
        }
    }

    private function dirSize(string $dir): int
    {
        $size = 0;

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)) as $file) {
            $size += $file->getSize();
        }

        return $size;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        }

        if ($bytes < 1048576) {
            return round($bytes / 1024, 1) . ' KB';
        }

        if ($bytes < 1073741824) {
            return round($bytes / 1048576, 1) . ' MB';
        }

        return round($bytes / 1073741824, 2) . ' GB';
    }
}
