<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ReorderNavigationItemsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'             => 'required|array',
            'items.*.id'        => 'required|integer',
            'items.*.parent_id' => 'nullable|integer',
            'items.*.position'  => 'required|integer',
        ];
    }
}
