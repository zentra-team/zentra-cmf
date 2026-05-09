@extends('admin.layout')

@section('title', 'API-токены')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/api-tokens.css') }}">
@endpush

@section('content')
<div class="ztr-page-title d-flex align-items-center">
    <i class="bi bi-braces me-2"></i>API-токены
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('api.public.docs') }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-secondary" title="Публичная документация API для клиентов">
            <i class="bi bi-book me-1"></i>Документация API
        </a>
        @if($canCreate)
        <a href="{{ route('admin.api-tokens.create') }}" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-circle me-1"></i>Создать токен
        </a>
        @endif
    </div>
</div>

@if(! $apiEnabled)
<div class="alert alert-warning py-2 ztr-api-info-banner">
    <i class="bi bi-exclamation-triangle me-1"></i>
    JSON API сейчас выключен глобально. Включить можно в
    <a href="{{ route('admin.settings') }}#tabSeo" class="alert-link">Настройках → SEO и коды → JSON API</a>.
</div>
@endif

<div class="row g-3 mb-3 ztr-api-stats">
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
                <div class="text-secondary small mb-1">Истекли</div>
                <div class="fw-semibold fs-5 text-warning">{{ number_format($stats['expired'], 0, '.', ' ') }}</div>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-3">
        <div class="ztr-api-search-toggle" data-target="#filtersCollapse" role="button">
            <i class="bi bi-funnel me-2"></i>
            <span>Фильтры и поиск</span>
            @if(request()->hasAny(['q', 'status', 'sort']))
                <span class="badge bg-info ms-2">Активны</span>
            @endif
            <i class="bi bi-chevron-down ms-auto"></i>
        </div>
        <div class="collapse {{ request()->hasAny(['q', 'status', 'sort']) ? 'show' : '' }}" id="filtersCollapse">
            <form method="GET" class="row g-2 mt-2">
                <div class="col-md-7">
                    <label class="form-label small mb-1">Поиск</label>
                    <input type="text" name="q" class="form-control form-control-sm"
                        value="{{ request('q') }}" placeholder="по названию, описанию или префиксу">
                </div>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Статус</label>
                    <select name="status" class="form-select form-select-sm">
                        <option value="">Все</option>
                        <option value="active"   @selected(request('status') === 'active')>Активные</option>
                        <option value="inactive" @selected(request('status') === 'inactive')>Неактивные</option>
                        <option value="expired"  @selected(request('status') === 'expired')>Истёкшие</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-1">
                    <button type="submit" class="btn btn-sm btn-primary flex-grow-1">Применить</button>
                    @if(request()->hasAny(['q', 'status', 'sort']))
                    <a href="{{ route('admin.api-tokens.index') }}" class="btn btn-sm btn-secondary" title="Сбросить">
                        <i class="bi bi-x"></i>
                    </a>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-body p-0">

    <div class="ztr-api-bulk-bar d-none" id="bulkBar">
        <div class="d-flex align-items-center gap-2 px-3 py-2">
            <span class="me-2"><span id="bulkCount">0</span> выбрано</span>
            <button type="button" class="btn btn-sm btn-outline-success" data-bulk-action="activate">
                <i class="bi bi-check2-circle me-1"></i>Активировать
            </button>
            <button type="button" class="btn btn-sm btn-outline-secondary" data-bulk-action="deactivate">
                <i class="bi bi-pause-circle me-1"></i>Деактивировать
            </button>
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

    @if($tokens->isEmpty())
        <div class="text-center py-5 text-muted">
            <i class="bi bi-braces ztr-api-empty-icon"></i>
            <div class="mt-2">
                @if(request()->hasAny(['q', 'status']))
                    Под заданные параметры фильтрации записей не найдено.
                @else
                    API-токенов пока нет.
                    @if($canCreate)
                        <a href="{{ route('admin.api-tokens.create') }}">Создать первый</a>.
                    @endif
                @endif
            </div>
        </div>
    @else
        <table class="table table-hover mb-0 ztr-api-table align-middle">
            <thead>
                <tr>
                    <th class="ztr-api-col-checkbox">
                        <input type="checkbox" id="checkAll" class="form-check-input">
                    </th>
                    <th>
                        <a href="?{{ http_build_query(array_merge(request()->query(), ['sort' => 'name', 'dir' => request('sort') === 'name' && request('dir') === 'asc' ? 'desc' : 'asc'])) }}" class="ztr-sort-link">
                            Название / префикс
                        </a>
                    </th>
                    <th class="ztr-api-col-rubrics">Рубрики</th>
                    <th class="ztr-api-col-rate">Лимит/мин</th>
                    <th class="ztr-api-col-hits">
                        <a href="?{{ http_build_query(array_merge(request()->query(), ['sort' => 'hits', 'dir' => request('sort') === 'hits' && request('dir') === 'desc' ? 'asc' : 'desc'])) }}" class="ztr-sort-link">
                            Запросов
                        </a>
                    </th>
                    <th class="ztr-api-col-last">
                        <a href="?{{ http_build_query(array_merge(request()->query(), ['sort' => 'last', 'dir' => request('sort') === 'last' && request('dir') === 'desc' ? 'asc' : 'desc'])) }}" class="ztr-sort-link">
                            Последнее
                        </a>
                    </th>
                    <th class="ztr-api-col-status">Статус</th>
                    <th class="ztr-api-col-actions"></th>
                </tr>
            </thead>
            <tbody>
                @foreach($tokens as $t)
                @php
                    $allowedIds = is_array($t->allowed_rubrics) ? $t->allowed_rubrics : [];
                    $allowedNames = empty($allowedIds)
                        ? null
                        : collect($rubricsList)->whereIn('id', array_map('intval', $allowedIds))->pluck('title')->all();
                @endphp
                <tr data-token-id="{{ $t->id }}" class="{{ ! $t->is_active ? 'ztr-api-token-inactive' : '' }}">
                    <td>
                        <input type="checkbox" class="form-check-input row-check" value="{{ $t->id }}">
                    </td>
                    <td>
                        <div class="ztr-api-token-name">{{ $t->name }}</div>
                        <div class="ztr-api-token-prefix small text-muted"><code>{{ $t->token_prefix }}</code></div>
                        @if($t->description)
                            <div class="ztr-api-token-desc small text-muted mt-1"><i class="bi bi-sticky me-1"></i>{{ \Illuminate\Support\Str::limit($t->description, 80) }}</div>
                        @endif
                    </td>
                    <td>
                        @if($allowedNames === null)
                            <span class="badge bg-info">Все рубрики</span>
                        @elseif(empty($allowedNames))
                            <span class="badge bg-warning text-dark">Нет</span>
                        @else
                            <span class="ztr-api-rubrics-list" data-bs-toggle="tooltip" title="{{ implode(', ', $allowedNames) }}">
                                {{ count($allowedNames) }} {{ \Illuminate\Support\Str::plural('рубрика', count($allowedNames)) }}
                            </span>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($t->rate_limit_per_minute > 0)
                            {{ $t->rate_limit_per_minute }}
                        @else
                            <span class="text-muted small">∞</span>
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($t->hits, 0, '.', ' ') }}</td>
                    <td class="text-muted small">
                        @if($t->last_used_at)
                            {{ $t->last_used_at->format('d.m.Y H:i') }}
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if(! $t->is_active)
                            <span class="badge bg-secondary">Выключен</span>
                        @elseif($t->expires_at && $t->expires_at->isPast())
                            <span class="badge bg-warning text-dark"
                                  data-bs-toggle="tooltip"
                                  title="Истёк {{ $t->expires_at->format('d.m.Y H:i') }}">Истёк</span>
                        @elseif($t->expires_at)
                            <span class="badge bg-success"
                                  data-bs-toggle="tooltip"
                                  title="До {{ $t->expires_at->format('d.m.Y H:i') }}">Активен</span>
                        @else
                            <span class="badge bg-success">Активен</span>
                        @endif
                    </td>
                    <td class="text-end ztr-nowrap">
                        <a href="{{ route('admin.api-tokens.edit', $t) }}" class="btn btn-sm btn-outline-success" title="Редактировать">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @if($canDelete)
                        <button type="button" class="btn btn-sm btn-outline-danger btn-row-delete" title="Удалить"
                            data-id="{{ $t->id }}"
                            data-name="{{ $t->name }}">
                            <i class="bi bi-trash"></i>
                        </button>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="px-3 py-2 ztr-api-pagination">
            {{ $tokens->links() }}
        </div>
    @endif

    </div>
</div>

@if($canDelete)
<div class="modal fade" id="deleteTokenModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-trash me-2"></i>Удаление токена</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2">Удалить токен <strong id="delTokenName"></strong>?</p>
                <p class="mb-0 text-warning small">
                    <i class="bi bi-exclamation-triangle me-1"></i>Клиенты с этим токеном перестанут получать данные через API.
                </p>
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
    bulkUrl:     @json(route('admin.api-tokens.bulk')),
    destroyTpl:  @json(route('admin.api-tokens.destroy', ['apiToken' => 0])),
};
</script>
@endsection

@push('scripts')
<script src="{{ route('admin.asset', 'js/api-tokens-index.js') }}"></script>
@endpush
