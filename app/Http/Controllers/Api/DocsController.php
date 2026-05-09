<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Rubric;
use App\Models\Setting;
use App\Services\ApiJsonGenerator;
use Illuminate\Http\Request;

class DocsController extends Controller
{
    public function __construct(
        private readonly ApiJsonGenerator $generator,
    ) {
    }

    public function show(Request $request)
    {
        $apiEnabled = Setting::getValue('api_enabled', '0') === '1';
        $apiDomain = trim((string) Setting::getValue('api_domain', ''));
        $apiPrefix = '/' . trim((string) Setting::getValue('api_url_prefix', '/api/v1'), '/');

        $scheme = $request->getScheme();
        $host = $apiDomain !== '' ? $apiDomain : $request->getHost();
        $port = $request->getPort();
        $portStr = (in_array($port, [80, 443], true) || $port === null) ? '' : ':' . $port;
        $baseUrl = $scheme . '://' . $host . $portStr . $apiPrefix;

        $rubrics = Rubric::where('api_enabled', true)
            ->orderBy('alias')
            ->get(['id', 'alias', 'title', 'description'])
            ->map(function (Rubric $r) {
                $r->setRelation(
                    'apiFields',
                    \App\Models\RubricField::where('rubric_id', $r->id)
                        ->where('in_api', true)
                        ->orderBy('position')
                        ->get(['alias', 'title', 'type']),
                );

                return $r;
            });

        $sampleRubric = $rubrics->first();
        $sampleDoc = null;

        if ($sampleRubric !== null) {
            $sampleDoc = Document::where('rubric_id', $sampleRubric->id)
                ->where('status', Document::STATUS_ACTIVE)
                ->whereNotNull('alias')->where('alias', '!=', '')
                ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()))
                ->with('fields')
                ->first();
        }

        $publicHost = $request->getSchemeAndHttpHost();
        $samples = $this->buildSamples($baseUrl, $publicHost, $sampleRubric, $sampleDoc);

        return view('api.docs', [
            'apiEnabled'   => $apiEnabled,
            'baseUrl'      => $baseUrl,
            'rubrics'      => $rubrics,
            'sampleRubric' => $sampleRubric,
            'sampleDoc'    => $sampleDoc,
            'samples'      => $samples,
            'rateDefault'  => (int) Setting::getValue('api_default_rate_limit', '60'),
            'cacheTtl'     => (int) Setting::getValue('api_cache_ttl', '300'),
            'siteName'     => (string) Setting::getValue('site_name', 'Zentra'),
        ]);
    }

    private function buildSamples(string $baseUrl, string $publicHost, ?Rubric $sampleRubric, ?Document $sampleDoc): array
    {
        $flags = JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

        try {
            $rubricsPayload = $this->generator->listRubrics(null);
        } catch (\Throwable) {
            $rubricsPayload = ['data' => []];
        }

        if (empty($rubricsPayload['data'])) {
            $rubricsPayload = ['data' => [[
                'id' => 1, 'alias' => 'blog', 'title' => 'Блог', 'description' => 'Статьи и заметки',
            ]]];
        }

        if ($sampleRubric !== null) {
            try {
                $rubricPayload = $this->generator->showRubric($sampleRubric);
            } catch (\Throwable) {
                $rubricPayload = $this->fallbackRubricPayload();
            }
        } else {
            $rubricPayload = $this->fallbackRubricPayload();
        }

        if ($sampleRubric !== null) {
            try {
                $docsPayload = $this->generator->listDocuments(
                    $sampleRubric,
                    ['page' => 1, 'per_page' => 2, 'sort' => '-published_at'],
                    $publicHost,
                );
            } catch (\Throwable) {
                $docsPayload = $this->fallbackDocumentsPayload($baseUrl, $publicHost);
            }
        } else {
            $docsPayload = $this->fallbackDocumentsPayload($baseUrl, $publicHost);
        }

        if ($sampleRubric !== null && $sampleDoc !== null) {
            try {
                $docPayload = $this->generator->showDocument($sampleRubric, $sampleDoc, $publicHost);
            } catch (\Throwable) {
                $docPayload = $this->fallbackDocumentPayload($publicHost);
            }
        } else {
            $docPayload = $this->fallbackDocumentPayload($publicHost);
        }

        return [
            'rubrics'   => (string) json_encode($rubricsPayload, $flags),
            'rubric'    => (string) json_encode($rubricPayload, $flags),
            'documents' => (string) json_encode($docsPayload, $flags),
            'document'  => (string) json_encode($docPayload, $flags),
        ];
    }

    private function fallbackRubricPayload(): array
    {
        return ['data' => [
            'id'          => 1,
            'alias'       => 'blog',
            'title'       => 'Блог',
            'description' => 'Статьи и заметки',
            'fields'      => [
                ['alias' => 'cover', 'title' => 'Обложка', 'type' => 'image'],
                ['alias' => 'content', 'title' => 'Текст', 'type' => 'markdown'],
                ['alias' => 'tags', 'title' => 'Теги', 'type' => 'tags'],
            ],
        ]];
    }

    private function fallbackDocumentsPayload(string $baseUrl, string $publicHost): array
    {
        $url = $baseUrl . '/rubrics/blog/documents';

        return [
            'data' => [
                [
                    'id'           => 42,
                    'alias'        => 'hello-world',
                    'title'        => 'Привет, мир',
                    'url'          => $publicHost . '/blog/hello-world',
                    'rubric_alias' => 'blog',
                    'published_at' => '2026-05-01T10:00:00+00:00',
                    'created_at'   => '2026-05-01T09:30:00+00:00',
                    'updated_at'   => '2026-05-01T10:15:00+00:00',
                    'fields'       => [
                        'cover' => ['url' => $publicHost . '/img/cover.jpg', 'alt' => 'Обложка'],
                        'tags'  => ['intro', 'news'],
                    ],
                ],
                [
                    'id'           => 41,
                    'alias'        => 'second-post',
                    'title'        => 'Вторая публикация',
                    'url'          => $publicHost . '/blog/second-post',
                    'rubric_alias' => 'blog',
                    'published_at' => '2026-04-28T14:20:00+00:00',
                    'created_at'   => '2026-04-28T13:50:00+00:00',
                    'updated_at'   => '2026-04-28T14:25:00+00:00',
                    'fields'       => ['tags' => ['tutorial']],
                ],
            ],
            'meta'  => ['page' => 1, 'per_page' => 2, 'total' => 24, 'last_page' => 12],
            'links' => [
                'self'  => $url . '?page=1&per_page=2',
                'next'  => $url . '?page=2&per_page=2',
                'prev'  => null,
                'first' => $url . '?page=1&per_page=2',
                'last'  => $url . '?page=12&per_page=2',
            ],
        ];
    }

    private function fallbackDocumentPayload(string $publicHost): array
    {
        return ['data' => [
            'id'           => 42,
            'alias'        => 'hello-world',
            'title'        => 'Привет, мир',
            'url'          => $publicHost . '/blog/hello-world',
            'rubric_alias' => 'blog',
            'published_at' => '2026-05-01T10:00:00+00:00',
            'created_at'   => '2026-05-01T09:30:00+00:00',
            'updated_at'   => '2026-05-01T10:15:00+00:00',
            'meta'         => [
                'title'       => 'Привет, мир - пример SEO-заголовка',
                'description' => 'Краткое описание для поисковых систем.',
                'keywords'    => 'пример, статья, блог',
            ],
            'fields' => [
                'cover'   => ['url' => $publicHost . '/img/cover.jpg', 'alt' => 'Обложка статьи'],
                'content' => "# Заголовок\n\nТекст статьи в формате Markdown.",
                'tags'    => ['intro', 'news'],
            ],
        ]];
    }
}
