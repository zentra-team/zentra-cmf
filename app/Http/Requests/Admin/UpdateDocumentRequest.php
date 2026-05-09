<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $document = $this->route('document');
        $documentId = $document->id ?? $document;
        $rubricId = $this->input('rubric_id', $document->rubric_id ?? null);

        return [
            'title' => ['required', 'string', 'max:255'],
            'alias' => [
                'nullable', 'string', 'max:255',
                'regex:/^[a-z0-9_\-]+(\/[a-z0-9_\-]+)*$/',
                Rule::unique('documents')
                    ->where(fn ($q) => $q->where('rubric_id', $rubricId))
                    ->ignore($documentId),
            ],
            'meta_title'            => ['nullable', 'string', 'max:255'],
            'meta_description'      => ['nullable', 'string'],
            'meta_robots'           => ['nullable', 'string', 'max:50'],
            'og_title'              => ['nullable', 'string', 'max:255'],
            'og_description'        => ['nullable', 'string'],
            'og_image'              => ['nullable', 'string', 'max:2048'],
            'sitemap_changefreq'    => ['nullable', 'string', 'max:20'],
            'sitemap_priority'      => ['nullable', 'numeric', 'min:0', 'max:1'],
            'public_cache_disabled' => ['nullable', 'in:0,1'],
            'public_cache_ttl'      => ['nullable', 'integer', 'min:0', 'max:604800'],
            'published_at'          => ['nullable', 'date'],
            'unpublished_at'        => ['nullable', 'date'],
            'status'                => ['nullable', 'in:0,1,2'],
            'position'              => ['nullable', 'integer'],
            'nav_item_id'           => ['nullable', 'exists:navigation_items,id'],
            'breadcrumb_title'      => ['nullable', 'string', 'max:255'],
            'parent_doc_id'         => ['nullable', 'exists:documents,id'],
            'fields'                => ['nullable', 'array'],
        ];
    }

    public function messages(): array
    {
        return [
            'alias.unique' => 'Такой псевдоним уже существует в этой рубрике.',
            'alias.regex'  => 'Алиас может содержать только строчные латинские буквы, цифры, дефисы и слэши (для вложенных путей).',
        ];
    }
}
