@extends('admin.layout')

@section('title', 'Редиректы')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/redirects.css') }}">
@endpush

@section('content')
<div class="ztr-page-title d-flex align-items-center">
    <i class="bi bi-signpost-split me-2"></i>Редиректы
    <div class="ms-auto d-flex gap-2">
        @if($canMisses)
        <a href="{{ route('admin.redirects.misses') }}" class="btn btn-sm btn-outline-warning">
            <i class="bi bi-exclamation-triangle me-1"></i>Битые ссылки
            @if($logMissesEnabled && $stats['misses'] > 0)
                <span class="badge bg-warning text-dark ms-1">{{ $stats['misses'] }}</span>
            @endif
        </a>
        @endif
        @if($canCreate)
        <a href="{{ route('admin.redirects.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Создать редирект
        </a>
        @endif
    </div>
</div>

@if(! $logMissesEnabled && $canMisses)
<div class="alert alert-info py-2 ztr-redirects-info-banner">
    <i class="bi bi-info-circle me-1"></i>
    Журнал битых ссылок (404) выключен. Включить можно в
    <a href="{{ route('admin.settings') }}#tabSeo" class="alert-link">Настройках → SEO → Редиректы</a>.
</div>
@endif

<div class="row g-3 mb-3 ztr-redirects-stats">
    <div class="col-md">
        <div class="card h-100">
            <div class="card-body py-3 text-center">
                <div class="text-secondary small mb-1">Всего</div>
                <div class="fw-semibold fs-5">{{ number_format($stats['total'], 0, '.', ' ') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md">
        <div class="card h-100">
            <div class="card-body py-3 text-center">
                <div class="text-secondary small mb-1">Активных</div>
                <div class="fw-semibold fs-5 text-success">{{ number_format($stats['active'], 0, '.', ' ') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md">
        <div class="card h-100">
            <div class="card-body py-3 text-center">
                <div class="text-secondary small mb-1">Wildcards</div>
                <div class="fw-semibold fs-5 text-info">{{ number_format($stats['wildcards'], 0, '.', ' ') }}</div>
            </div>
        </div>
    </div>
    <div class="col-md">
        <div class="card h-100">
            <div class="card-body py-3 text-center">
                <div class="text-secondary small mb-1">Истекли</div>
                <div class="fw-semibold fs-5 text-warning">{{ number_format($stats['expired'], 0, '.', ' ') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <div class="ztr-redirects-search-toggle" data-target="#filtersCollapse" role="button">
            <i class="bi bi-funnel me-2"></i>
            <span>Фильтры и поиск</span>
            @if(request()->hasAny(['q', 'status', 'kind', 'type', 'sort']))
                <span class="badge bg-info ms-2">Активны</span>
            @endif
            <i class="bi bi-chevron-down ms-auto"></i>
        </div>
        <div class="collapse {{ request()->hasAny(['q', 'status', 'kind', 'type', 'sort']) ? 'show' : '' }}" id="filtersCollapse">
            <form method="GET" class="row g-2 mt-2">
                <div class="col-md-4">
                    <label class="form-label small mb-1">Поиск</label>
                    <input type="text" name="q" class="form-control form-control-sm"
                        value="{{ request('q') }}" placeholder="по from_url, to_url или заметке">
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Статус</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Все</option>
                        <option value="active"   @selected(request('status') === 'active')>Активные</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Неактивные</option>
                        <option value="expired"  @selected(request('status') === 'expired')>Истёкшие</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Вид</label>
                    <select name="kind" class="form-select form-select-sm">
                        <option value="">Любой</option>
                        <option value="direct"   @selected(request('kind') === 'direct')>Точные</option>
                        <option value="wildcard" @selected(request('kind') === 'wildcard')>Wildcards</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small mb-1">Тип</label>
                    <select name="type" class="form-select form-select-sm">
                        <option value="">Все</option>
                        @foreach([301, 302] as $t)
                        <option value="{{ $t }}" @selected((int)request('type') === $t)>{{ $t }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Применить</button>
                    @if(request()->hasAny(['q', 'status', 'kind', 'type', 'sort']))
                    <a href="{{ route('admin.redirects.index') }}" class="btn btn-sm btn-secondary" title="Сбросить">
                        <i class="bi bi-x"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <div class="ztr-redirects-search-toggle" data-target="#inspectorCollapse" role="button">
            <i class="bi bi-search me-2"></i>
            <span>Инспектор: проверить URL</span>
            <i class="bi bi-chevron-down ms-auto"></i>
        </div>
        <div class="collapse" id="inspectorCollapse">
            <div class="row g-2 mt-2 align-items-end">
                <div class="col-md-9">
                    <label class="form-label small mb-1">URL для проверки</label>
                    <input type="text" id="inspectorUrl" class="form-control form-control-sm"
                        placeholder="/old-section/some-page">
                </div>
                <div class="col-md-3">
                    <button type="button" class="btn btn-sm btn-primary w-100" id="btnInspect"
                        data-url="{{ route('admin.redirects.inspect') }}">
                        <i class="bi bi-search me-1"></i>Проверить
                    </button>
                </div>
                <div class="col-12">
                    <div id="inspectorResult" class="ztr-redirects-inspect-result d-none"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">

    @if($canEdit || $canDelete)
    <div class="ztr-redirects-bulk-bar d-none" id="bulkBar">
        <div class="d-flex align-items-center gap-2 px-3 py-2">
            <span class="me-2"><span id="bulkCount">0</span> выбрано</span>
            @if($canEdit)
            <button type="button" class="btn btn-sm btn-outline-success" data-bulk-action="activate">
                <i class="bi bi-check2-circle me-1"></i>Активировать
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bulk-action="deactivate">
                <i class="bi bi-pause-circle me-1"></i>Деактивировать
            </button>
            @endif
            @if($canDelete)
            <button type="button" class="btn btn-sm btn-outline-danger" data-bulk-action="delete">
                <i class="bi bi-trash me-1"></i>Удалить
            </button>
            @endif
            <button type="button" class="btn btn-sm btn-link text-muted ms-auto" id="btnBulkClear">
                Снять выделение
            </button>
        </div>
    </div>
    @endif

    @if($redirects->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-signpost-split ztr-redirects-empty-icon"></i>
            <div class="mt-2">
                @if(request()->hasAny(['q', 'status', 'kind', 'type']))
                    Под фильтр ничего не подошло.
                @else
                    Редиректов пока нет.
                    @if($canCreate)
                        <a href="{{ route('admin.redirects.create') }}">Создать первый</a>.
                    @endif
                @endif
            </div>
        </div>
    @else
        <table class="table table-hover mb-0 ztr-redirects-table align-middle">
            <thead>
                <tr>
                    <th class="ztr-redirects-col-checkbox">
                        <input type="checkbox" id="checkAll" class="form-check-input">
                    </th>
                    <th>
                        <a href="?{{ http_build_query(array_merge(request()->query(), ['sort' => 'from', 'dir' => request('sort') === 'from' && request('dir') === 'asc' ? 'desc' : 'asc'])) }}" class="ztr-sort-link">
                            URL
                        </a>
                    </th>
                    <th class="ztr-redirects-col-type">Тип</th>
                    <th class="ztr-redirects-col-kind">Вид</th>
                    <th class="ztr-redirects-col-priority">
                        <a href="?{{ http_build_query(array_merge(request()->query(), ['sort' => 'priority', 'dir' => request('sort') === 'priority' && request('dir') === 'desc' ? 'asc' : 'desc'])) }}" class="ztr-sort-link">
                            Приоритет
                        </a>
                    </th>
                    <th class="ztr-redirects-col-hits">
                        <a href="?{{ http_build_query(array_merge(request()->query(), ['sort' => 'hits', 'dir' => request('sort') === 'hits' && request('dir') === 'desc' ? 'asc' : 'desc'])) }}" class="ztr-sort-link">
                            Хиты
                        </a>
                    </th>
                    <th class="ztr-redirects-col-last">
                        <a href="?{{ http_build_query(array_merge(request()->query(), ['sort' => 'last_hit', 'dir' => request('sort') === 'last_hit' && request('dir') === 'desc' ? 'asc' : 'desc'])) }}" class="ztr-sort-link">
                            Последнее
                        </a>
                    </th>
                    <th class="ztr-redirects-col-status">Статус</th>
                    <th class="ztr-redirects-col-actions"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($redirects as $r)
                <tr data-redirect-id="{{ $r->id }}" class="{{ ! $r->is_active ? 'ztr-redirect-inactive' : '' }}">
                    <td>
                        <input type="checkbox" class="form-check-input row-check" value="{{ $r->id }}">
                    </td>
                    <td>
                        <div class="ztr-redirect-from"><code>{{ $r->from_url }}</code></div>
                        <div class="ztr-redirect-to small text-muted">
                            <i class="bi bi-arrow-return-right me-1"></i><code>{{ $r->to_url }}</code>
                            @if(isset($potentialCycles[$r->id]))
                                <i class="bi bi-exclamation-triangle text-warning ms-1"
                                   data-bs-toggle="tooltip" title="Возможная цепочка/цикл: цель является источником другого активного редиректа"></i>
                            @endif
                        </div>
                        @if($r->note)
                            <div class="ztr-redirect-note small text-muted mt-1"><i class="bi bi-sticky me-1"></i>{{ $r->note }}</div>
                        @endif
                    </td>
                    <td>
                        <span class="badge bg-secondary">{{ $r->type }}</span>
                    </td>
                    <td>
                        @if($r->is_wildcard)
                            <span class="badge bg-info"><i class="bi bi-asterisk"></i> wildcard</span>
                        @else
                            <span class="text-muted small">точный</span>
                        @endif
                    </td>
                    <td class="text-center text-muted">{{ $r->priority }}</td>
                    <td class="text-end">{{ number_format($r->hits, 0, '.', ' ') }}</td>
                    <td class="text-muted small">
                        @if($r->last_hit_at)
                            {{ $r->last_hit_at->format('d.m.Y H:i') }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        @if(! $r->is_active)
                            <span class="badge bg-secondary">Выключен</span>
                        @elseif($r->expires_at && $r->expires_at->isPast())
                            <span class="badge bg-warning text-dark"
                                  data-bs-toggle="tooltip"
                                  title="Истёк {{ $r->expires_at->format('d.m.Y H:i') }}">Истёк</span>
                        @elseif($r->expires_at)
                            <span class="badge bg-success"
                                  data-bs-toggle="tooltip"
                                  title="До {{ $r->expires_at->format('d.m.Y H:i') }}">Активен</span>
                        @else
                            <span class="badge bg-success">Активен</span>
                        @endif
                    </td>
                    <td class="text-end ztr-nowrap">
                        <a href="{{ route('admin.redirects.edit', $r) }}" class="btn btn-sm btn-outline-success"
                           title="{{ $canEdit ? 'Редактировать' : 'Просмотреть' }}">
                            <i class="bi {{ $canEdit ? 'bi-pencil' : 'bi-eye' }}"></i>
                        </a>
                        @if($canDelete)
                        <button type="button" class="btn btn-sm btn-outline-danger btn-row-delete" title="Удалить"
                            data-id="{{ $r->id }}"
                            data-from="{{ $r->from_url }}"
                            data-to="{{ $r->to_url }}">
                            <i class="bi bi-trash"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-3 py-2 ztr-redirects-pagination">
            {{ $redirects->links() }}
        </div>
    @endif

    </div>
</div>

@if($canDelete)
<div class="modal fade" id="deleteRedirectModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-trash me-2"></i>Удаление редиректа</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Удалить редирект:</p>
                <p class="mb-1"><code id="delFrom"></code></p>
                <p class="mb-2"><i class="bi bi-arrow-return-right me-1"></i><code id="delTo"></code></p>
                <p class="mb-0 text-muted small">Это действие необратимо.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDelConfirm">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
@endif

<div class="modal fade" id="bulkConfirmModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-check2-square me-2"></i>Подтверждение</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="bulkConfirmText"></p>
                <p class="mb-0 text-warning small d-none" id="bulkConfirmWarn">
                    <i class="bi bi-exclamation-triangle me-1"></i>Действие необратимо.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnBulkConfirm">Применить</button>
            </div>
        </div>
    </div>
</div>

<script>
window.ZentraConfig = {
    csrf:        @json(csrf_token()),
    bulkUrl:     @json(route('admin.redirects.bulk')),
    destroyTpl:  @json(route('admin.redirects.destroy', ['redirect' => 0])),
};
</script>
@endsection

@push('scripts')
<script src="{{ route('admin.asset', 'js/redirects-index.js') }}"></script>
@endpush
