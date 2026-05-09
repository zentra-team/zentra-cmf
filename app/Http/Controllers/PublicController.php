<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\LayoutRenderer;
use App\Services\Logger;
use App\Services\PublicRouteResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class PublicController extends Controller
{
    public function __construct(
        private readonly LayoutRenderer $layoutRenderer,
        private readonly PublicRouteResolver $resolver,
    ) {
    }

    public function show(Request $request, string $path = ''): Response
    {
        $path = trim($path, '/');

        if ($path === '') {
            return $this->handleHome($request);
        }

        $suffix = $this->resolver->urlSuffix();

        if ($suffix !== '' && str_ends_with($path, $suffix)) {
            $path = substr($path, 0, -strlen($suffix));
        }

        $segments = explode('/', $path, 2);

        if (count($segments) === 2) {
            [$rubricAlias, $docAlias] = $segments;

            $rubric = $this->resolver->rubricByAlias($rubricAlias);

            if ($rubric !== null) {
                $document = $this->resolver->docInRubric($rubric->id, $docAlias);

                if ($document !== null && $this->isPublished($document)) {
                    return $this->renderDocument($document, $request);
                }

                $redirect = $this->checkTwoSegmentRedirect($rubricAlias, $docAlias, $suffix);

                if ($redirect !== null) {
                    return $redirect;
                }

                return $this->render404($request);
            }
        }

        $singleAlias = $segments[0];

        $rubric = $this->resolver->rubricByAlias($singleAlias);

        if ($rubric !== null) {
            $indexDoc = $this->resolver->indexDocInRubric($rubric->id);

            if ($indexDoc !== null && $this->isPublished($indexDoc)) {
                return $this->renderDocument($indexDoc, $request);
            }
        }

        $document = $this->resolver->docBySingleAlias($singleAlias);

        if ($document !== null && $this->isPublished($document)) {
            return $this->renderDocument($document, $request);
        }

        $redirect = $this->checkSingleSegmentRedirect($singleAlias, $suffix);

        if ($redirect !== null) {
            return $redirect;
        }

        return $this->render404($request);
    }

    private function handleHome(Request $request): Response
    {
        $document = $this->resolver->homePageDoc();

        if ($document !== null && $this->isPublished($document)) {
            return $this->renderDocument($document, $request);
        }

        return $this->layoutRenderer->renderWelcome($request);
    }

    private function renderDocument(Document $document, Request $request): Response
    {
        $this->resolver->incrementViews($document);

        $document->loadMissing('rubric', 'parentDoc');
        $request->attributes->set(\App\Services\PublicCacheManager::REQUEST_DOC_ATTR, $document);

        return $this->layoutRenderer->render($document, $request);
    }

    private function isPublished(Document $document): bool
    {
        if ($document->published_at !== null && $document->published_at->isFuture()) {
            return false;
        }

        return !($document->unpublished_at !== null && $document->unpublished_at->isPast());
    }

    private ?bool $aliasHistoryEnabledCache = null;

    private function aliasHistoryEnabled(): bool
    {
        return $this->aliasHistoryEnabledCache ??= $this->resolver->aliasHistoryEnabled();
    }

    private function checkTwoSegmentRedirect(string $rubricAlias, string $docAlias, string $suffix): ?Response
    {
        if (!$this->aliasHistoryEnabled()) {
            return null;
        }

        $oldRubric = $this->resolver->oldRubricRecord($rubricAlias);

        if ($oldRubric !== null) {
            $rubric = $this->resolver->rubricById($oldRubric->rubric_id);

            if ($rubric !== null) {
                $currentAlias = trim($rubric->alias ?? '');

                $doc = $this->resolver->docInRubric($rubric->id, $docAlias);

                if ($doc !== null && $this->isPublished($doc)) {
                    $newUrl = $currentAlias !== '' ? "/{$currentAlias}/{$docAlias}" : "/{$docAlias}";

                    return response('', 301)->header('Location', $newUrl . $suffix);
                }
            }
        }

        $rubric = $this->resolver->rubricByAlias($rubricAlias);

        if ($rubric !== null) {
            $oldDoc = $this->resolver->oldDocRecord($docAlias, $rubric->id);

            if ($oldDoc !== null) {
                $doc = $this->resolver->docById($oldDoc->document_id);

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
            $rubric = $this->resolver->rubricById($oldRubric->rubric_id);

            if ($rubric !== null) {
                $oldDoc = $this->resolver->oldDocRecord($docAlias, $rubric->id);

                if ($oldDoc !== null) {
                    $doc = $this->resolver->docById($oldDoc->document_id);

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

        $oldRubric = $this->resolver->oldRubricRecord($alias);

        if ($oldRubric !== null) {
            $rubric = $this->resolver->rubricById($oldRubric->rubric_id);

            if ($rubric !== null && trim($rubric->alias ?? '') !== '') {
                return response('', 301)->header('Location', '/' . $rubric->alias . $suffix);
            }
        }

        $oldDoc = $this->resolver->oldDocRecord($alias, null);

        if ($oldDoc !== null) {
            $doc = $this->resolver->docById($oldDoc->document_id);

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

    private function render404(Request $request): Response
    {
        Logger::error404($request);

        $doc404 = $this->resolver->custom404Doc();

        if ($doc404 !== null && $this->isPublished($doc404)) {
            $rendered = $this->layoutRenderer->render($doc404, $request);

            return response($rendered->getContent(), 404)
                ->header('Content-Type', 'text/html; charset=utf-8');
        }

        $html = View::make('errors.404', [
            'siteName' => $this->siteName(),
        ])->render();

        return response($html, 404)->header('Content-Type', 'text/html; charset=utf-8');
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
