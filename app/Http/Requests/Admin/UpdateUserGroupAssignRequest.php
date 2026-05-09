<?php

namespace App\Http\Requests\Admin;

use App\Rules\AssignableGroup;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserGroupAssignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'group_id' => ['nullable', 'exists:user_groups,id', new AssignableGroup()],
        ];
    }
}
