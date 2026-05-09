<?php

namespace App\Fields;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;

class MarkdownField extends BaseField
{
    public function getType(): string
    {
        return 'markdown';
    }

    public function getName(): string
    {
        return 'Markdown редактор';
    }

    public function getIcon(): string
    {
        return 'bi-markdown';
    }

    public function getGroup(): string
    {
        return 'text';
    }

    public function getDatabaseColumn(): string
    {
        return 'text';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $name = e($config['name'] ?? '');
        $val = (string) ($value ?? $config['default'] ?? '');
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<textarea id=\"{$id}\" name=\"{$name}\" class=\"ztr-markdown\">"
               . e($val)
               . '</textarea>';

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        return (string) ($input ?? '');
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $html = $this->toHtml((string) ($value ?? ''));

        if ($template !== null) {
            return str_replace('[value]', $html, $template);
        }

        return $html;
    }

    public function toArray(mixed $value): array
    {
        return [
            'markdown' => (string) ($value ?? ''),
            'html'     => $this->toHtml((string) ($value ?? '')),
        ];
    }

    public function fromArray(array $data): mixed
    {
        return $data['markdown'] ?? '';
    }

    private function toHtml(string $markdown): string
    {
        if ($markdown === '') {
            return '';
        }

        $markdown = preg_replace_callback(
            '/^(```\S*)\s+title="([^"]+)"/m',
            fn ($m) => $m[1] . ':::' . base64_encode($m[2]),
            $markdown,
        );

        $environment = new Environment([
            'html_input'         => 'allow',
            'allow_unsafe_links' => false,
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());

        $html = (new MarkdownConverter($environment))->convert($markdown)->getContent();

        $html = preg_replace_callback(
            '/class="language-([^":]+):::([^"]+)"/',
            fn ($m) => 'class="language-' . $m[1] . '" data-title="'
                     . htmlspecialchars(base64_decode($m[2])) . '"',
            $html,
        ) ?? $html;

        $html = preg_replace(
            '/<a\s+href="(https?:\/\/[^"]+)"/',
            '<a href="$1" target="_blank" rel="noopener noreferrer"',
            $html,
        ) ?? $html;

        return $html;
    }
}
