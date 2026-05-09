<?php

namespace App\Services;

use App\Models\Document;
use App\Models\Setting;
use App\Support\DocumentUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\View;

class LayoutRenderer
{
    private ?array $cachedSettings = null;

    public function __construct(
        private readonly PageRenderer $pageRenderer,
        private readonly NavigationRenderer $navRenderer,
        private readonly TagProcessor $tagProcessor,
        private readonly RequestRenderer $requestRenderer,
        private readonly BlockRenderer $blockRenderer,
    ) {
    }

    public function render(Document $document, Request $request): Response
    {
        $document->loadMissing(['rubric.layout', 'rubric.fields', 'fields']);

        $pageContent = $this->pageRenderer->render($document);
        $layout = $document->rubric?->layout;
        $url = $request->url();

        if ($layout !== null && trim($layout->content) !== '') {
            $html = $this->processLayoutHtml($layout->content, $document, $pageContent, $request);

            return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
        }

        $siteName = $this->siteName();
        $host = $request->getSchemeAndHttpHost();
        $html = View::make('public.layout', [
            'document'       => $document,
            'content'        => $pageContent,
            'siteName'       => $siteName,
            'pageTitle'      => $document->meta_title ?: $document->title,
            'headCode'       => $this->buildHeadCode(),
            'bodyCode'       => trim($this->setting('body_code')),
            'breadcrumbHtml' => $this->renderBreadcrumb($document, $host),
        ])->render();

        $ogTags = $this->buildOgTags($document, $url);
        $html = preg_replace('/<\/head>/i', $ogTags . "\n</head>", $html, 1) ?? $html;

        return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
    }

    public function renderWelcome(Request $request): Response
    {
        $html = View::make('public.welcome', [
            'siteName' => $this->siteName(),
        ])->render();

        return response($html, 200)->header('Content-Type', 'text/html; charset=utf-8');
    }

    private function processLayoutHtml(
        string $layoutHtml,
        Document $document,
        string $pageContent,
        Request $request,
    ): string {
        $hasMeta = str_contains($layoutHtml, '[meta]');
        $siteName = $this->siteName();
        $url = $request->url();
        $host = $request->getSchemeAndHttpHost();

        $html = str_replace('[maincontent]', $pageContent, $layoutHtml);

        $html = $this->blockRenderer->expand($html);

        $codeBlocks = [];
        $html = preg_replace_callback(
            '/<(pre|code)([\s>])(.*?)<\/\1>/si',
            function (array $m) use (&$codeBlocks): string {
                $key = '<!--CODE_BLOCK_' . count($codeBlocks) . '-->';
                $codeBlocks[$key] = $m[0];

                return $key;
            },
            $html,
        ) ?? $html;

        $replacements = [
            '[title]'      => '<title>' . e($document->meta_title ?: $document->title) . '</title>',
            '[meta]'       => $this->buildMetaTags($document, $url),
            '[robots]'     => '<meta name="robots" content="' . e($document->meta_robots ?? 'index, follow') . '">',
            '[sitename]'   => e($siteName),
            '[domain]'     => e($request->getHost()),
            '[home:url]'   => $host . '/',
            '[canonical]'  => '<link rel="canonical" href="' . e($url) . '">',
            '[breadcrumb]' => $this->renderBreadcrumb($document, $host),
        ];

        $html = str_replace(array_keys($replacements), array_values($replacements), $html);

        $html = preg_replace_callback(
            '/\[assets:css\/([^\]]+)\]/',
            fn (array $m) => '<link rel="stylesheet" href="/assets/css/' . e(trim($m[1])) . '">',
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[assets:js\/([^\]]+)\]/',
            fn (array $m) => '<script src="/assets/js/' . e(trim($m[1])) . '"></script>',
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[file:([^\]]+)\]/',
            fn (array $m) => '/' . e(ltrim(trim($m[1]), '/')),
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[date:([^\]]+)\]/',
            fn (array $m) => \Carbon\Carbon::now()->locale('ru')->translatedFormat(trim($m[1])),
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[nav:([a-zA-Z0-9_\-]+)\]/',
            fn (array $m) => $this->navRenderer->renderByAlias($m[1], $request),
            $html,
        ) ?? $html;

        $html = preg_replace_callback(
            '/\[request:([a-zA-Z0-9_\-]+)\]/',
            fn (array $m) => $this->requestRenderer->renderByAlias($m[1], $request, $document->id),
            $html,
        ) ?? $html;

        $headCodeInjected = false;

        if (str_contains($html, '[head:code]')) {
            $headCodeInjected = true;
            $html = str_replace('[head:code]', trim($this->setting('head_code')), $html);
        }

        $bodyCodeInjected = false;

        if (str_contains($html, '[body:code]')) {
            $bodyCodeInjected = true;
            $html = str_replace('[body:code]', trim($this->setting('body_code')), $html);
        }

        $this->tagProcessor->setContext([
            'document_id' => $document->id,
            'rubric_id'   => $document->rubric_id,
        ]);
        $html = $this->tagProcessor->process($html);

        $html = $this->injectMetaCodes($html, $headCodeInjected, $bodyCodeInjected);

        if (!$hasMeta) {
            $ogTags = $this->buildOgTags($document, $url);
            $html = preg_replace('/<\/head>/i', $ogTags . "\n</head>", $html, 1) ?? $html;
        }

        if ($codeBlocks) {
            $html = str_replace(array_keys($codeBlocks), array_values($codeBlocks), $html);
        }

        return $html;
    }

    private function buildMetaTags(Document $document, string $url): string
    {
        $description = $document->meta_description ?? '';
        $tags = '<meta name="description" content="' . e($description) . '">';
        $tags .= "\n" . $this->buildOgTags($document, $url);

        return $tags;
    }

    private function buildOgTags(Document $document, string $url): string
    {
        $ogTitle = $document->og_title ?: ($document->meta_title ?: $document->title);
        $ogDescription = $document->og_description ?: ($document->meta_description ?? '');
        $ogImage = $document->og_image ?: $this->setting('og_default_image');

        $tags = '<meta property="og:type" content="article">';
        $tags .= "\n" . '<meta property="og:url" content="' . e($url) . '">';
        $tags .= "\n" . '<meta property="og:title" content="' . e($ogTitle) . '">';
        $tags .= "\n" . '<meta property="og:description" content="' . e($ogDescription) . '">';

        if ($ogImage !== '') {
            $tags .= "\n" . '<meta property="og:image" content="' . e($ogImage) . '">';
        }

        return $tags;
    }

    private function buildHeadCode(bool $includeCustom = true): string
    {
        $out = '';

        $gaId = trim($this->setting('analytics_google'));

        if ($gaId !== '') {
            $gaIdE = e($gaId);
            $out .= "\n<!-- Google Analytics -->"
                . "\n<script async src=\"https://www.googletagmanager.com/gtag/js?id={$gaIdE}\"></script>"
                . "\n<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}"
                . "gtag('js',new Date());gtag('config','{$gaIdE}');</script>";
        }

        $ymId = trim($this->setting('analytics_yandex'));

        if ($ymId !== '' && ctype_digit($ymId)) {
            $ymIdInt = (int) $ymId;
            $out .= "\n<!-- Yandex.Metrika -->"
                . "\n<script>(function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};"
                . 'm[i].l=1*new Date();'
                . 'for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r){return;}}'
                . 'k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})'
                . "(window,document,'script','https://mc.yandex.ru/metrika/tag.js','ym');"
                . "ym({$ymIdInt},'init',{clickmap:true,trackLinks:true,accurateTrackBounce:true});</script>"
                . "\n<noscript><div><img src=\"https://mc.yandex.ru/watch/{$ymIdInt}\" style=\"position:absolute;left:-9999px\" alt=\"\"></div></noscript>";
        }

        if ($includeCustom) {
            $customHead = trim($this->setting('head_code'));

            if ($customHead !== '') {
                $out .= "\n" . $customHead;
            }
        }

        return $out;
    }

    private function injectMetaCodes(string $html, bool $headCodeInjected = false, bool $bodyCodeInjected = false): string
    {
        $headCode = $this->buildHeadCode(!$headCodeInjected);
        $bodyCode = trim($this->setting('body_code'));

        if ($headCode !== '') {
            $html = preg_replace('/<\/head>/i', $headCode . "\n</head>", $html, 1) ?? $html;
        }

        if (!$bodyCodeInjected && $bodyCode !== '') {
            $html = preg_replace('/<\/body>/i', $bodyCode . "\n</body>", $html, 1) ?? $html;
        }

        return $html;
    }

    private function renderBreadcrumb(Document $document, string $host): string
    {
        $showHome = $this->setting('breadcrumbs_show_home', '1') === '1';
        $separator = $this->setting('breadcrumbs_separator', '/');
        $lastLink = $this->setting('breadcrumbs_last_link', '0') === '1';

        $crumbs = [];
        $rubric = $document->rubric;
        $rubricAlias = trim($rubric?->alias ?? '');
        $docAlias = $document->alias;
        $isIndexPage = $docAlias === null || $docAlias === '';
        $urlSuffix = $this->setting('url_suffix', '');

        if ($showHome) {
            $crumbs[] = '<li class="breadcrumb-item"><a href="' . $host . '/">Главная</a></li>';
        }

        if ($rubricAlias !== '' && !$isIndexPage) {
            $crumbs[] = '<li class="breadcrumb-item">'
                . '<a href="' . $host . '/' . e($rubricAlias) . '">'
                . e($rubric->title)
                . '</a></li>';
        }

        $ancestors = [];
        $seen = [$document->id => true];
        $ancestor = $document->parent_doc_id ? $document->parentDoc : null;
        while ($ancestor !== null && !isset($seen[$ancestor->id])) {
            $seen[$ancestor->id] = true;
            $ancestors[] = $ancestor;
            $ancestor = $ancestor->parent_doc_id ? $ancestor->parentDoc : null;
        }
        foreach (array_reverse($ancestors) as $anc) {
            $ancLabel = trim($anc->breadcrumb_title ?: $anc->title);
            $ancUrl = DocumentUrl::build($rubricAlias, $anc->alias, $urlSuffix);
            $crumbs[] = '<li class="breadcrumb-item">'
                . '<a href="' . $host . $ancUrl . '">' . e($ancLabel) . '</a>'
                . '</li>';
        }

        $label = trim($document->breadcrumb_title ?: $document->title);

        if ($lastLink) {
            $docUrl = DocumentUrl::build($rubricAlias, $docAlias, $urlSuffix);
            $crumbs[] = '<li class="breadcrumb-item active" aria-current="page">'
                . '<a href="' . $host . $docUrl . '">' . e($label) . '</a>'
                . '</li>';
        } else {
            $crumbs[] = '<li class="breadcrumb-item active" aria-current="page">' . e($label) . '</li>';
        }

        $sepAttr = '';

        if ($separator !== '/' && $separator !== '') {
            $sepEscaped = str_replace(['\\', "'"], ['\\\\', "\\'"], $separator);
            $sepAttr = ' style="--bs-breadcrumb-divider: \'' . $sepEscaped . '\'"';
        }

        return '<nav aria-label="breadcrumb"><ol class="breadcrumb"' . $sepAttr . '>'
            . implode('', $crumbs)
            . '</ol></nav>';
    }

    private function settings(): array
    {
        if ($this->cachedSettings === null) {
            try {
                $this->cachedSettings = Setting::allAsArray();
            } catch (\Throwable) {
                $this->cachedSettings = [];
            }
        }

        return $this->cachedSettings;
    }

    private function setting(string $key, string $default = ''): string
    {
        return (string) ($this->settings()[$key] ?? $default);
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
