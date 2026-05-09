<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderBlockGroupRequest;
use App\Http\Requests\Admin\SaveBlockGroupRequest;
use App\Models\BlockGroup;
use App\Services\Logger;

class BlockGroupController extends Controller
{
    public function store(SaveBlockGroupRequest $request)
    {
        $data = $request->validated();

        $data['position'] = (int) BlockGroup::max('position') + 1;
        $group = BlockGroup::create($data);

        Logger::adminAction('Создал группу блоков', 'create', 'block_group', $group->id, $group->title);

        return response()->json(['ok' => true, 'message' => 'Группа создана', 'group' => $group]);
    }

    public function update(SaveBlockGroupRequest $request, BlockGroup $group)
    {
        $data = $request->validated();

        $group->update($data);

        Logger::adminAction('Редактировал группу блоков', 'edit', 'block_group', $group->id, $group->title);

        return response()->json(['ok' => true, 'message' => 'Группа обновлена']);
    }

    public function reorder(ReorderBlockGroupRequest $request)
    {
        $data = $request->validated();

        foreach ($data['ids'] as $pos => $id) {
            BlockGroup::where('id', $id)->update(['position' => $pos]);
        }

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
