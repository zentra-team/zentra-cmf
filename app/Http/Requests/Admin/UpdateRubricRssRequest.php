<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRubricRssRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rubricId = $this->route('rubric')?->id ?? $this->route('rubric');

        return [
            'rss_enabled'              => 'nullable|in:0,1',
            'rss_title'                => 'nullable|string|max:255',
            'rss_description'          => 'nullable|string|max:1000',
            'rss_limit'                => 'nullable|integer|min:1|max:500',
            'rss_description_field_id' => 'nullable|integer|exists:rubric_fields,id',
            'rss_image_field_id'       => 'nullable|integer|exists:rubric_fields,id',
            'rss_category_field_id'    => 'nullable|integer|exists:rubric_fields,id',
        ];
    }
}
