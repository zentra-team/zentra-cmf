<?php

namespace App\Fields;

abstract class BaseField implements FieldInterface
{
    public function validate(mixed $input, array $rules): bool
    {
        return true;
    }

    public function toArray(mixed $value): array
    {
        return ['value' => $value];
    }

    public function fromArray(array $data): mixed
    {
        return $data['value'] ?? null;
    }

    public function output(mixed $value, ?string $template = null, array $config = []): string
    {
        $str = (string) ($value ?? '');

        if ($template !== null) {
            return str_replace('[value]', e($str), $template);
        }

        return e($str);
    }

    public function getTemplateInfo(): ?array
    {
        return null;
    }

    protected function safePath(string $path): string
    {
        if ($path === '' || str_starts_with($path, '/') || str_starts_with($path, 'https://') || str_starts_with($path, 'http://')) {
            return $path;
        }

        return '';
    }

    protected function splitOption(string $opt): array
    {
        $pos = strpos($opt, '=');

        if ($pos !== false) {
            return [substr($opt, 0, $pos), substr($opt, $pos + 1)];
        }

        return [$opt, $opt];
    }

    protected function replaceParts(string $template, array $parts, string $alias): string
    {
        foreach ($parts as $i => $part) {
            $template = str_replace(
                ["[field:{$alias}:{$i}]", "[value:{$i}]"],
                e((string) $part),
                $template,
            );
        }

        return $template;
    }
}
