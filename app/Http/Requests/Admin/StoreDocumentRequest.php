<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rubricId = $this->input('rubric_id');

        return [
            'title' => ['required', 'string', 'max:255'],
            'alias' => [
                'nullable', 'string', 'max:255', 'alpha_dash',
                Rule::unique('documents')->where(fn ($q) => $q->where('rubric_id', $rubricId)),
            ],
            'rubric_id' => ['required', 'exists:rubrics,id'],
            'status'    => ['nullable', 'in:0,1,2'],
        ];
    }

    public function messages(): array
    {
        return [
            'alias.unique' => 'Такой псевдоним уже существует в этой рубрике.',
        ];
    }
}
