<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Setting;
use App\Support\DocumentUrl;

class PageRenderer
{
    public function __construct(
        private readonly BlockRenderer $blockRenderer,
        private readonly ChildrenRenderer $childrenRenderer,
    ) {
    }

    public function render(Document $document): string
    {
        $document->loadMissing(['rubric', 'fields']);

        $rubric = $document->rubric;
        $template = trim($rubric?->template ?? '');

        $html = $template === ''
            ? $this->autoLayout($document)
            : $this->applyTemplate($template, $document);

        $html = $this->blockRenderer->expand($html);

        return $this->childrenRenderer->process($html, $document);
    }

    private function applyTemplate(string $template, Document $document): string
    {
        $html = str_replace('[title]', e($document->title), $template);

        $fieldMap = $this->buildFieldMap($document);

        $html = preg_replace_callback(
            '/\[field:([a-zA-Z0-9_\-]+)\](.*?)\[\/field:\1\]/s',
            fn (array $m) => $this->renderFieldValue($fieldMap, $m[1], $m[2]),
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[field:([a-zA-Z0-9_\-]+)\]/',
            fn (array $m) => $this->renderFieldValue($fieldMap, $m[1], null),
            $html,
        ) ?? $html;

        [$html, $codeBlocks] = $this->extractCodeBlocks($html);
        $html = $this->processDocNav($html, $document);
        $html = strtr($html, $codeBlocks);

        return $html;
    }

    /** @return array{string, array<string, string>} */
    private function extractCodeBlocks(string $html): array
    {
        $blocks = [];
        $i = 0;

        $html = preg_replace_callback(
            '/<(?:code|pre)(?:\s[^>]*)?>.*?<\/(?:code|pre)>/si',
            function (array $m) use (&$blocks, &$i): string {
                $key = "\x00CODEBLOCK{$i}\x00";
                $blocks[$key] = $m[0];
                $i++;

                return $key;
            },
            $html,
        ) ?? $html;

        return [$html, $blocks];
    }

    private function renderFieldValue(array $fieldMap, string $alias, ?string $template): string
    {
        $fd = $fieldMap[$alias] ?? null;

        if ($fd === null) {
            return '';
        }

        if ($fd['instance'] === null) {
            return (string) $fd['raw'];
        }

        return $fd['instance']->output($fd['raw'], $template, $fd['config'] ?? []);
    }

    private function buildFieldMap(Document $document): array
    {
        $rubric = $document->rubric;

        if ($rubric === null) {
            return [];
        }

        $rubricFields = $rubric->fields->keyBy('id');
        $fm = app(FieldManager::class);

        $map = [];

        foreach ($document->fields as $docField) {
            $rf = $rubricFields->get($docField->field_id);

            if ($rf === null) {
                continue;
            }

            $raw = $docField->value ?? '';
            $instance = $fm->instance($rf->type);

            $map[$rf->alias] = [
                'raw'      => $raw,
                'instance' => $instance,
                'config'   => is_array($rf->config) ? $rf->config : [],
            ];
        }

        return $map;
    }

    private function processDocNav(string $html, Document $document): string
    {
        $hasPrevTag = str_contains($html, '[doc:prev:') || str_contains($html, '[if:doc:prev]');
        $hasNextTag = str_contains($html, '[doc:next:') || str_contains($html, '[if:doc:next]');

        if (!$hasPrevTag && !$hasNextTag) {
            return $html;
        }

        if ($document->alias === null || $document->alias === '') {
            $html = $this->processCondBlock($html, 'doc:prev', false);
            $html = $this->processCondBlock($html, 'doc:next', false);
            $html = str_replace(['[doc:prev:title]', '[doc:prev:url]', '[doc:next:title]', '[doc:next:url]'], '', $html);

            return $html;
        }

        $rubricId = $document->rubric_id;
        $position = $document->position;
        $docId = $document->id;

        $prev = $hasPrevTag
            ? Document::where('rubric_id', $rubricId)
                ->where('status', Document::STATUS_ACTIVE)
                ->whereNotNull('alias')
                ->where(
                    fn ($q) => $q
                    ->where('position', '<', $position)
                    ->orWhere(fn ($q2) => $q2->where('position', $position)->where('id', '<', $docId)),
                )
                ->orderByDesc('position')->orderByDesc('id')
                ->first(['id', 'title', 'alias', 'rubric_id'])
            : null;

        $next = $hasNextTag
            ? Document::where('rubric_id', $rubricId)
                ->where('status', Document::STATUS_ACTIVE)
                ->whereNotNull('alias')
                ->where(
                    fn ($q) => $q
                    ->where('position', '>', $position)
                    ->orWhere(fn ($q2) => $q2->where('position', $position)->where('id', '>', $docId)),
                )
                ->orderBy('position')->orderBy('id')
                ->first(['id', 'title', 'alias', 'rubric_id'])
            : null;

        $rubricAlias = $document->rubric?->alias ?? '';

        $html = $this->processCondBlock($html, 'doc:prev', $prev !== null);
        $html = $this->processCondBlock($html, 'doc:next', $next !== null);

        $html = str_replace('[doc:prev:title]', $prev ? e($prev->title) : '', $html);
        $html = str_replace('[doc:prev:url]', $prev ? $this->docUrl($rubricAlias, $prev->alias) : '', $html);
        $html = str_replace('[doc:next:title]', $next ? e($next->title) : '', $html);
        $html = str_replace('[doc:next:url]', $next ? $this->docUrl($rubricAlias, $next->alias) : '', $html);

        return $html;
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

    private function docUrl(string $rubricAlias, ?string $docAlias): string
    {
        return DocumentUrl::build($rubricAlias, $docAlias, Setting::getValue('url_suffix', ''));
    }

    private function autoLayout(Document $document): string
    {
        $rubric = $document->rubric;

        $html = '<article class="ztr-document">';
        $html .= '<h1 class="ztr-document-title">' . e($document->title) . '</h1>';

        if ($rubric !== null) {
            $fieldMap = $this->buildFieldMap($document);
            $rubricFields = $rubric->fields;

            foreach ($rubricFields as $rf) {
                $value = $this->renderFieldValue($fieldMap, $rf->alias, null);

                if ($value === '' || $value === null) {
                    continue;
                }

                $html .= '<div class="ztr-field mb-3">';
                $html .= '<div class="ztr-field-label text-muted small fw-semibold mb-1">'
                       . e($rf->title) . '</div>';
                $html .= '<div class="ztr-field-value">' . $value . '</div>';
                $html .= '</div>';
            }
        }

        $html .= '</article>';

        return $html;
    }
}
