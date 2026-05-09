<?php

namespace App\Fields;

use App\Models\Document;
use App\Models\Setting;
use App\Support\DocumentUrl;

class DocLinkField extends BaseField
{
    public function getType(): string
    {
        return 'doc_link';
    }

    public function getName(): string
    {
        return 'Ссылка на документ';
    }

    public function getIcon(): string
    {
        return 'bi-link-45deg';
    }

    public function getGroup(): string
    {
        return 'relation';
    }

    public function getDatabaseColumn(): string
    {
        return 'jsonb';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $data = is_array($value) ? $value : (is_string($value) ? json_decode($value, true) ?? [] : []);
        $docId = (int) ($data['doc_id'] ?? 0);
        $anchor = $data['anchor'] ?? '';
        $name = e($config['name'] ?? '');
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());

        $selectedTitle = '';
        $selectedRubric = '';

        if ($docId > 0) {
            $doc = Document::with('rubric')->find($docId);

            if ($doc) {
                $selectedTitle = $doc->title;
                $selectedRubric = $doc->rubric?->title ?? '';
            }
        }

        $rubricFilter = (int) ($config['config']['rubric_id'] ?? 0);
        $rubricFilterAttr = $rubricFilter > 0 ? " data-rubric-id=\"{$rubricFilter}\"" : '';

        $showSelected = $docId > 0 ? '' : 'd-none';
        $showSearch = $docId > 0 ? 'd-none' : '';

        $docIdSafe = e((string) $docId);
        $anchorSafe = e($anchor);
        $titleSafe = e($selectedTitle);
        $rubricSafe = e($selectedRubric);

        $html = $label ? "<label class=\"form-label\">{$label}</label>" : '';
        $html .= "<div class=\"doc-link-field\"{$rubricFilterAttr}>";
        $html .= "<div class=\"doc-link-selected {$showSelected}\">";
        $html .= '<div class="doc-link-chip">';
        $html .= '<i class="bi bi-file-earmark-text me-2"></i>';
        $html .= "<span class=\"doc-link-selected-title\">{$titleSafe}</span>";

        if ($selectedRubric !== '') {
            $html .= " <span class=\"doc-link-selected-rubric text-muted\">· {$rubricSafe}</span>";
        }

        $html .= '<button type="button" class="doc-link-clear btn btn-sm btn-link text-danger ms-auto p-0" title="Сбросить">';
        $html .= '<i class="bi bi-x-circle"></i></button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= "<div class=\"doc-link-search position-relative {$showSearch}\">";
        $html .= "<input type=\"text\" id=\"{$id}\" class=\"form-control doc-link-search-input\""
               . ' placeholder="Начните вводить название документа…" autocomplete="off">';
        $html .= '<div class="doc-link-results d-none"></div>';
        $html .= '</div>';
        $html .= "<input type=\"hidden\" name=\"{$name}[doc_id]\" class=\"doc-link-id\" value=\"{$docIdSafe}\">";
        $html .= "<input type=\"text\" name=\"{$name}[anchor]\" class=\"form-control mt-2 doc-link-anchor\""
               . " value=\"{$anchorSafe}\" placeholder=\"Текст ссылки (необязательно - по умолчанию заголовок документа)\">";
        $html .= '</div>';

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        if (!is_array($input)) {
            return null;
        }

        $docId = (int) ($input['doc_id'] ?? 0);

        if ($docId <= 0) {
            return null;
        }

        return json_encode([
            'doc_id' => $docId,
            'anchor' => trim($input['anchor'] ?? ''),
        ]);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $data = is_string($value) ? json_decode($value, true) ?? [] : (is_array($value) ? $value : []);
        $docId = (int) ($data['doc_id'] ?? 0);
        $anchor = $data['anchor'] ?? '';

        if ($docId <= 0) {
            return '';
        }

        $doc = Document::with('rubric')->find($docId);

        if (!$doc) {
            return '';
        }

        $suffix = (string) Setting::getValue('url_suffix', '');
        $url = DocumentUrl::build($doc->rubric?->alias, $doc->alias, $suffix);
        $title = $anchor !== '' ? $anchor : $doc->title;

        if ($template !== null) {
            return $this->replaceParts($template, [$docId, $anchor, $url, $title], $template);
        }

        return '<a href="' . e($url) . '">' . e($title) . '</a>';
    }

    public function toArray(mixed $value): array
    {
        return is_string($value) ? json_decode($value, true) ?? [] : (is_array($value) ? $value : []);
    }

    public function fromArray(array $data): mixed
    {
        return json_encode($data);
    }
}
