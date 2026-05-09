<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ApiToken;
use App\Models\Rubric;
use App\Services\ApiJsonGenerator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RubricController extends Controller
{
    public function __construct(
        private readonly ApiJsonGenerator $generator,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $token = $this->token($request);

        return response()->json($this->generator->listRubrics($token));
    }

    public function show(Request $request, string $alias): JsonResponse
    {
        $rubric = $this->resolveRubric($alias);

        if ($rubric === null) {
            return $this->notFound();
        }

        if ($denied = $this->denyIfNoRubricAccess($request, $rubric)) {
            return $denied;
        }

        return response()->json($this->generator->showRubric($rubric));
    }

    public function documents(Request $request, string $alias): JsonResponse
    {
        $rubric = $this->resolveRubric($alias);

        if ($rubric === null) {
            return $this->notFound();
        }

        if ($denied = $this->denyIfNoRubricAccess($request, $rubric)) {
            return $denied;
        }

        $params = [
            'page'     => (int) $request->query('page', 1),
            'per_page' => (int) $request->query('per_page', 0),
            'sort'     => (string) $request->query('sort', ''),
        ];

        return response()->json($this->generator->listDocuments($rubric, $params, $this->baseUrl($request)));
    }

    public function showDocument(Request $request, string $alias, string $docAlias): JsonResponse
    {
        $rubric = $this->resolveRubric($alias);

        if ($rubric === null) {
            return $this->notFound();
        }

        if ($denied = $this->denyIfNoRubricAccess($request, $rubric)) {
            return $denied;
        }

        $document = $this->generator->findApiDocument($rubric, $docAlias);

        if ($document === null) {
            return $this->notFound();
        }

        return response()->json($this->generator->showDocument($rubric, $document, $this->baseUrl($request)));
    }

    private function resolveRubric(string $alias): ?Rubric
    {
        return $this->generator->findApiRubric($alias);
    }

    private function token(Request $request): ?ApiToken
    {
        $t = $request->attributes->get(\App\Http\Middleware\AuthenticateApiToken::REQUEST_TOKEN_ATTR);

        return $t instanceof ApiToken ? $t : null;
    }

    private function denyIfNoRubricAccess(Request $request, Rubric $rubric): ?JsonResponse
    {
        $token = $this->token($request);

        if ($token !== null && !$token->canAccessRubric((int) $rubric->id)) {
            return response()->json(['error' => [
                'code'    => 'rubric_forbidden',
                'message' => 'Токен не имеет доступа к этой рубрике.',
            ]], 403);
        }

        return null;
    }

    private function baseUrl(Request $request): string
    {
        return rtrim($request->getSchemeAndHttpHost() ?: (string) config('app.url', ''), '/');
    }

    private function notFound(): JsonResponse
    {
        return response()->json(['error' => [
            'code'    => 'not_found',
            'message' => 'Ресурс не доступен',
        ]], 404);
    }
}
