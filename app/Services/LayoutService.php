<?php

namespace App\Services;

use App\Models\Layout;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class LayoutService
{
    public function list(): Collection
    {
        return Layout::withCount('rubrics')->orderBy('title')->get();
    }

    public function create(array $data): Layout
    {
        return Layout::create([
            'title'   => $data['title'],
            'content' => $data['content'] ?? '',
        ]);
    }

    public function copy(Layout $layout, string $newTitle): Layout
    {
        return Layout::create([
            'title'   => $newTitle,
            'content' => $layout->content,
        ]);
    }

    public function update(Layout $layout, array $data): void
    {
        $layout->update([
            'title'   => $data['title'],
            'content' => $data['content'] ?? '',
        ]);
    }

    public function delete(Layout $layout): array
    {
        $rubrics = $layout->usedByRubrics();

        if ($rubrics->isNotEmpty()) {
            return [
                'ok'      => false,
                'message' => 'Невозможно удалить макет. Он используется в рубриках: ' . $rubrics->join(', '),
            ];
        }

        $layout->delete();

        return ['ok' => true];
    }

    public function availableTags(): array
    {
        $system = [
            ['tag' => '[maincontent]', 'hint' => 'Основной контент страницы - содержимое документа'],
            ['tag' => '[title]', 'hint' => 'Заголовок страницы (title). Формируется из названия документа, образуя готовый HTML-тег <br /> <code>&lt;title&gt;Название документа&lt;/title&gt;</code>.'],
            ['tag' => '[meta]', 'hint' => 'Формирует мета-тег описания страницы:<br /><code>&lt;meta name="description"&gt;</code>.<br /><br />Описание подставляется автоматически на основании значения, указанного для каждого документа.'],
            ['tag' => '[robots]', 'hint' => 'Формирует полноценный мета-тег: <br /><code>&lt;meta name="robots" content="noindex,nofollow"&gt;</code> <br /><br />Параметры будут подставлены автоматически в зависимости от значений, заданных в документе.'],
            ['tag' => '[canonical]', 'hint' => 'Формирует  полноценный тег с каноническим адресом.<br /><code>&lt;link rel="canonical" href="https://site.ru/about"&gt;</code>'],
            ['tag' => '[sitename]', 'hint' => 'Использует название сайта, указанного в системных настройках.'],
            ['tag' => '[domain]', 'hint' => 'Получает чистое доменное имя без протокола. Пример: <code>site.ru</code>'],
            ['tag' => '[home:url]', 'hint' => 'Полный URL главной страницы.<br><br><b>Пример:</b><br><code>&lt;a href="[home:url]"&gt;Главная&lt;/a&gt;</code>'],
            ['tag' => '[breadcrumb]', 'hint' => 'Автоматически формирует Хлебные крошки до текущей страницы.'],
            ['tag' => '[assets:FILE]', 'hint' => 'Подключает CSS или JS файл из <code>/assets/</code>.<br><br>'
                . '<b>Примеры:</b><br>'
                . '<code>[assets:css/style.css]</code><br>'
                . '→ <code>&lt;link rel="stylesheet" href="/assets/css/style.css"&gt;</code><br><br>'
                . '<code>[assets:js/app.js]</code><br>'
                . '→ <code>&lt;script src="/assets/js/app.js"&gt;&lt;/script&gt;</code>'],
            ['tag' => '[file:PATH]', 'hint' => 'Путь к файлу в директории <code>public/</code>.<br><br>'
                . '<b>Примеры:</b><br>'
                . '<code>[file:uploads/photo.jpg]</code> → <code>/uploads/photo.jpg</code><br>'
                . '<code>[file:images/logo.png]</code> → <code>/images/logo.png</code>'],
            ['tag' => '[date:format]', 'hint' => $this->dateTagHint()],
            ['tag' => '[head:code]', 'hint' => 'Вставляет произвольный код из настроек SEO (поле «Код в head»).<br><br>'
                . 'Размещайте тег в любом месте внутри <code>&lt;head&gt;</code>, чтобы управлять порядком подключения стилей и скриптов.<br><br>'
                . '<b>Пример:</b><br>'
                . '<code>[head:code]</code><br>'
                . '<code>[assets:css/app.css]</code><br><br>'
                . 'CDN-библиотеки загрузятся раньше app.css и смогут быть переопределены вашими стилями.<br><br>'
                . 'Если тег не указан в макете - код вставляется автоматически перед <code>&lt;/head&gt;</code>.'],
            ['tag' => '[body:code]', 'hint' => 'Вставляет произвольный код из настроек SEO (поле «Код перед body»).<br><br>'
                . 'Размещайте тег перед подключением своих скриптов, чтобы управлять порядком загрузки JS.<br><br>'
                . '<b>Пример:</b><br>'
                . '<code>[body:code]</code><br>'
                . '<code>[assets:js/app.js]</code><br><br>'
                . 'Bootstrap JS из CDN загрузится раньше app.js и будет доступен при его инициализации.<br><br>'
                . 'Если тег не указан в макете - код вставляется автоматически перед <code>&lt;/body&gt;</code>.'],
        ];

        $blocks = [];

        try {
            $blocks = DB::table('blocks')
                ->select('alias', 'title')
                ->orderBy('title')
                ->get()
                ->map(fn ($b) => ['tag' => "[block:{$b->alias}]", 'title' => $b->title])
                ->toArray();
        } catch (\Throwable) {
        }

        $navigation = [];

        try {
            $navigation = DB::table('navigations')
                ->select('alias', 'title')
                ->orderBy('title')
                ->get()
                ->map(fn ($n) => ['tag' => "[nav:{$n->alias}]", 'title' => $n->title])
                ->toArray();
        } catch (\Throwable) {
        }

        $modules = [];

        try {
            $activeModules = \App\Models\Module::where('is_active', true)
                ->whereNotNull('tag')
                ->where('tag', '!=', '')
                ->orderBy('name')
                ->get();

            foreach ($activeModules as $m) {
                $controllerFile = base_path("modules/{$m->sys_name}/Controllers/AdminController.php");
                $tags = null;

                if (file_exists($controllerFile)) {
                    require_once $controllerFile;
                    $class = "Modules\\{$m->sys_name}\\Controllers\\AdminController";

                    if (class_exists($class) && method_exists($class, 'getLayoutTags')) {
                        $tags = $class::getLayoutTags();
                    }
                }

                if ($tags !== null) {
                    foreach ($tags as $tag) {
                        $modules[] = $tag;
                    }
                } else {
                    $modules[] = ['tag' => $m->tag, 'title' => $m->name];
                }
            }
        } catch (\Throwable) {
        }

        return compact('system', 'blocks', 'navigation', 'modules');
    }

    public function listAssets(string $type): array
    {
        $base = public_path("assets/{$type}");

        if (!is_dir($base)) {
            return [];
        }

        $result = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($base, \RecursiveDirectoryIterator::SKIP_DOTS),
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === $type) {
                $relativePath = ltrim(
                    str_replace([$base, '\\'], ['', '/'], $file->getPathname()),
                    '/',
                );
                $result[] = [
                    'name'     => $relativePath,
                    'size'     => $file->getSize(),
                    'modified' => $file->getMTime(),
                ];
            }
        }

        usort($result, fn ($a, $b) => strcmp($a['name'], $b['name']));

        return $result;
    }

    private function dateTagHint(): string
    {
        $now = Carbon::now()->locale('ru');

        $rows = [
            ['d', 'День с нулём', $now->format('d')],
            ['j', 'День без нуля', $now->format('j')],
            ['m', 'Месяц с нулём', $now->format('m')],
            ['n', 'Месяц без нуля', $now->format('n')],
            ['Y', 'Год (4 цифры)', $now->format('Y')],
            ['y', 'Год (2 цифры)', $now->format('y')],
            ['H', 'Часы (00–23)', $now->format('H')],
            ['i', 'Минуты (00–59)', $now->format('i')],
            ['s', 'Секунды', $now->format('s')],
            ['l', 'День недели', $now->translatedFormat('l')],
            ['D', 'День недели кратко', $now->translatedFormat('D')],
            ['F', 'Месяц прописью', $now->translatedFormat('F')],
            ['M', 'Месяц кратко', $now->translatedFormat('M')],
        ];

        $tableRows = implode('', array_map(
            fn ($r) => "<tr><td><code>{$r[0]}</code></td><td>{$r[1]}</td><td class=\"text-warning\">{$r[2]}</td></tr>",
            $rows,
        ));

        return '<b>Текущая дата/время.</b> Формат задаётся по правилам PHP <code>date()</code>.<br><br>'
            . '<table class="table table-sm mb-2 ztr-hint-table">'
            . '<thead><tr><th>Символ</th><th>Значение</th><th>Сейчас</th></tr></thead>'
            . "<tbody>{$tableRows}</tbody></table>"
            . '<b>Примеры использования:</b><br>'
            . '<code>[date:d.m.Y]</code> → <span class="text-warning">' . $now->format('d.m.Y') . '</span><br>'
            . '<code>[date:l, d F Y]</code> → <span class="text-warning">' . $now->translatedFormat('l, d F Y') . '</span><br>'
            . '<code>[date:H:i]</code> → <span class="text-warning">' . $now->format('H:i') . '</span><br>'
            . '<code>[date:d F Y, H:i]</code> → <span class="text-warning">' . $now->translatedFormat('d F Y') . ', ' . $now->format('H:i') . '</span>';
    }
}
