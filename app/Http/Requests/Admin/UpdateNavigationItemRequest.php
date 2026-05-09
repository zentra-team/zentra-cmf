<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateNavigationItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'title'       => 'required|string|max:255',
            'parent_id'   => 'nullable|integer|exists:navigation_items,id',
            'url'         => 'nullable|string|max:500',
            'target'      => 'nullable|string|in:_self,_blank',
            'css_class'   => 'nullable|string|max:200',
            'css_id'      => 'nullable|string|max:100',
            'css_style'   => 'nullable|string|max:500',
            'description' => 'nullable|string',
            'image'       => 'nullable|string|max:500',
            'icon'        => 'nullable|string|max:500',
            'extra_html'  => 'nullable|string',
        ];
    }
}
