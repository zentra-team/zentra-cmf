<?php

namespace App\Services;

use App\Models\Navigation;
use App\Models\NavigationItem;
use Illuminate\Http\Request;

class NavigationRenderer
{
    private const DEFAULT_TPL_L1 = '<ul class="navbar-nav me-auto mb-2 mb-lg-0">[content]</ul>';

    private const DEFAULT_LINK_TPL_L1 = <<<'TPL'
<li class="nav-item[if:active] active[/if:active]">
  <a class="nav-link [link:active:class][link:class]" href="[link:url]"[link:target][link:id][link:style]>[link:text]</a>
  [level:2]
</li>
TPL;

    private const DEFAULT_TPL_L2 = '<ul class="dropdown-menu">[content]</ul>';

    private const DEFAULT_LINK_TPL_L2 = <<<'TPL'
<li><a class="dropdown-item [link:active:class][link:class]" href="[link:url]"[link:target][link:id][link:style]>[link:text]</a>[level:3]</li>
TPL;

    private const DEFAULT_TPL_L3 = '<ul class="dropdown-menu dropdown-submenu">[content]</ul>';

    private const DEFAULT_LINK_TPL_L3 = <<<'TPL'
<li><a class="dropdown-item [link:active:class][link:class]" href="[link:url]"[link:target][link:id][link:style]>[link:text]</a></li>
TPL;

    public function renderByAlias(string $alias, Request $request): string
    {
        $navigation = Navigation::where('alias', $alias)->first();

        if ($navigation === null) {
            return "<!-- nav:{$alias} not found -->";
        }

        $navigation->load([
            'rootItems' => fn ($q) => $q->where('is_active', true)->orderBy('position'),
            'rootItems.allChildren',
        ]);

        $templates = $this->extractTemplates($navigation);
        $currentPath = trim($request->getPathInfo(), '/');

        $rootItems = $navigation->rootItems->values();
        $total = $rootItems->count();
        $itemsHtml = '';

        foreach ($rootItems as $i => $item) {
            $itemsHtml .= $this->renderItem($item, 1, $templates, $currentPath, $i, $total);
        }

        return $this->applyWrapperTemplate($templates['tpl_l1'], $itemsHtml);
    }

    private function renderItem(
        NavigationItem $item,
        int $level,
        array $tpls,
        string $currentPath,
        int $index,
        int $total,
    ): string {
        if (!$item->is_active) {
            return '';
        }

        $isActive = $this->isActive($item, $currentPath);

        $subItems = '';

        if ($level < 3 && $item->allChildren->isNotEmpty()) {
            $childLevel = $level + 1;
            $wrapperTpl = $tpls["tpl_l{$childLevel}"];
            $children = $item->allChildren->where('is_active', true)->values();
            $childTotal = $children->count();
            $childHtml = '';

            foreach ($children as $ci => $child) {
                $childHtml .= $this->renderItem($child, $childLevel, $tpls, $currentPath, $ci, $childTotal);
            }

            if ($childHtml !== '') {
                $subItems = $this->applyWrapperTemplate($wrapperTpl, $childHtml);
            }
        }

        return $this->applyLinkTemplate($tpls["link_tpl_l{$level}"], $item, $isActive, $subItems, $index, $total);
    }

    private function applyWrapperTemplate(string $tpl, string $items): string
    {
        return str_replace('[content]', $items, $tpl);
    }

    private function applyLinkTemplate(
        string $tpl,
        NavigationItem $item,
        bool $isActive,
        string $subItems,
        int $index,
        int $total,
    ): string {
        $isFirst = $index === 0;
        $isLast = $index === $total - 1;
        $hasChildren = $subItems !== '';

        $hasImg = $item->image !== null && $item->image !== '';

        $conditionals = [
            'if:active'      => $isActive,
            'if:not:active'  => !$isActive,
            'if:first'       => $isFirst,
            'if:not:first'   => !$isFirst,
            'if:last'        => $isLast,
            'if:not:last'    => !$isLast,
            'if:children'    => $hasChildren,
            'if:no:children' => !$hasChildren,
            'if:img'         => $hasImg,
            'if:no:img'      => !$hasImg,
        ];

        foreach ($conditionals as $tag => $show) {
            $tpl = preg_replace_callback(
                '/\[' . preg_quote($tag, '/') . '\](.*?)\[\/' . preg_quote($tag, '/') . '\]/s',
                fn (array $m) => $show ? $m[1] : '',
                $tpl,
            ) ?? $tpl;
        }

        $tpl = preg_replace_callback(
            '/\[if:every:(\d+)\](.*?)\[\/if:every:\1\]/s',
            fn (array $m) => (($index + 1) % (int) $m[1] === 0) ? $m[2] : '',
            $tpl,
        ) ?? $tpl;

        $tpl = preg_replace_callback(
            '/\[if:pos:(\d+)\](.*?)\[\/if:pos:\1\]/s',
            fn (array $m) => (($index + 1) === (int) $m[1]) ? $m[2] : '',
            $tpl,
        ) ?? $tpl;

        $target = ($item->target && $item->target !== '_self')
                        ? ' target="' . e($item->target) . '"' : '';
        $id = $item->css_id ? ' id="' . e($item->css_id) . '"' : '';
        $style = $item->css_style ? ' style="' . e($item->css_style) . '"' : '';
        $class = $item->css_class ? ' ' . e($item->css_class) : '';
        $activeClass = $isActive ? 'active' : '';
        $desc = e($item->description ?? '');
        $img = $item->image ? e($item->image) : '';

        $replacements = [
            '[level:2]'           => $subItems,
            '[level:3]'           => $subItems,
            '[link:url]'          => e($item->url ?? '#'),
            '[link:text]'         => e($item->title),
            '[link:target]'       => $target,
            '[link:class]'        => $class,
            '[link:id]'           => $id,
            '[link:style]'        => $style,
            '[link:active:class]' => $activeClass,
            '[link:title]'        => $desc,
            '[link:img]'          => $img,
            '[link:icon]'         => $item->icon ?? '',
            '[link:html]'         => $item->extra_html ?? '',
            '[link:pos]'          => (string) ($index + 1),
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $tpl);
    }

    private function isActive(NavigationItem $item, string $currentPath): bool
    {
        if (empty($item->url)) {
            return false;
        }

        $itemPath = trim(parse_url($item->url, PHP_URL_PATH) ?? '', '/');

        return $itemPath === $currentPath;
    }

    private function extractTemplates(Navigation $nav): array
    {
        return [
            'tpl_l1'      => $this->orDefault($nav->template_l1, self::DEFAULT_TPL_L1),
            'tpl_l2'      => $this->orDefault($nav->template_l2, self::DEFAULT_TPL_L2),
            'tpl_l3'      => $this->orDefault($nav->template_l3, self::DEFAULT_TPL_L3),
            'link_tpl_l1' => $this->orDefault($nav->link_tpl_l1, self::DEFAULT_LINK_TPL_L1),
            'link_tpl_l2' => $this->orDefault($nav->link_tpl_l2, self::DEFAULT_LINK_TPL_L2),
            'link_tpl_l3' => $this->orDefault($nav->link_tpl_l3, self::DEFAULT_LINK_TPL_L3),
        ];
    }

    private function orDefault(?string $value, string $default): string
    {
        return (isset($value) && trim($value) !== '') ? $value : $default;
    }
}
