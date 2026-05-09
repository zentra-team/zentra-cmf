<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserGroupAssignRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Logger;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct(private UserService $userService)
    {
    }

    public function index(Request $request)
    {
        /** @var User $authUser */
        $authUser = Auth::guard('admin')->user();
        $canList = $authUser->hasPermission('users.list');
        $canCreate = $authUser->hasPermission('users.create');
        $canEdit = $authUser->hasPermission('users.edit');
        $canDelete = $authUser->hasPermission('users.delete');
        $canGroups = $authUser->hasPermission('users.groups');

        $groups = $this->userService->allGroups();
        $users = $this->userService->list($request, $canList);

        return view('admin.users.index', compact(
            'users',
            'groups',
            'canList',
            'canCreate',
            'canEdit',
            'canDelete',
            'canGroups',
        ));
    }

    public function store(StoreUserRequest $request)
    {
        $user = $this->userService->create($request->validated());

        Logger::adminAction('Создал пользователя', 'create', 'user', $user->id, $user->email);

        return redirect()->route('admin.users.edit', $user)
            ->with('new_user', true);
    }

    public function edit(User $user)
    {
        /** @var User $authUser */
        $authUser = Auth::guard('admin')->user();
        $canEdit = $authUser->hasPermission('users.edit');
        $canGroups = $authUser->hasPermission('users.groups');

        $groups = $this->userService->allGroups();
        $isNewUser = session('new_user', false);

        if ($canEdit && !$user->group_id) {
            $default = $groups->firstWhere('is_default', true);

            if ($default !== null) {
                $user->group_id = $default->id;
                $user->save();
            }
        }

        return view('admin.users.edit', compact('user', 'groups', 'isNewUser', 'canEdit', 'canGroups'));
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');

        $this->userService->update($user, $data, $request->filled('password'));

        Logger::adminAction('Редактировал пользователя', 'edit', 'user', $user->id, $user->email);

        return response()->json(['ok' => true, 'message' => 'Пользователь сохранён']);
    }

    public function sendPassword(User $user)
    {
        $result = $this->userService->resetPassword($user);

        if ($result['ok']) {
            Logger::adminAction('Отправил пароль пользователю', 'other', 'user', $user->id, $user->email);

            return response()->json($result);
        }

        Logger::adminAction('Ошибка отправки пароля пользователю', 'error', 'user', $user->id, $user->email);

        return response()->json($result, 500);
    }

    public function updateGroup(UpdateUserGroupAssignRequest $request, User $user)
    {
        $data = $request->validated();
        $oldGroup = $user->group?->name ?? '—';
        $newId = $data['group_id'] ?? null;
        $newGroup = $newId ? ($this->userService->allGroups()->find($newId)?->name ?? '—') : '—';

        $this->userService->assignGroup($user, $newId);

        Logger::adminAction(
            "Изменил группу пользователя ({$oldGroup} → {$newGroup})",
            'edit',
            'user',
            $user->id,
            $user->email,
        );

        return response()->json(['ok' => true]);
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::guard('admin')->id()) {
            return response()->json([
                'ok'      => false,
                'message' => 'Нельзя удалить свою учётную запись',
            ], 422);
        }

        [$id, $email] = [$user->id, $user->email];
        $this->userService->delete($user);
        Logger::adminAction('Удалил пользователя', 'delete', 'user', $id, $email);

        return response()->json(['ok' => true, 'message' => 'Пользователь удалён']);
    }
}
