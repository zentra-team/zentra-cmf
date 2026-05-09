<?php

namespace App\Support;

final class DocumentUrl
{
    public static function build(?string $rubricAlias, ?string $docAlias, string $suffix = ''): string
    {
        $rubricAlias = trim((string) $rubricAlias);
        $docAlias = $docAlias === null ? '' : trim($docAlias);

        if ($docAlias === '') {
            return $rubricAlias !== '' ? '/' . $rubricAlias : '/';
        }

        $prefix = $rubricAlias !== '' ? '/' . $rubricAlias : '';

        return $prefix . '/' . $docAlias . $suffix;
    }

    public static function rubric(?string $rubricAlias): string
    {
        $alias = trim((string) $rubricAlias);

        return $alias !== '' ? '/' . $alias : '';
    }
}
