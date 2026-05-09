@extends('admin.layout')

@section('title', 'Дашборд')

@section('content')
<div class="ztr-page-title"><i class="bi bi-speedometer2 me-2"></i>Дашборд</div>

<div class="card mb-3" id="cardOnlineUsers">
    <div class="card-header d-flex align-items-center gap-2">
        <i class="bi bi-circle-fill text-success" style="font-size:.55rem"></i>
        <span>Пользователи онлайн</span>
        <span class="badge bg-secondary ms-1" id="onlineBadge">…</span>
    </div>
    <div class="card-body py-2 px-3" id="onlineList">
        <span class="text-muted" style="font-size:.85rem">Загрузка…</span>
    </div>
</div>

<div class="row g-3 mb-3">

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-file-earmark-text me-2 text-muted"></i>Документы по статусам
            </div>
            <div class="card-body" id="docStatuses">
                <div class="text-muted" style="font-size:.85rem">Загрузка…</div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-activity me-2 text-muted"></i>Публикации за 14 дней</span>
                <span class="text-muted" id="sparkTotalLabel" style="font-size:.78rem"></span>
            </div>
            <div class="card-body pb-2">
                <div class="ztr-sparkline-wrap" id="sparklineWrap">
                    <svg class="ztr-sparkline" id="sparklineSvg" viewBox="0 0 280 60" preserveAspectRatio="none">
                        <defs>
                            <linearGradient id="sparkGradient" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stop-color="#a78bfa" stop-opacity=".5"/>
                                <stop offset="100%" stop-color="#a78bfa" stop-opacity="0"/>
                            </linearGradient>
                        </defs>
                        <path class="ztr-sparkline-area" id="sparkArea" d=""/>
                        <polyline class="ztr-sparkline-line" id="sparkLine" points=""/>
                    </svg>
                    <div class="ztr-sparkline-labels" id="sparkLabels"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-3">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-braces me-2 text-muted"></i>API
            </div>
            <div class="card-body" id="apiStats">
                <div class="text-muted" style="font-size:.85rem">Загрузка…</div>
            </div>
            <div class="card-footer px-3 py-2">
                <a href="{{ route('admin.api-tokens.index') }}" class="btn btn-sm btn-secondary w-100">
                    <i class="bi bi-arrow-right me-1"></i>Управление токенами
                </a>
            </div>
        </div>
    </div>

</div>

<div class="row g-3 mb-3" id="urgentRow">
    <div class="col-lg-4">
        <div class="card h-100 ztr-urgent-card" id="urgentModeration">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="ztr-urgent-dot" id="urgentModerationDot"></span>
                <span class="fw-semibold">Документы на модерации</span>
                <span class="badge ms-auto" id="urgentModerationBadge">…</span>
            </div>
            <div class="card-body p-0" id="urgentModerationBody">
                <div class="ztr-widget-empty">Загрузка…</div>
            </div>
            <div class="card-footer px-3 py-2">
                <a href="{{ route('admin.documents.index') }}?status=2" class="btn btn-sm btn-secondary w-100">
                    <i class="bi bi-arrow-right me-1"></i>Открыть очередь
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100 ztr-urgent-card" id="urgentErrors">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="ztr-urgent-dot" id="urgentErrorsDot"></span>
                <span class="fw-semibold">Ошибки системы</span>
                <span class="badge ms-auto" id="urgentErrorsBadge">…</span>
            </div>
            <div class="card-body p-0" id="urgentErrorsBody">
                <div class="ztr-widget-empty">Загрузка…</div>
            </div>
            <div class="card-footer px-3 py-2">
                <a href="{{ route('admin.logs.index') }}" class="btn btn-sm btn-secondary w-100">
                    <i class="bi bi-arrow-right me-1"></i>Открыть журналы
                </a>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card h-100 ztr-urgent-card" id="urgentMisses">
            <div class="card-header d-flex align-items-center gap-2">
                <span class="ztr-urgent-dot" id="urgentMissesDot"></span>
                <span class="fw-semibold">Страницы не найдены</span>
                <span class="badge ms-auto" id="urgentMissesBadge">…</span>
            </div>
            <div class="card-body p-0" id="urgentMissesBody">
                <div class="ztr-widget-empty">Загрузка…</div>
            </div>
            <div class="card-footer px-3 py-2">
                <a href="{{ route('admin.redirects.misses') }}" class="btn btn-sm btn-secondary w-100">
                    <i class="bi bi-arrow-right me-1"></i>Все запросы 404
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card mb-3">
    <div class="card-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-files me-2 text-muted"></i>Последние документы</span>
        <a href="{{ route('admin.documents.index') }}" class="text-muted" style="font-size:.78rem">
            Все документы <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div class="card-body">
        <div class="row g-2" id="recentDocs">
            <div class="col-12 text-muted" style="font-size:.85rem">Загрузка…</div>
        </div>
    </div>
</div>

<div class="row g-3 mb-3">

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle me-2 text-muted"></i>Топ 404 ошибок</span>
                <a href="{{ route('admin.redirects.misses') }}" class="text-muted" style="font-size:.78rem">
                    Все ошибки <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div id="top404" class="card-body p-0">
                <div class="ztr-widget-empty">Загрузка…</div>
            </div>
            <div class="card-footer px-3 py-2">
                <a href="{{ route('admin.redirects.create') }}" class="btn btn-sm btn-secondary w-100">
                    <i class="bi bi-plus me-1"></i>Создать редирект
                </a>
            </div>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-clock-history me-2 text-muted"></i>Последние действия</span>
                <a href="{{ route('admin.logs.index') }}" class="text-muted" style="font-size:.78rem">
                    Все события <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="card-body p-0">
                <ul class="ztr-log-feed" id="recentLogs">
                    <li class="ztr-widget-empty">Загрузка…</li>
                </ul>
            </div>
        </div>
    </div>

</div>

<div class="row g-3 mb-3">

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-cpu me-2 text-muted"></i>Системная информация
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0 ztr-sysinfo-table">
                    <tbody>
                        <tr class="ztr-sys-row" data-bs-toggle="modal" data-bs-target="#modalPlatformUpdate" title="Проверить обновления">
                            <td class="text-muted ps-3">Версия системы</td>
                            <td class="pe-3 text-end">
                                <span class="badge bg-primary" id="currentVersionBadge">{{ config('app.version', '1.0.0') }}</span>
                                <a href="#" id="btnUpdateAvailable" class="badge bg-warning text-dark ms-1 d-none" data-bs-toggle="modal" data-bs-target="#modalPlatformUpdate">
                                    <i class="bi bi-arrow-up-circle me-1"></i><span id="updateVersionLabel"></span>
                                </a>
                                <i class="bi bi-arrow-up-circle ztr-sys-row-icon text-muted ms-1"></i>
                            </td>
                        </tr>
                        <tr class="ztr-sys-row" data-href="{{ url('/') }}" data-target="_blank" title="Открыть сайт">
                            <td class="text-muted ps-3">Домен</td>
                            <td class="pe-3 text-end">
                                <code style="font-size:.8rem">{{ request()->getHost() }}</code>
                                <i class="bi bi-box-arrow-up-right ztr-sys-row-icon text-muted ms-1"></i>
                            </td>
                        </tr>
                        <tr class="ztr-sys-row" data-href="https://www.php.net/releases/{{ PHP_MAJOR_VERSION }}.{{ PHP_MINOR_VERSION }}/" data-target="_blank" title="Документация PHP">
                            <td class="text-muted ps-3">PHP</td>
                            <td class="pe-3 text-end">
                                {{ PHP_VERSION }}
                                <i class="bi bi-box-arrow-up-right ztr-sys-row-icon text-muted ms-1"></i>
                            </td>
                        </tr>
                        <tr class="ztr-sys-row" id="pgRow" data-target="_blank" title="Документация PostgreSQL">
                            <td class="text-muted ps-3">PostgreSQL</td>
                            <td class="pe-3 text-end" id="pgVersion">
                                <span class="text-muted">—</span>
                            </td>
                        </tr>
                        <tr class="ztr-sys-row" data-href="https://laravel.com/docs/{{ Str::before(app()->version(), '.') }}.x" data-target="_blank" title="Документация Laravel">
                            <td class="text-muted ps-3">Laravel</td>
                            <td class="pe-3 text-end">
                                {{ app()->version() }}
                                <i class="bi bi-box-arrow-up-right ztr-sys-row-icon text-muted ms-1"></i>
                            </td>
                        </tr>
                        <tr class="ztr-sys-row" data-href="{{ route('admin.database') }}" title="Управление базой данных">
                            <td class="text-muted ps-3">Размер БД</td>
                            <td class="pe-3 text-end" id="dbSize">
                                <a href="#" class="ztr-lazy-load text-muted" data-metric="db_size" style="font-size:.8rem">
                                    <i class="bi bi-calculator me-1"></i>Рассчитать
                                </a>
                            </td>
                        </tr>
                        <tr class="ztr-sys-row" data-href="{{ route('admin.settings') }}" title="Настройки - управление кешем">
                            <td class="text-muted ps-3">Размер кеша</td>
                            <td class="pe-3 text-end" id="cacheSize">
                                <a href="#" class="ztr-lazy-load text-muted" data-metric="cache_size" style="font-size:.8rem">
                                    <i class="bi bi-calculator me-1"></i>Рассчитать
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-bar-chart me-2 text-muted"></i>Статистика контента
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted ps-3">Документов</td>
                            <td class="pe-3 text-end" id="statDocuments">
                                <a href="#" class="ztr-lazy-load text-muted" data-metric="stat_documents" style="font-size:.8rem">
                                    <i class="bi bi-calculator me-1"></i>Рассчитать
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-3">Рубрик</td>
                            <td class="pe-3 text-end" id="statRubrics"><span class="text-muted">—</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-3">Запросов</td>
                            <td class="pe-3 text-end" id="statRequests"><span class="text-muted">—</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-3">Макетов</td>
                            <td class="pe-3 text-end" id="statLayouts"><span class="text-muted">—</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-3">Модулей</td>
                            <td class="pe-3 text-end" id="statModules"><span class="text-muted">—</span></td>
                        </tr>
                        <tr>
                            <td class="text-muted ps-3">Пользователей</td>
                            <td class="pe-3 text-end" id="statUsers"><span class="text-muted">—</span></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header">
                <i class="bi bi-journal-text me-2 text-muted"></i>Журналы событий
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <td class="text-muted ps-3">Лог событий</td>
                            <td class="pe-3 text-end" id="logEvents">
                                <a href="#" class="ztr-lazy-load text-muted" data-metric="log_events" style="font-size:.8rem">
                                    <i class="bi bi-calculator me-1"></i>Рассчитать
                                </a>
                            </td>
                        </tr>
                        <tr id="row404">
                            <td class="text-muted ps-3">Ошибки 404</td>
                            <td class="pe-3 text-end" id="log404"><span class="text-muted">—</span></td>
                        </tr>
                        <tr id="rowSql">
                            <td class="text-muted ps-3">SQL ошибки</td>
                            <td class="pe-3 text-end" id="logSql"><span class="text-muted">—</span></td>
                        </tr>
                    </tbody>
                </table>
                <div class="px-3 pb-3 pt-2">
                    <a href="{{ route('admin.logs.index') }}" class="btn btn-sm btn-secondary w-100">
                        <i class="bi bi-arrow-right me-1"></i> Открыть журналы
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

@stack('dashboard_widgets')

<div class="card mb-3">
    <div class="card-body py-2 px-3 d-flex align-items-center gap-2 flex-wrap">
        <span class="text-muted me-1" style="font-size:.8rem">Статус компонентов:</span>
        <div id="featureFlags" class="d-flex flex-wrap gap-2">
            <span class="text-muted" style="font-size:.8rem">Загрузка…</span>
        </div>
    </div>
</div>

<div class="modal fade" id="modalLazyConfirm" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-hourglass me-2"></i>Расчёт метрики</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" style="font-size:.875rem">Операция может занять некоторое время. Продолжить?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnLazyConfirm">Рассчитать</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/dashboard.css') }}">
@endpush

@push('scripts')
<script>
    window.ZentraConfig = {
        onlineUsersUrl:   '{{ route('admin.dashboard.online-users') }}',
        statsUrl:         '{{ route('admin.dashboard.stats') }}',
        metricUrl:        '{{ route('admin.dashboard.metric') }}',
        widgetsUrl:       '{{ route('admin.dashboard.widgets') }}',
        usersUrl:         '{{ url('admin/users') }}',
        docsEditUrl:      '{{ url('admin/documents') }}',
        redirectCreateUrl:'{{ route('admin.redirects.create') }}',
        redirectMissesUrl:'{{ route('admin.redirects.misses') }}',
        logsUrl:          '{{ route('admin.logs.index') }}',
        platformCheckUrl: '{{ route('admin.platform.check') }}',
        platformUpdateUrl:'{{ route('admin.platform.update') }}',
        currentVersion:   '{{ config('app.version', '1.0.0') }}',
        csrfToken:        '{{ csrf_token() }}',
    };
</script>
<script src="{{ route('admin.asset', 'js/dashboard.js') }}"></script>
@endpush
