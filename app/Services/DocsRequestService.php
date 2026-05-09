<?php

namespace App\Services;

use App\Models\DocsRequest;
use App\Models\Rubric;
use App\Models\RubricField;
use Illuminate\Database\Eloquent\Collection;

class DocsRequestService
{
    public function indexData(): array
    {
        return [
            'requests' => DocsRequest::orderBy('id')->get(),
            'rubrics'  => Rubric::orderBy('title')->get(),
        ];
    }

    public function rubrics(): Collection
    {
        return Rubric::orderBy('title')->get();
    }

    public function fieldsForRubrics(array $rubricIds): Collection
    {
        return RubricField::whereIn('rubric_id', $rubricIds)
            ->join('rubrics', 'rubrics.id', '=', 'rubric_fields.rubric_id')
            ->orderBy('rubrics.title')
            ->orderBy('rubric_fields.position')
            ->select('rubric_fields.*', 'rubrics.title as rubric_title')
            ->get();
    }

    public function copy(DocsRequest $docsRequest): array
    {
        $newAlias = $docsRequest->alias . '-copy';
        $counter  = 1;

        while (DocsRequest::where('alias', $newAlias)->exists()) {
            $newAlias = $docsRequest->alias . '-copy-' . $counter++;
        }

        $copy        = $docsRequest->replicate();
        $copy->title = $docsRequest->title . ' (копия)';
        $copy->alias = $newAlias;
        $copy->save();

        $rubricNames = $copy->rubrics()->pluck('title')->join(', ') ?: '—';

        return ['copy' => $copy, 'rubricNames' => $rubricNames];
    }
}
