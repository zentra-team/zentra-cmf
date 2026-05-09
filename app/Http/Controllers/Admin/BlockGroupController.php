<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderBlockGroupRequest;
use App\Http\Requests\Admin\SaveBlockGroupRequest;
use App\Models\BlockGroup;
use App\Services\BlockService;
use App\Services\Logger;

class BlockGroupController extends Controller
{
    public function __construct(private readonly BlockService $blockService) {}

    public function store(SaveBlockGroupRequest $request)
    {
        $group = $this->blockService->createGroup($request->validated());

        Logger::adminAction('Создал группу блоков', 'create', 'block_group', $group->id, $group->title);

        return response()->json(['ok' => true, 'message' => 'Группа создана', 'group' => $group]);
    }

    public function update(SaveBlockGroupRequest $request, BlockGroup $group)
    {
        $group->update($request->validated());

        Logger::adminAction('Редактировал группу блоков', 'edit', 'block_group', $group->id, $group->title);

        return response()->json(['ok' => true, 'message' => 'Группа обновлена']);
    }

    public function reorder(ReorderBlockGroupRequest $request)
    {
        $this->blockService->reorderGroups($request->validated()['ids']);

        return response()->json(['ok' => true]);
    }

    public function destroy(BlockGroup $group)
    {
        [$id, $title] = [$group->id, $group->title];
        $group->delete();
        Logger::adminAction('Удалил группу блоков', 'delete', 'block_group', $id, $title);

        return response()->json(['ok' => true, 'message' => 'Группа удалена. Блоки перенесены в «Без группы»']);
    }
}
