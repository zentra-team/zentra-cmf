<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\CopyBlockRequest;
use App\Http\Requests\Admin\StoreBlockRequest;
use App\Http\Requests\Admin\UpdateBlockRequest;
use App\Models\Block;
use App\Services\BlockService;
use App\Services\Logger;
use App\Support\Permission;
use Illuminate\Http\Request;

class BlockController extends Controller
{
    public function __construct(private readonly BlockService $blockService) {}

    public function index(Request $request)
    {
        ['groups' => $groups, 'ungrouped' => $ungrouped] = $this->blockService->indexData();

        [$canList, $canCreate, $canEdit, $canDelete, $canGroups] = $this->resolveCaps($request);

        return view('admin.blocks.index', compact(
            'groups',
            'ungrouped',
            'canList',
            'canCreate',
            'canEdit',
            'canDelete',
            'canGroups',
        ));
    }

    public function create(Request $request)
    {
        $groups = $this->blockService->groupsForSelect();
        [, $canCreate, $canEdit] = $this->resolveCaps($request);

        return view('admin.blocks.edit', [
            'block'     => null,
            'groups'    => $groups,
            'tags'      => $this->blockService->buildTags(),
            'canCreate' => $canCreate,
            'canEdit'   => $canEdit,
        ]);
    }

    public function store(StoreBlockRequest $request)
    {
        $data = $request->validated();
        $data['is_wysiwyg'] = $request->boolean('is_wysiwyg');

        $block = Block::create($data);

        Logger::adminAction('Создал блок', 'create', 'block', $block->id, $block->title);

        if (request()->wantsJson()) {
            return response()->json([
                'ok'       => true,
                'message'  => 'Блок создан',
                'redirect' => route('admin.blocks.edit', $block),
            ]);
        }

        return redirect()->route('admin.blocks.edit', $block)
            ->with('success', 'Блок создан');
    }

    public function edit(Request $request, Block $block)
    {
        $groups = $this->blockService->groupsForSelect();
        [, $canCreate, $canEdit] = $this->resolveCaps($request);

        return view('admin.blocks.edit', [
            'block'     => $block,
            'groups'    => $groups,
            'tags'      => $this->blockService->buildTags($block),
            'canCreate' => $canCreate,
            'canEdit'   => $canEdit,
        ]);
    }

    public function update(UpdateBlockRequest $request, Block $block)
    {
        $block->update($request->validated());

        Logger::adminAction('Редактировал блок', 'edit', 'block', $block->id, $block->title);

        return response()->json(['ok' => true, 'message' => 'Блок сохранён']);
    }

    public function copy(CopyBlockRequest $request, Block $block)
    {
        $copy = $this->blockService->copy($block, $request->validated());

        Logger::adminAction("Скопировал блок «{$block->title}»", 'create', 'block', $copy->id, $copy->title);

        return response()->json(['ok' => true, 'message' => 'Блок скопирован']);
    }

    public function destroy(Block $block)
    {
        [$id, $title] = [$block->id, $block->title];
        $block->delete();
        Logger::adminAction('Удалил блок', 'delete', 'block', $id, $title);

        return response()->json(['ok' => true, 'message' => 'Блок удалён']);
    }

    private function resolveCaps(Request $request): array
    {
        $user = $request->user('admin') ?? $request->user();

        return [
            $user?->hasPermission(Permission::BLOCKS_LIST) ?? false,
            $user?->hasPermission(Permission::BLOCKS_CREATE) ?? false,
            $user?->hasPermission(Permission::BLOCKS_EDIT) ?? false,
            $user?->hasPermission(Permission::BLOCKS_DELETE) ?? false,
            $user?->hasPermission(Permission::BLOCKS_GROUPS) ?? false,
        ];
    }
}
