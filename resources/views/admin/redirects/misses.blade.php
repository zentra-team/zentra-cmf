@extends('admin.layout')

@section('title', 'Битые ссылки')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/redirects.css') }}">
@endpush

@section('content')
<div class="ztr-page-title d-flex align-items-center">
    <a href="{{ route('admin.redirects.index') }}" class="btn btn-sm btn-secondary me-2">
        <i class="bi bi-arrow-left"></i>
    </a>
    <i class="bi bi-exclamation-triangle me-2"></i>Битые ссылки (404)
    <div class="ms-auto d-flex gap-2">
        @if($enabled && ($canDelete ?? false) && $misses && $misses->total() > 0)
        <button type="button" class="btn btn-sm btn-outline-danger" id="btnClearAllMisses"
            data-url="{{ route('admin.redirects.misses.clear') }}">
            <i class="bi bi-trash me-1"></i>Очистить весь журнал
        </button>
        @endif
    </div>
</div>

@if(! $enabled)
<div class="alert alert-info ztr-redirects-info-banner">
    <i class="bi bi-info-circle me-2"></i>
    Журнал битых ссылок выключен. Чтобы он начал собираться - включите его в
    <a href="{{ route('admin.settings') }}#tabSeo" class="alert-link">Настройках → SEO → Редиректы</a>.
    После включения система будет фиксировать каждый 404 с группировкой по URL и счётчиком попаданий.
</div>
@else

<div class="alert alert-light ztr-redirects-info-banner mb-3">
    <i class="bi bi-info-circle me-1"></i>
    В данном разделе отображается список всех URL, на которые приходили посетители, но страница не нашлась.
    Полезно для выявления старых ссылок из почтовых рассылок, чужих сайтов, опечаток.
    По любой записи можно одним кликом создать редирект.
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <form method="GET" class="row g-2">
            <div class="col-md-9">
                <input type="text" name="q" class="form-control form-control-sm"
                    value="{{ request('q') }}" placeholder="Поиск по URL">
            </div>
            <div class="col-md-3 d-flex gap-1">
                <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Найти</button>
                @if(request('q'))
                <a href="{{ route('admin.redirects.misses') }}" class="btn btn-sm btn-secondary">
                    <i class="bi bi-x"></i>
                </a>
                @endif
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">

    @if($misses->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-emoji-smile ztr-redirects-empty-icon"></i>
            <div class="mt-2">
                @if(request('q'))
                    Под поиск ничего не подошло.
                @else
                    Битых ссылок пока нет - поисковики и пользователи находят всё что им нужно.
                @endif
            </div>
        </div>
    @else
        <table class="table table-hover mb-0 ztr-redirects-table align-middle">
            <thead>
                <tr>
                    <th>URL</th>
                    <th class="ztr-redirects-col-hits">
                        <a href="?{{ http_build_query(array_merge(request()->query(), ['sort' => 'hits', 'dir' => request('sort') === 'hits' && request('dir') === 'desc' ? 'asc' : 'desc'])) }}" class="ztr-sort-link">
                            Хиты
                        </a>
                    </th>
                    <th class="ztr-redirects-col-last">
                        <a href="?{{ http_build_query(array_merge(request()->query(), ['sort' => 'first', 'dir' => request('sort') === 'first' && request('dir') === 'desc' ? 'asc' : 'desc'])) }}" class="ztr-sort-link">
                            Первый
                        </a>
                    </th>
                    <th class="ztr-redirects-col-last">
                        <a href="?{{ http_build_query(array_merge(request()->query(), ['sort' => 'last', 'dir' => request('sort') === 'last' && request('dir') === 'desc' ? 'asc' : 'desc'])) }}" class="ztr-sort-link">
                            Последний
                        </a>
                    </th>
                    <th>Откуда (Referer)</th>
                    <th class="ztr-redirects-col-actions"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($misses as $m)
                <tr data-miss-id="{{ $m->id }}">
                    <td><code>{{ $m->url }}</code></td>
                    <td class="text-end">{{ number_format($m->hits, 0, '.', ' ') }}</td>
                    <td class="text-muted small">{{ $m->first_seen_at->format('d.m.Y H:i') }}</td>
                    <td class="text-muted small">{{ $m->last_seen_at->format('d.m.Y H:i') }}</td>
                    <td class="text-muted small">
                        @if($m->last_referer)
                            <span class="ztr-redirects-referer" title="{{ $m->last_referer }}">{{ $m->last_referer }}</span>
                        @else
                            —
                        @endif
                    </td>
                    <td class="text-end ztr-nowrap">
                        @if($canCreate ?? false)
                        <a href="{{ route('admin.redirects.create', ['from_url' => $m->url]) }}"
                           class="btn btn-sm btn-outline-success"
                           data-bs-toggle="tooltip" data-bs-delay='{"show":600,"hide":100}'
                           title="Создать редирект из этой ссылки">
                            <i class="bi bi-plus-circle"></i>
                        </a>
                        @endif
                        @if($canDelete ?? false)
                        <button type="button" class="btn btn-sm btn-outline-danger btn-miss-delete"
                            data-bs-toggle="tooltip" data-bs-delay='{"show":600,"hide":100}'
                            title="Удалить запись из журнала"
                            data-url="{{ route('admin.redirects.misses.destroy', $m) }}">
                            <i class="bi bi-trash"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-3 py-2 ztr-redirects-pagination">
            {{ $misses->links() }}
        </div>
    @endif

    </div>
</div>

@if($canDelete ?? false)
<div class="modal fade" id="clearMissesModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-trash me-2"></i>Очистить журнал</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Удалить все записи о битых ссылках?</p>
                <p class="mb-0 text-warning small">
                    <i class="bi bi-exclamation-triangle me-1"></i>Действие необратимо.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnClearAllMissesConfirm">
                    <i class="bi bi-trash me-1"></i>Очистить
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<script>
window.ZentraConfig = {
    csrf: @json(csrf_token()),
};
</script>

@endif

@endsection

@push('scripts')
<script src="{{ route('admin.asset', 'js/redirects-misses.js') }}"></script>
@endpush
