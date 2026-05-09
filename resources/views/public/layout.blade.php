<!DOCTYPE html>
<html lang="ru" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $pageTitle ?? ($document->meta_title ?: $document->title) }}</title>
    @if(!empty($document->meta_description))
    <meta name="description" content="{{ $document->meta_description }}">
    @endif
    @if(!empty($document->meta_keywords))
    <meta name="keywords" content="{{ $document->meta_keywords }}">
    @endif
    <meta name="robots" content="{{ $document->meta_robots ?: 'index, follow' }}">
    <link rel="canonical" href="{{ request()->url() }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/assets/css/public-layout.css">
    @if(!empty($headCode)){!! $headCode !!}@endif
</head>
<body>

<nav class="ztr-navbar navbar sticky-top">
    <div class="container">
        <a href="/" class="navbar-brand">{{ $siteName }}</a>
        <div class="d-flex align-items-center gap-3">
            <a href="/admin/login" class="nav-link">
                <i class="bi bi-person me-1"></i>Войти
            </a>
        </div>
    </div>
</nav>

<main>
    <div class="container ztr-content">
        @if(!empty($breadcrumbHtml))
        {!! $breadcrumbHtml !!}
        @endif
        {!! $content !!}
    </div>
</main>

<footer class="ztr-footer">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span>{{ $siteName }}</span>
        <span>Powered by <a href="https://github.com/zentra-team/zentra-cmf" target="_blank" rel="noopener">Zentra CMF</a></span>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
@if(!empty($bodyCode))
{!! $bodyCode !!}
@endif
</body>
</html>
