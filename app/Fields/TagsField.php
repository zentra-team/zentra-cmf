<?php

namespace App\Fields;

class TagsField extends BaseField
{
    public function getType(): string
    {
        return 'tags';
    }

    public function getName(): string
    {
        return 'Теги';
    }

    public function getIcon(): string
    {
        return 'bi-tags';
    }

    public function getGroup(): string
    {
        return 'data';
    }

    public function getDatabaseColumn(): string
    {
        return 'text';
    }

    public function renderEdit(mixed $value, array $config): string
    {
        $name = e($config['name'] ?? '');
        $val = e((string) ($value ?? $config['default'] ?? ''));
        $label = e($config['label'] ?? '');
        $desc = $config['description'] ?? '';
        $id = 'field_' . ($config['alias'] ?? uniqid());

        $maxItems = (int) ($config['config']['max_items'] ?? 0);
        $maxAttr = $maxItems > 0 ? " data-max-items=\"{$maxItems}\"" : '';
        $hint = $maxItems > 0 ? " (максимум {$maxItems})" : '';

        $html = $label ? "<label class=\"form-label\" for=\"{$id}\">{$label}</label>" : '';
        $html .= "<input type=\"text\" id=\"{$id}\" name=\"{$name}\" class=\"form-control ztr-tags-field\" value=\"{$val}\""
               . " placeholder=\"Теги через запятую{$hint}\"{$maxAttr}>";

        if ($desc) {
            $html .= '<div class="form-text">' . e($desc) . '</div>';
        }

        return $html;
    }

    public function save(mixed $input): mixed
    {
        if (!$input) {
            return '';
        }

        $tags = array_map('trim', explode(',', (string) $input));
        $tags = array_filter($tags, fn ($t) => $t !== '');
        $tags = array_unique($tags);

        return implode(',', $tags);
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        if (!$value) {
            return '';
        }
        $tags = array_map('trim', explode(',', (string) $value));

        if ($template !== null) {
            $items = implode('', array_map(
                fn ($tag) => str_replace('[value]', e($tag), $template),
                $tags,
            ));

            return $items;
        }

        return implode(', ', array_map('e', $tags));
    }

    public function toArray(mixed $value): array
    {
        if (!$value) {
            return ['tags' => []];
        }

        return ['tags' => array_map('trim', explode(',', (string) $value))];
    }

    public function fromArray(array $data): mixed
    {
        return implode(',', $data['tags'] ?? []);
    }
}
