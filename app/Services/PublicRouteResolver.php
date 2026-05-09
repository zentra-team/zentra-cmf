<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Rubric;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

class PublicRouteResolver
{
    public function urlSuffix(): string
    {
        return Setting::getValue('url_suffix', '');
    }

    public function aliasHistoryEnabled(): bool
    {
        return Setting::getValue('redirects_use_alias_history', '1') === '1';
    }

    public function rubricByAlias(string $alias): ?Rubric
    {
        return Rubric::where('alias', $alias)->first();
    }

    public function rubricById(int $id): ?Rubric
    {
        return Rubric::find($id);
    }

    public function docInRubric(int $rubricId, string $docAlias): ?Document
    {
        return Document::where('rubric_id', $rubricId)
            ->where('alias', $docAlias)
            ->where('status', Document::STATUS_ACTIVE)
            ->first();
    }

    public function indexDocInRubric(int $rubricId): ?Document
    {
        return Document::where('rubric_id', $rubricId)
            ->whereNull('alias')
            ->where('status', Document::STATUS_ACTIVE)
            ->first();
    }

    public function docBySingleAlias(string $alias): ?Document
    {
        return Document::where('alias', $alias)
            ->where('status', Document::STATUS_ACTIVE)
            ->first();
    }

    public function homePageDoc(): ?Document
    {
        return Document::where('alias', 'index')
            ->where('status', Document::STATUS_ACTIVE)
            ->first()
            ?? Document::where('status', Document::STATUS_ACTIVE)
            ->orderBy('position')
            ->orderBy('id')
            ->first();
    }

    public function incrementViews(Document $document): void
    {
        Document::where('id', $document->id)->increment('views');
    }

    public function custom404Doc(): ?Document
    {
        $id = (int) Setting::getValue('page_404_id', '');

        if ($id <= 0) {
            return null;
        }

        return Document::where('id', $id)
            ->where('status', Document::STATUS_ACTIVE)
            ->first();
    }

    public function oldRubricRecord(string $alias): ?object
    {
        return DB::table('rubric_alias_history')
            ->where('old_alias', $alias)
            ->first();
    }

    public function docById(int $id): ?Document
    {
        return Document::where('id', $id)
            ->where('status', Document::STATUS_ACTIVE)
            ->first();
    }

    public function oldDocRecord(string $alias, ?int $oldRubricId): ?object
    {
        return DB::table('document_alias_history')
            ->where('old_alias', $alias)
            ->where('old_rubric_id', $oldRubricId)
            ->first();
    }
}
