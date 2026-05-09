<?php

namespace App\Console\Commands;

use App\Models\MediaFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MediaCleanupOrphans extends Command
{
    protected $signature = 'media:cleanup-orphans
                            {--delete : Реально удалить (без флага - только показать список)}
                            {--min-age-hours=24 : Не трогать файлы младше N часов (защита от гонки upload <-> save)}';

    protected $description = 'Найти и (опционально) удалить MediaFile без ссылок в контенте';

    public function handle(): int
    {
        $minAgeHours = max(0, (int) $this->option('min-age-hours'));
        $cutoff = now()->subHours($minAgeHours);

        $candidates = MediaFile::where('created_at', '<=', $cutoff)->get();

        if ($candidates->isEmpty()) {
            $this->info("Нет файлов старше {$minAgeHours} ч для проверки.");

            return 0;
        }

        $this->line("Найдено файлов для проверки: <fg=cyan>{$candidates->count()}</> (старше {$minAgeHours} ч)");
        $this->line('Сканирую контент...');

        $haystack = $this->buildHaystack();
        $this->line('Размер базы поиска: <fg=cyan>' . number_format(strlen($haystack)) . '</> байт');

        $orphans = $candidates->filter(fn (MediaFile $f) => strpos($haystack, $f->uuid) === false);

        if ($orphans->isEmpty()) {
            $this->info('Файлов без использования не найдено - всё используется.');

            return 0;
        }

        $rows = $orphans->map(fn (MediaFile $f) => [
            $f->id,
            $f->uuid,
            $f->kind,
            $this->formatSize((int) $f->size),
            $f->created_at?->format('Y-m-d H:i') ?? '—',
            mb_strimwidth((string) $f->original_name, 0, 32, '...'),
        ])->all();

        $this->table(
            ['ID', 'UUID', 'Kind', 'Size', 'Created', 'Original name'],
            $rows,
        );

        $totalSize = $orphans->sum('size');
        $this->line("Файлов без использования: <fg=yellow>{$orphans->count()}</>, суммарный размер: <fg=yellow>{$this->formatSize((int) $totalSize)}</>");

        if (!$this->option('delete')) {
            $this->line('');
            $this->warn('Dry-run - ничего не удалено. Запустите с --delete для реального удаления.');

            return 0;
        }

        $this->line('');
        $this->warn('Удаляю файлы и записи...');

        [$deleted, $skipped] = [0, 0];

        foreach ($orphans as $f) {
            $path = $f->absolutePath();

            if (File::exists($path) && !File::delete($path)) {
                $this->error("  ✗ Не удалось удалить файл: {$path}");
                $skipped++;
                continue;
            }

            $f->delete();
            $deleted++;
        }

        $this->info("Удалено: {$deleted}" . ($skipped ? ", пропущено: {$skipped}" : ''));

        return 0;
    }

    private function buildHaystack(): string
    {
        $parts = [];
        $parts[] = DB::table('document_fields')->whereNotNull('value')->pluck('value')->implode("\n");
        $parts[] = DB::table('blocks')->whereNotNull('content')->pluck('content')->implode("\n");
        $parts[] = DB::table('layouts')->whereNotNull('content')->pluck('content')->implode("\n");
        $parts[] = DB::table('navigation_items')->whereNotNull('icon')->pluck('icon')->implode("\n");
        $parts[] = DB::table('navigation_items')->whereNotNull('extra_html')->pluck('extra_html')->implode("\n");
        $parts[] = DB::table('settings')->whereNotNull('value')->pluck('value')->implode("\n");

        return implode("\n", array_filter($parts));
    }

    private function formatSize(int $bytes): string
    {
        $units = ['Б', 'КБ', 'МБ', 'ГБ', 'ТБ'];
        $i = 0;
        $size = (float) $bytes;

        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, 2) . ' ' . $units[$i];
    }
}
