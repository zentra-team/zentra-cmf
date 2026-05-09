<?php

namespace App\Fields;

interface FieldInterface
{
    public function getType(): string;

    public function getName(): string;

    public function getIcon(): string;

    public function getGroup(): string;

    public function renderEdit(mixed $value, array $config): string;

    public function validate(mixed $input, array $rules): bool;

    public function save(mixed $input): mixed;

    public function output(mixed $value, ?string $template = null, array $config = []): string;

    public function toArray(mixed $value): array;

    public function fromArray(array $data): mixed;

    public function getDatabaseColumn(): string;

    public function getTemplateInfo(): ?array;
}
