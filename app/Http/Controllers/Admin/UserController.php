<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserGroupAssignRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Models\UserGroup;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $authUser = Auth::guard('admin')->user();
        $canList = $authUser->hasPermission('users.list');
        $canCreate = $authUser->hasPermission('users.create');
        $canEdit = $authUser->hasPermission('users.edit');
        $canDelete = $authUser->hasPermission('users.delete');
        $canGroups = $authUser->hasPermission('users.groups');

        $groups = UserGroup::orderBy('name')->get();

        $query = User::with('group')->orderBy('id');

        if ($canList) {
            if ($search = $request->input('search')) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ilike', "%{$search}%")
                      ->orWhere('email', 'ilike', "%{$search}%")
                      ->orWhere('first_name', 'ilike', "%{$search}%")
                      ->orWhere('last_name', 'ilike', "%{$search}%");

                    if (is_numeric($search)) {
                        $q->orWhere('id', $search);
                    }

                    if (str_contains($search, '@') === false && str_contains($search, '.')) {
                        $q->orWhere('email', 'ilike', "%@{$search}");
                    }
                });
            }

            if ($groupId = $request->input('group_id')) {
                $query->where('group_id', $groupId);
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->boolean('status'));
            }

            $users = $query->paginate(50)->withQueryString();
        } else {
            $users = User::query()->whereRaw('false')->paginate(50);
        }

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
        $data = $request->validated();

        $user = User::create([
            'name'      => strstr($data['email'], '@', true),
            'email'     => $data['email'],
            'password'  => bcrypt(Str::random(16)),
            'is_active' => true,
        ]);

        Logger::adminAction('Создал пользователя', 'create', 'user', $user->id, $user->email);

        return redirect()->route('admin.users.edit', $user)
            ->with('new_user', true);
    }

    public function edit(User $user)
    {
        $authUser = Auth::guard('admin')->user();
        $canEdit = $authUser->hasPermission('users.edit');
        $canGroups = $authUser->hasPermission('users.groups');

        $groups = UserGroup::orderBy('name')->get();
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

        $firstName = $data['first_name'] ?? null;
        $lastName = $data['last_name'] ?? null;
        $autoName = trim(($firstName ?? '') . ' ' . ($lastName ?? ''));

        $update = [
            'name'              => $autoName ?: strstr($data['email'], '@', true),
            'first_name'        => $firstName,
            'last_name'         => $lastName,
            'email'             => $data['email'],
            'group_id'          => $data['group_id'] ?? null,
            'additional_groups' => array_filter(
                $data['additional_groups'] ?? [],
                fn ($id) => $id != ($data['group_id'] ?? null),
            ),
            'is_active' => $request->boolean('is_active'),
        ];

        if ($request->filled('password')) {
            $update['password'] = $data['password'];
        }

        $user->update($update);

        Logger::adminAction('Редактировал пользователя', 'edit', 'user', $user->id, $user->email);

        return response()->json(['ok' => true, 'message' => 'Пользователь сохранён']);
    }

    public function sendPassword(User $user)
    {
        $password = Str::random(12);
        $user->update(['password' => bcrypt($password)]);

        try {
            Mail::raw(
                "Здравствуйте!\n\nВаши данные для входа в панель управления:\n\nEmail: {$user->email}\nПароль: {$password}\n\nРекомендуем сменить пароль после первого входа.",
                function ($message) use ($user) {
                    $message->to($user->email)
                            ->subject('Данные для входа в панель управления');
                },
            );

            Logger::adminAction('Отправил пароль пользователю', 'other', 'user', $user->id, $user->email);

            return response()->json([
                'ok'      => true,
                'message' => "Пароль отправлен на {$user->email}",
            ]);
        } catch (\Exception $e) {
            Log::error('Не удалось отправить пароль пользователю ' . $user->email . ': ' . $e->getMessage(), [
                'user_id'   => $user->id,
                'exception' => $e,
            ]);
            Logger::adminAction('Ошибка отправки пароля пользователю', 'error', 'user', $user->id, $user->email);

            return response()->json([
                'ok'      => false,
                'message' => 'Пароль обновлён, но письмо не отправлено. Проверьте настройки SMTP.',
            ], 500);
        }
    }

    public function updateGroup(UpdateUserGroupAssignRequest $request, User $user)
    {
        $data = $request->validated();
        $oldGroup = $user->group?->name ?? '—';
        $newId = $data['group_id'] ?? null;
        $newGroup = $newId ? (UserGroup::find($newId)?->name ?? '—') : '—';

        $user->update(['group_id' => $newId]);

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
        $user->delete();
        Logger::adminAction('Удалил пользователя', 'delete', 'user', $id, $email);

        return response()->json(['ok' => true, 'message' => 'Пользователь удалён']);
    }
}
