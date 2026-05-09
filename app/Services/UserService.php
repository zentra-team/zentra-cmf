<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class UserService
{
    public function list(Request $request, bool $canList): LengthAwarePaginator
    {
        if (!$canList) {
            return User::query()->whereRaw('false')->paginate(50);
        }

        $query = User::with('group')->orderBy('id');

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

        return $query->paginate(50)->withQueryString();
    }

    public function create(array $data): User
    {
        return User::create([
            'name'      => strstr($data['email'], '@', true),
            'email'     => $data['email'],
            'password'  => bcrypt(Str::random(16)),
            'is_active' => true,
        ]);
    }

    public function update(User $user, array $data, bool $fillPassword = false): void
    {
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
            'is_active' => (bool) ($data['is_active'] ?? false),
        ];

        if ($fillPassword && !empty($data['password'])) {
            $update['password'] = $data['password'];
        }

        $user->update($update);
    }

    public function assignGroup(User $user, ?int $groupId): void
    {
        $user->update(['group_id' => $groupId]);
    }

    public function resetPassword(User $user): array
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

            return ['ok' => true, 'message' => "Пароль отправлен на {$user->email}"];
        } catch (\Exception $e) {
            Log::error('Не удалось отправить пароль пользователю ' . $user->email . ': ' . $e->getMessage(), [
                'user_id'   => $user->id,
                'exception' => $e,
            ]);

            return ['ok' => false, 'message' => 'Пароль обновлён, но письмо не отправлено. Проверьте настройки SMTP.'];
        }
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    public function allGroups(): Collection
    {
        return UserGroup::orderBy('name')->get();
    }

    public function touchLastLogin(User $user): void
    {
        $user->update(['last_login_at' => now()]);
    }

    public function touchLastSeen(int $userId): void
    {
        User::where('id', $userId)->update(['last_seen_at' => now()]);
    }
}
