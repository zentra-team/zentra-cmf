<?php

namespace App\Rules;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class AssignableGroup implements ValidationRule
{
    public function validate(string $attribute, mixed $value, \Closure $fail): void
    {
        if (empty($value)) {
            return;
        }

        $group = UserGroup::find($value);

        if (!$group || !$group->hasPermission('all')) {
            return;
        }

        /** @var User|null $actor */
        $actor = Auth::guard('admin')->user();

        if ($actor && $actor->group?->hasPermission('all')) {
            return;
        }

        $fail('Недостаточно прав для назначения группы с полным доступом.');
    }
}
