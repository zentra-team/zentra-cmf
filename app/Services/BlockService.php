<?php

namespace App\Services;

use App\Models\Block;
use App\Models\BlockGroup;
use App\Models\Module;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class BlockService
{
    public function indexData(): array
    {
        return [
            'groups'    => BlockGroup::orderBy('position')->with(['blocks'])->get(),
            'ungrouped' => Block::whereNull('group_id')->orderBy('position')->get(),
        ];
    }

    public function groupsForSelect(): Collection
    {
        return BlockGroup::orderBy('position')->get();
    }

    public function copy(Block $block, array $data): Block
    {
        return Block::create([
            'title'       => $block->title . ' (копия)',
            'alias'       => $data['alias'],
            'description' => $block->description,
            'group_id'    => $data['group_id'] ?? null,
            'content'     => $block->content,
            'is_wysiwyg'  => $block->is_wysiwyg,
            'position'    => (int) Block::max('position') + 1,
        ]);
    }

    public function createGroup(array $data): BlockGroup
    {
        $data['position'] = (int) BlockGroup::max('position') + 1;

        return BlockGroup::create($data);
    }

    public function reorderGroups(array $ids): void
    {
        foreach ($ids as $pos => $id) {
            BlockGroup::where('id', $id)->update(['position' => $pos]);
        }
    }

    public function buildTags(?Block $excludeBlock = null): array
    {
        $system = [
            ['tag' => '[sitename]', 'hint' => 'Название сайта из системных настроек.'],
            ['tag' => '[domain]', 'hint' => 'Домен без протокола. Пример: <code>example.com</code>'],
            ['tag' => '[home:url]', 'hint' => 'Полный URL главной страницы.<br><br><b>Пример:</b><br><code>&lt;a href="[home:url]"&gt;Главная&lt;/a&gt;</code>'],
            ['tag' => '[breadcrumb]', 'hint' => 'Хлебные крошки текущей страницы.'],
            ['tag' => '[maincontent]', 'hint' => 'Основной контент страницы. Используется в макетах, а не в блоках.'],
            ['tag' => '[title]', 'hint' => 'Заголовок страницы (meta title).'],
            ['tag' => '[meta]', 'hint' => 'Мета-тег описания страницы: <code>&lt;meta name="description"&gt;</code>.'],
            ['tag' => '[robots]', 'hint' => 'Директива meta robots. По умолчанию: <code>index, follow</code>.'],
            ['tag' => '[canonical]', 'hint' => 'Canonical URL текущей страницы (полный адрес).'],
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
        ];

        $blockTags = [];

        try {
            $blocksQuery = Block::orderBy('position');

            if ($excludeBlock) {
                $blocksQuery->where('id', '!=', $excludeBlock->id);
            }

            $blockTags = $blocksQuery->get()->map(fn ($b) => [
                'tag'   => '[block:' . $b->alias . ']',
                'title' => $b->title,
            ])->toArray();
        } catch (\Exception) {
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

        $moduleTags = [];

        try {
            $activeModules = Module::where('is_active', true)
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
                        $moduleTags[] = $tag;
                    }
                } else {
                    $moduleTags[] = ['tag' => $m->tag, 'title' => $m->name];
                }
            }
        } catch (\Throwable) {
        }

        return compact('system', 'blockTags', 'navigation', 'moduleTags');
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
            ['H', 'Часы (00–23)', $now->format('H')],
            ['i', 'Минуты (00–59)', $now->format('i')],
            ['l', 'День недели', $now->translatedFormat('l')],
            ['F', 'Месяц прописью', $now->translatedFormat('F')],
        ];

        $tableRows = implode('', array_map(
            fn ($r) => "<tr><td><code>{$r[0]}</code></td><td>{$r[1]}</td><td class=\"text-warning\">{$r[2]}</td></tr>",
            $rows,
        ));

        return '<b>Текущая дата/время.</b> Формат - по правилам PHP <code>date()</code>.<br><br>'
            . '<table class="table table-sm mb-2 ztr-hint-table">'
            . '<thead><tr><th>Символ</th><th>Значение</th><th>Сейчас</th></tr></thead>'
            . "<tbody>{$tableRows}</tbody></table>"
            . '<b>Примеры:</b><br>'
            . '<code>[date:d.m.Y]</code> → <span class="text-warning">' . $now->format('d.m.Y') . '</span><br>'
            . '<code>[date:H:i]</code> → <span class="text-warning">' . $now->format('H:i') . '</span>';
    }
}
