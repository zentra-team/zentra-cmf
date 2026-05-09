<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Rubric;
use App\Models\Setting;
use App\Services\LayoutRenderer;
use App\Services\Logger;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\View;

class PublicController extends Controller
{
    public function __construct(
        private readonly LayoutRenderer $layoutRenderer,
    ) {
    }

    public function show(Request $request, string $path = ''): Response
    {
        $path = trim($path, '/');

        if ($path === '') {
            return $this->handleHome($request);
        }

        $suffix = Setting::getValue('url_suffix', '');

        if ($suffix !== '' && str_ends_with($path, $suffix)) {
            $path = substr($path, 0, -strlen($suffix));
        }

        $segments = explode('/', $path, 2);

        if (count($segments) === 2) {
            [$rubricAlias, $docAlias] = $segments;

            $rubric = Rubric::where('alias', $rubricAlias)->first();

            if ($rubric !== null) {
                $document = Document::where('rubric_id', $rubric->id)
                    ->where('alias', $docAlias)
                    ->where('status', Document::STATUS_ACTIVE)
                    ->first();

                if ($document !== null && $this->isPublished($document)) {
                    return $this->renderDocument($document, $request);
                }
            }

            $redirect = $this->checkTwoSegmentRedirect($rubricAlias, $docAlias, $suffix);

            if ($redirect !== null) {
                return $redirect;
            }
        }

        $singleAlias = $segments[0];

        $rubric = Rubric::where('alias', $singleAlias)->first();

        if ($rubric !== null) {
            $indexDoc = Document::where('rubric_id', $rubric->id)
                ->whereNull('alias')
                ->where('status', Document::STATUS_ACTIVE)
                ->first();

            if ($indexDoc !== null && $this->isPublished($indexDoc)) {
                return $this->renderDocument($indexDoc, $request);
            }
        }

        $document = Document::where('alias', $singleAlias)
            ->where('status', Document::STATUS_ACTIVE)
            ->first();

        if ($document !== null && $this->isPublished($document)) {
            return $this->renderDocument($document, $request);
        }

        $redirect = $this->checkSingleSegmentRedirect($singleAlias, $suffix);

        if ($redirect !== null) {
            return $redirect;
        }

        Logger::error404($request);

        $custom404Id = (int) Setting::getValue('page_404_id', '');

        if ($custom404Id > 0) {
            $doc404 = Document::where('id', $custom404Id)
                ->where('status', Document::STATUS_ACTIVE)
                ->first();

            if ($doc404 !== null && $this->isPublished($doc404)) {
                $rendered = $this->layoutRenderer->render($doc404, $request);

                return response($rendered->getContent(), 404)
                    ->header('Content-Type', 'text/html; charset=utf-8');
            }
        }

        $html = View::make('errors.404', [
            'siteName' => $this->siteName(),
        ])->render();

        return response($html, 404)->header('Content-Type', 'text/html; charset=utf-8');
    }

    private function handleHome(Request $request): Response
    {
        $document = Document::where('alias', 'index')
            ->where('status', Document::STATUS_ACTIVE)
            ->first()
            ?? Document::where('status', Document::STATUS_ACTIVE)
            ->orderBy('position')
            ->orderBy('id')
            ->first();

        if ($document !== null && $this->isPublished($document)) {
            return $this->renderDocument($document, $request);
        }

        return $this->layoutRenderer->renderWelcome($request);
    }

    private function renderDocument(Document $document, Request $request): Response
    {
        Document::where('id', $document->id)->increment('views');

        $document->loadMissing('rubric');
        $request->attributes->set(\App\Services\PublicCacheManager::REQUEST_DOC_ATTR, $document);

        return $this->layoutRenderer->render($document, $request);
    }

    private function isPublished(Document $document): bool
    {
        if ($document->published_at !== null && $document->published_at->isFuture()) {
            return false;
        }

        return !($document->unpublished_at !== null && $document->unpublished_at->isPast())

        ;
    }

    private ?bool $aliasHistoryEnabledCache = null;

    private function aliasHistoryEnabled(): bool
    {
        return $this->aliasHistoryEnabledCache
            ??= (Setting::getValue('redirects_use_alias_history', '1') === '1');
    }

    private function checkTwoSegmentRedirect(string $rubricAlias, string $docAlias, string $suffix): ?Response
    {
        if (!$this->aliasHistoryEnabled()) {
            return null;
        }

        $oldRubric = DB::table('rubric_alias_history')
            ->where('old_alias', $rubricAlias)
            ->first();

        if ($oldRubric !== null) {
            $rubric = Rubric::find($oldRubric->rubric_id);

            if ($rubric !== null) {
                $currentAlias = trim($rubric->alias ?? '');

                $doc = Document::where('rubric_id', $rubric->id)
                    ->where('alias', $docAlias)
                    ->where('status', Document::STATUS_ACTIVE)
                    ->first();

                if ($doc !== null && $this->isPublished($doc)) {
                    $newUrl = $currentAlias !== '' ? "/{$currentAlias}/{$docAlias}" : "/{$docAlias}";

                    return response('', 301)->header('Location', $newUrl . $suffix);
                }
            }
        }

        $rubric = Rubric::where('alias', $rubricAlias)->first();

        if ($rubric !== null) {
            $oldDoc = DB::table('document_alias_history')
                ->where('old_alias', $docAlias)
                ->where('old_rubric_id', $rubric->id)
                ->first();

            if ($oldDoc !== null) {
                $doc = Document::where('id', $oldDoc->document_id)
                    ->where('status', Document::STATUS_ACTIVE)
                    ->first();

                if ($doc !== null && $this->isPublished($doc)) {
                    $currentRubricAlias = trim($doc->rubric?->alias ?? '');
                    $newUrl = $currentRubricAlias !== ''
                        ? "/{$currentRubricAlias}/{$doc->alias}"
                        : "/{$doc->alias}";

                    return response('', 301)->header('Location', $newUrl . $suffix);
                }
            }
        }

        if ($oldRubric !== null) {
            $rubric = Rubric::find($oldRubric->rubric_id);

            if ($rubric !== null) {
                $oldDoc = DB::table('document_alias_history')
                    ->where('old_alias', $docAlias)
                    ->where('old_rubric_id', $rubric->id)
                    ->first();

                if ($oldDoc !== null) {
                    $doc = Document::where('id', $oldDoc->document_id)
                        ->where('status', Document::STATUS_ACTIVE)
                        ->first();

                    if ($doc !== null && $this->isPublished($doc)) {
                        $currentRubricAlias = trim($doc->rubric?->alias ?? '');
                        $newUrl = $currentRubricAlias !== ''
                            ? "/{$currentRubricAlias}/{$doc->alias}"
                            : "/{$doc->alias}";

                        return response('', 301)->header('Location', $newUrl . $suffix);
                    }
                }
            }
        }

        return null;
    }

    private function checkSingleSegmentRedirect(string $alias, string $suffix): ?Response
    {
        if (!$this->aliasHistoryEnabled()) {
            return null;
        }

        $oldRubric = DB::table('rubric_alias_history')
            ->where('old_alias', $alias)
            ->first();

        if ($oldRubric !== null) {
            $rubric = Rubric::find($oldRubric->rubric_id);

            if ($rubric !== null && trim($rubric->alias ?? '') !== '') {
                return response('', 301)->header('Location', '/' . $rubric->alias . $suffix);
            }
        }

        $oldDoc = DB::table('document_alias_history')
            ->where('old_alias', $alias)
            ->whereNull('old_rubric_id')
            ->first();

        if ($oldDoc !== null) {
            $doc = Document::where('id', $oldDoc->document_id)
                ->where('status', Document::STATUS_ACTIVE)
                ->first();

            if ($doc !== null && $this->isPublished($doc)) {
                $rubricAlias = trim($doc->rubric?->alias ?? '');
                $newUrl = $rubricAlias !== ''
                    ? "/{$rubricAlias}/{$doc->alias}"
                    : "/{$doc->alias}";

                return response('', 301)->header('Location', $newUrl . $suffix);
            }
        }

        return null;
    }

    private function siteName(): string
    {
        try {
            return config('app.name', 'Zentra CMF');
        } catch (\Throwable) {
            return 'Zentra CMF';
        }
    }
}
