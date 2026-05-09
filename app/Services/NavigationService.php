<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Navigation;
use App\Models\NavigationItem;
use App\Models\Setting;
use App\Models\UserGroup;
use App\Support\DocumentUrl;
use Illuminate\Support\Collection;

class NavigationService
{
    public function indexData(): array
    {
        return [
            'navigations' => Navigation::orderBy('position')->get(),
            'groups'      => UserGroup::orderBy('name')->get(),
        ];
    }

    public function nonSystemGroups(): Collection
    {
        return UserGroup::where('is_system', false)->orderBy('name')->get();
    }

    public function itemsTree(Navigation $navigation): Collection
    {
        return $navigation->rootItems()->with('allChildren')->get();
    }

    public function searchDocuments(string $query, ?int $rubricId = null): Collection
    {
        $suffix = Setting::getValue('url_suffix', '');

        return Document::select('documents.id', 'documents.title', 'documents.alias', 'documents.status', 'rubrics.alias as rubric_alias')
            ->leftJoin('rubrics', 'rubrics.id', '=', 'documents.rubric_id')
            ->when($query !== '', fn ($q) => $q->where('documents.title', 'ilike', '%' . $query . '%'))
            ->when($rubricId !== null, fn ($q) => $q->where('documents.rubric_id', $rubricId))
            ->orderByRaw('CASE WHEN documents.status = ? THEN 0 ELSE 1 END', [Document::STATUS_ACTIVE])
            ->orderBy('documents.title')
            ->limit(10)
            ->get()
            ->map(fn ($doc) => [
                'title'    => $doc->title,
                'url'      => DocumentUrl::build($doc->rubric_alias, $doc->alias, $suffix),
                'is_draft' => $doc->status !== Document::STATUS_ACTIVE,
            ]);
    }

    public function create(array $data): Navigation
    {
        $data['position'] = Navigation::max('position') + 1;

        return Navigation::create($data);
    }

    public function copy(Navigation $nav, array $data): Navigation
    {
        return Navigation::create([
            'title'          => $data['title'],
            'alias'          => $data['alias'],
            'allowed_groups' => $nav->allowed_groups,
            'template_l1'    => $nav->template_l1,
            'link_tpl_l1'    => $nav->link_tpl_l1,
            'template_l2'    => $nav->template_l2,
            'link_tpl_l2'    => $nav->link_tpl_l2,
            'template_l3'    => $nav->template_l3,
            'link_tpl_l3'    => $nav->link_tpl_l3,
            'position'       => Navigation::max('position') + 1,
        ]);
    }

    public function createItem(Navigation $nav, array $data): NavigationItem
    {
        $parentId = $data['parent_id'] ?? null;
        $afterId = $data['after_id'] ?? null;

        if ($afterId) {
            $after = NavigationItem::find($afterId);
            $position = $after ? $after->position + 1 : 0;
        } else {
            $position = NavigationItem::where('navigation_id', $nav->id)
                ->where('parent_id', $parentId)
                ->max('position') + 1;
        }

        return NavigationItem::create([
            'navigation_id' => $nav->id,
            'parent_id'     => $parentId,
            'title'         => $data['title'],
            'url'           => $data['url'] ?? null,
            'target'        => $data['target'] ?? '_self',
            'css_class'     => $data['css_class'] ?? null,
            'css_id'        => $data['css_id'] ?? null,
            'css_style'     => $data['css_style'] ?? null,
            'description'   => $data['description'] ?? null,
            'image'         => $data['image'] ?? null,
            'icon'          => $data['icon'] ?? null,
            'extra_html'    => $data['extra_html'] ?? null,
            'position'      => $position,
            'is_active'     => true,
        ]);
    }

    public function reorderItems(Navigation $nav, array $order): void
    {
        foreach ($order as $row) {
            NavigationItem::where('id', $row['id'])
                ->where('navigation_id', $nav->id)
                ->update([
                    'parent_id' => $row['parent_id'],
                    'position'  => $row['position'],
                ]);
        }
    }

    public function toggleItem(NavigationItem $item): void
    {
        $item->update(['is_active' => !$item->is_active]);
    }

    public function deleteItem(NavigationItem $item): void
    {
        $item->delete();
    }
}
