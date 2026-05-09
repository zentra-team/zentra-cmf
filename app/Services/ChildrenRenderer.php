<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Setting;
use App\Support\DocumentUrl;

class ChildrenRenderer
{
    public function __construct(
        private readonly BlockRenderer $blockRenderer,
    ) {
    }

    public function process(string $html, Document $document): string
    {
        if (
            !str_contains($html, '[children]') &&
            !str_contains($html, '[if:has_children]') &&
            !str_contains($html, '[if:no_children]')
        ) {
            return $html;
        }

        $hasChildren = null;

        if (str_contains($html, '[if:has_children]')) {
            $hasChildren ??= $this->hasChildren($document->id);
            $html = $this->processCondBlock($html, 'has_children', $hasChildren);
        }

        if (str_contains($html, '[if:no_children]')) {
            $hasChildren ??= $this->hasChildren($document->id);
            $html = $this->processCondBlock($html, 'no_children', !$hasChildren);
        }

        if (str_contains($html, '[children]')) {
            $html = preg_replace_callback(
                '/\[children\](.*?)\[\/children\]/s',
                fn (array $m) => $this->render($document->id, $m[1]),
                $html,
            ) ?? $html;
        }

        return $html;
    }

    private function hasChildren(int $docId): bool
    {
        return Document::where('parent_doc_id', $docId)
            ->where('status', Document::STATUS_ACTIVE)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('unpublished_at')->orWhere('unpublished_at', '>', now()))
            ->exists();
    }

    private function processCondBlock(string $html, string $tag, bool $show): string
    {
        $open = '[if:' . $tag . ']';
        $close = '[/if:' . $tag . ']';

        if (!str_contains($html, $open)) {
            return $html;
        }

        if ($show) {
            return str_replace([$open, $close], '', $html);
        }

        while (($start = strpos($html, $open)) !== false) {
            $end = strpos($html, $close, $start);

            if ($end === false) {
                break;
            }

            $html = substr($html, 0, $start) . substr($html, $end + strlen($close));
        }

        return $html;
    }

    private function render(int $parentDocId, string $template): string
    {
        $children = Document::where('parent_doc_id', $parentDocId)
            ->where('status', Document::STATUS_ACTIVE)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('unpublished_at')->orWhere('unpublished_at', '>', now()))
            ->orderBy('position')
            ->orderBy('id')
            ->with(['rubric.fields', 'fields'])
            ->get();

        if ($children->isEmpty()) {
            return '';
        }

        $suffix = Setting::getValue('url_suffix', '');
        $parts = [];

        foreach ($children as $child) {
            $item = $this->renderItem($child, $template, $suffix);
            $parts[] = $this->blockRenderer->expand($item);
        }

        return implode('', $parts);
    }

    private function renderItem(Document $child, string $template, string $suffix): string
    {
        $rubricAlias = $child->rubric?->alias ?? '';
        $url = DocumentUrl::build($rubricAlias, $child->alias, $suffix);

        $date = $child->published_at ?? $child->created_at;
        $dateStr = $date?->format('d.m.Y') ?? '';

        $html = str_replace('[doc:title]', e($child->title), $template);
        $html = str_replace('[doc:url]', $url, $html);
        $html = str_replace('[doc:id]', (string) $child->id, $html);
        $html = str_replace('[doc:views]', (string) ($child->views ?? 0), $html);
        $html = str_replace('[doc:date]', $dateStr, $html);
        $html = str_replace('[doc:alias]', e($child->alias ?? ''), $html);
        $html = str_replace('[doc:rubric]', e($child->rubric?->title ?? ''), $html);

        $html = preg_replace_callback(
            '/\[field:([a-zA-Z0-9_\-]+)\]/',
            fn (array $m) => $this->fieldValue($child, $m[1]),
            $html,
        ) ?? $html;

        return $html;
    }

    private function fieldValue(Document $child, string $alias): string
    {
        $rubric = $child->rubric;

        if ($rubric === null) {
            return '';
        }

        $rf = $rubric->fields->firstWhere('alias', $alias);

        if ($rf === null) {
            return '';
        }

        $docField = $child->fields->firstWhere('field_id', $rf->id);

        if ($docField === null) {
            return '';
        }

        $instance = app(FieldManager::class)->instance($rf->type);

        if ($instance === null) {
            return e((string) $docField->value);
        }

        return $instance->output(
            $docField->value ?? '',
            null,
            is_array($rf->config) ? $rf->config : [],
        );
    }
}
