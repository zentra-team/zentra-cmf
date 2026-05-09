@extends('admin.layout')

@section('title', 'События')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/logs.css') }}">
@endpush

@section('content')
<div class="ztr-page-title"><i class="bi bi-journal-text me-2"></i>События</div>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="logTabs">
            @if($canTabAdmin)
            <li class="nav-item">
                <a class="nav-link {{ $defaultTab === 'admin' ? 'active' : '' }}" data-bs-toggle="tab" href="#tabAdmin">
                    <i class="bi bi-person-check me-1"></i>Действия пользователей
                    <span class="badge bg-secondary ms-1 ztr-log-badge-count">{{ $adminLogs->total() }}</span>
                </a>
            </li>
            @endif
            @if($canTab404)
            <li class="nav-item">
                <a class="nav-link {{ $defaultTab === '404' ? 'active' : '' }}" data-bs-toggle="tab" href="#tab404">
                    <i class="bi bi-exclamation-circle me-1"></i>Ошибки 404
                    <span class="badge bg-secondary ms-1 ztr-log-badge-count">{{ $logs404->total() }}</span>
                </a>
            </li>
            @endif
            @if($canTabDb)
            <li class="nav-item">
                <a class="nav-link {{ $defaultTab === 'db' ? 'active' : '' }}" data-bs-toggle="tab" href="#tabDb">
                    <i class="bi bi-database-exclamation me-1"></i>Ошибки PostgreSQL
                    <span class="badge bg-secondary ms-1 ztr-log-badge-count">{{ $logsDb->total() }}</span>
                </a>
            </li>
            @endif
            @if($canTabFramework)
            <li class="nav-item">
                <a class="nav-link {{ $defaultTab === 'framework' ? 'active' : '' }}" data-bs-toggle="tab" href="#tabFramework">
                    <i class="bi bi-terminal me-1"></i>Логи фреймворка
                </a>
            </li>
            @endif
        </ul>
    </div>
    <div class="card-body tab-content p-0">

        @if($canTabAdmin)
        <div class="tab-pane fade {{ $defaultTab === 'admin' ? 'show active' : '' }}" id="tabAdmin">

            <div class="border-bottom">
                <div class="d-flex align-items-center gap-2 px-3 py-2 ztr-log-search-toggle" id="searchToggleAdmin" data-target="searchPanelAdmin">
                    <i class="bi bi-search ztr-log-search-icon"></i>
                    <span class="ztr-log-search-label">Поиск и фильтры</span>
                    <i class="bi bi-chevron-down ms-auto ztr-log-search-chevron"></i>
                </div>
                <div class="collapse {{ request()->hasAny(['search','user_id','action_type','date_from','date_to']) ? 'show' : '' }}" id="searchPanelAdmin">
                    <form method="GET" action="{{ route('admin.logs.index') }}"
                          class="p-3 d-flex flex-wrap gap-2 align-items-end ztr-log-filter">
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">Поиск по действию</label>
                            <input type="text" name="search" class="form-control form-control-sm ztr-log-input-search"
                                value="{{ request('search') }}" placeholder="Текст действия">
                        </div>
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">Пользователь</label>
                            <select name="user_id" class="form-select form-select-sm ztr-log-select-user">
                                <option value="">— Все —</option>
                                @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">Тип действия</label>
                            <select name="action_type" class="form-select form-select-sm ztr-log-select-action">
                                <option value="">— Все —</option>
                                <option value="create" {{ request('action_type') === 'create' ? 'selected' : '' }}>Создание</option>
                                <option value="edit"   {{ request('action_type') === 'edit'   ? 'selected' : '' }}>Редактирование</option>
                                <option value="delete" {{ request('action_type') === 'delete' ? 'selected' : '' }}>Удаление</option>
                                <option value="other"  {{ request('action_type') === 'other'  ? 'selected' : '' }}>Прочее</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">Период с</label>
                            <input type="date" name="date_from" class="form-control form-control-sm"
                                value="{{ request('date_from') }}">
                        </div>
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">по</label>
                            <input type="date" name="date_to" class="form-control form-control-sm"
                                value="{{ request('date_to') }}">
                        </div>
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">На странице</label>
                            <select name="per_page" class="form-select form-select-sm ztr-log-select-pp">
                                @foreach([10,25,50,100] as $pp)
                                <option value="{{ $pp }}" {{ request('per_page', 25) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-1 align-self-end">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-search me-1"></i>Фильтр
                            </button>
                            <a href="{{ route('admin.logs.index') }}" class="btn btn-sm btn-secondary">Сбросить</a>
                        </div>
                    </form>
                </div>
            </div>

            @if($canExport || $canClear)
            <div class="d-flex gap-2 p-3 border-bottom ztr-log-toolbar">
                @if($canExport && !$adminLogs->isEmpty())
                <a href="{{ route('admin.logs.export', 'admin') }}?{{ http_build_query(request()->only('search', 'user_id', 'action_type', 'date_from', 'date_to')) }}" class="btn btn-sm btn-secondary">
                    <i class="bi bi-download me-1"></i>Экспорт CSV
                </a>
                @endif
                @if($canClear)
                <button type="button" class="btn btn-sm btn-danger ms-auto btn-clear-log" data-type="admin">
                    <i class="bi bi-trash me-1"></i>Очистить журнал
                </button>
                @endif
            </div>
            @endif

            @if($adminLogs->isEmpty())
            <p class="text-muted text-center py-4 mb-0">Записей нет.</p>
            @else
            <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ztr-log-col-id">ID</th>
                        <th class="ztr-log-col-date">Дата</th>
                        <th class="ztr-log-col-user">Пользователь</th>
                        <th class="ztr-log-col-ip">IP</th>
                        <th class="ztr-log-col-type">Тип</th>
                        <th>Действие</th>
                        <th class="ztr-log-col-object">Объект</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($adminLogs as $log)
                    <tr>
                        <td class="text-muted">{{ $log->id }}</td>
                        <td class="ztr-log-date">
                            {{ $log->created_at?->format($dateFormat.' '.$timeFormat) }}
                        </td>
                        <td class="ztr-log-text-md">
                            {{ $log->user_name ?? '—' }}
                        </td>
                        <td class="ztr-log-ip">
                            {{ $log->ip }}
                        </td>
                        <td>
                            @php
                            $typeColors = ['create' => 'success', 'edit' => 'primary', 'delete' => 'danger', 'other' => 'secondary'];
                            $typeLabels = ['create' => 'Создание', 'edit' => 'Изменение', 'delete' => 'Удаление', 'other' => 'Прочее'];
                            @endphp
                            <span class="badge bg-{{ $typeColors[$log->action_type] ?? 'secondary' }} ztr-log-badge-type">
                                {{ $typeLabels[$log->action_type] ?? $log->action_type }}
                            </span>
                        </td>
                        <td class="ztr-log-action">{{ $log->action }}</td>
                        <td class="ztr-log-muted-sm">
                            @if($log->object_type)
                            @php
                            $objLabels = [
                                'block' => 'Блок', 'block_group' => 'Группа блоков', 'layout' => 'Макет',
                                'rubric' => 'Рубрика', 'rubric_field' => 'Поле рубрики',
                                'nav_item' => 'Пункт меню', 'navigation' => 'Навигация',
                                'asset' => 'Файл', 'document' => 'Документ', 'request' => 'Запрос',
                                'user' => 'Пользователь', 'user_group' => 'Группа пользователей',
                                'settings' => 'Настройки', 'cache' => 'Кэш', 'database' => 'База данных',
                                'module' => 'Модуль', 'platform' => 'Платформа',
                            ];
                            @endphp
                            <div>Тип: {{ $objLabels[$log->object_type] ?? $log->object_type }}</div>
                            @if($log->object_title)
                            <div class="fw-semibold ztr-log-obj-title">{{ Str::limit($log->object_title, 30) }}</div>
                            @endif
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            <div class="p-3">{{ $adminLogs->links() }}</div>
            @endif
        </div>
        @endif

        @if($canTab404)
        <div class="tab-pane fade {{ $defaultTab === '404' ? 'show active' : '' }}" id="tab404">

            <div class="border-bottom">
                <div class="d-flex align-items-center gap-2 px-3 py-2 ztr-log-search-toggle" id="searchToggle404" data-target="searchPanel404">
                    <i class="bi bi-search ztr-log-search-icon"></i>
                    <span class="ztr-log-search-label">Поиск и фильтры</span>
                    <i class="bi bi-chevron-down ms-auto ztr-log-search-chevron"></i>
                </div>
                <div class="collapse {{ request()->hasAny(['search_404','date_from_404','date_to_404']) ? 'show' : '' }}" id="searchPanel404">
                    <form method="GET" action="{{ route('admin.logs.index') }}"
                          class="p-3 d-flex flex-wrap gap-2 align-items-end ztr-log-filter">
                        <input type="hidden" name="tab" value="404">
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">Поиск по URL</label>
                            <input type="text" name="search_404" class="form-control form-control-sm ztr-log-input-search"
                                value="{{ request('search_404') }}" placeholder="/path/to/page">
                        </div>
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">Период с</label>
                            <input type="date" name="date_from_404" class="form-control form-control-sm"
                                value="{{ request('date_from_404') }}">
                        </div>
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">по</label>
                            <input type="date" name="date_to_404" class="form-control form-control-sm"
                                value="{{ request('date_to_404') }}">
                        </div>
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">На странице</label>
                            <select name="per_page" class="form-select form-select-sm ztr-log-select-pp">
                                @foreach([10,25,50,100] as $pp)
                                <option value="{{ $pp }}" {{ request('per_page', 25) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-1 align-self-end">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-search me-1"></i>Фильтр
                            </button>
                            <a href="{{ route('admin.logs.index') }}?tab=404" class="btn btn-sm btn-secondary">Сбросить</a>
                        </div>
                    </form>
                </div>
            </div>

            @if($canExport || $canClear)
            <div class="d-flex gap-2 p-3 border-bottom ztr-log-toolbar">
                @if($canExport && !$logs404->isEmpty())
                <a href="{{ route('admin.logs.export', '404') }}?{{ http_build_query(request()->only('search_404', 'date_from_404', 'date_to_404')) }}" class="btn btn-sm btn-secondary">
                    <i class="bi bi-download me-1"></i>Экспорт CSV
                </a>
                @endif
                @if($canClear)
                <button type="button" class="btn btn-sm btn-danger ms-auto btn-clear-log" data-type="404">
                    <i class="bi bi-trash me-1"></i>Очистить журнал
                </button>
                @endif
            </div>
            @endif

            @if($logs404->isEmpty())
            <p class="text-muted text-center py-4 mb-0">Ошибок 404 не зафиксировано.</p>
            @else
            <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ztr-log-col-id">ID</th>
                        <th class="ztr-log-col-date">Дата</th>
                        <th class="ztr-log-col-ip-wide">IP</th>
                        <th>URL</th>
                        <th class="ztr-log-col-wide">Referer</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logs404 as $log)
                    <tr>
                        <td class="text-muted">{{ $log->id }}</td>
                        <td class="ztr-log-date">
                            {{ $log->created_at?->format($dateFormat.' '.$timeFormat) }}
                        </td>
                        <td class="ztr-log-ip">
                            {{ $log->ip }}
                        </td>
                        <td class="ztr-log-text-md-break">{{ $log->url }}</td>
                        <td class="ztr-log-referer">
                            {{ $log->referer ?? '—' }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            <div class="p-3">{{ $logs404->links() }}</div>
            @endif
        </div>
        @endif

        @if($canTabDb)
        <div class="tab-pane fade {{ $defaultTab === 'db' ? 'show active' : '' }}" id="tabDb">

            <div class="border-bottom">
                <div class="d-flex align-items-center gap-2 px-3 py-2 ztr-log-search-toggle" id="searchToggleDb" data-target="searchPanelDb">
                    <i class="bi bi-search ztr-log-search-icon"></i>
                    <span class="ztr-log-search-label">Поиск и фильтры</span>
                    <i class="bi bi-chevron-down ms-auto ztr-log-search-chevron"></i>
                </div>
                <div class="collapse {{ request()->hasAny(['search_db','db_level']) ? 'show' : '' }}" id="searchPanelDb">
                    <form method="GET" action="{{ route('admin.logs.index') }}"
                          class="p-3 d-flex flex-wrap gap-2 align-items-end ztr-log-filter">
                        <input type="hidden" name="tab" value="db">
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">Поиск по сообщению</label>
                            <input type="text" name="search_db" class="form-control form-control-sm ztr-log-input-search"
                                value="{{ request('search_db') }}" placeholder="Текст ошибки">
                        </div>
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">Уровень</label>
                            <select name="db_level" class="form-select form-select-sm ztr-log-select-db-level">
                                <option value="">— Все —</option>
                                <option value="ERROR"   {{ request('db_level') === 'ERROR'   ? 'selected' : '' }}>ERROR</option>
                                <option value="WARNING" {{ request('db_level') === 'WARNING' ? 'selected' : '' }}>WARNING</option>
                                <option value="NOTICE"  {{ request('db_level') === 'NOTICE'  ? 'selected' : '' }}>NOTICE</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">На странице</label>
                            <select name="per_page" class="form-select form-select-sm ztr-log-select-pp">
                                @foreach([10,25,50,100] as $pp)
                                <option value="{{ $pp }}" {{ request('per_page', 25) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-1 align-self-end">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-search me-1"></i>Фильтр
                            </button>
                            <a href="{{ route('admin.logs.index') }}?tab=db" class="btn btn-sm btn-secondary">Сбросить</a>
                        </div>
                    </form>
                </div>
            </div>

            @if($canExport || $canClear)
            <div class="d-flex gap-2 p-3 border-bottom ztr-log-toolbar">
                @if($canExport && !$logsDb->isEmpty())
                <a href="{{ route('admin.logs.export', 'db') }}?{{ http_build_query(request()->only('search_db', 'db_level')) }}" class="btn btn-sm btn-secondary">
                    <i class="bi bi-download me-1"></i>Экспорт CSV
                </a>
                @endif
                @if($canClear)
                <button type="button" class="btn btn-sm btn-danger ms-auto btn-clear-log" data-type="db">
                    <i class="bi bi-trash me-1"></i>Очистить журнал
                </button>
                @endif
            </div>
            @endif

            @if($logsDb->isEmpty())
            <p class="text-muted text-center py-4 mb-0">Ошибок базы данных не зафиксировано.</p>
            @else
            <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ztr-log-col-id">ID</th>
                        <th class="ztr-log-col-date">Дата</th>
                        <th class="ztr-log-col-type">Уровень</th>
                        <th>Сообщение</th>
                        <th class="ztr-log-col-wide">Контекст</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($logsDb as $log)
                    <tr>
                        <td class="text-muted">{{ $log->id }}</td>
                        <td class="ztr-log-date">
                            {{ $log->created_at?->format($dateFormat.' '.$timeFormat) }}
                        </td>
                        <td>
                            @php $lvlColor = match($log->level) { 'ERROR' => 'danger', 'WARNING' => 'warning', default => 'secondary' }; @endphp
                            <span class="badge bg-{{ $lvlColor }} ztr-log-badge-type">{{ $log->level }}</span>
                        </td>
                        <td class="ztr-log-text-md">
                            <div>{{ Str::limit($log->message, 120) }}</div>
                            @if($log->query)
                            <code class="ztr-log-sql">
                                {{ Str::limit($log->query, 200) }}
                            </code>
                            @endif
                        </td>
                        <td class="ztr-log-context">{{ $log->context }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            <div class="p-3">{{ $logsDb->links() }}</div>
            @endif
        </div>
        @endif

        @if($canTabFramework)
        <div class="tab-pane fade {{ $defaultTab === 'framework' ? 'show active' : '' }}" id="tabFramework">

            <div class="border-bottom">
                <div class="d-flex align-items-center gap-2 px-3 py-2 ztr-log-search-toggle" id="searchToggleFramework" data-target="searchPanelFramework">
                    <i class="bi bi-search ztr-log-search-icon"></i>
                    <span class="ztr-log-search-label">Поиск и фильтры</span>
                    <i class="bi bi-chevron-down ms-auto ztr-log-search-chevron"></i>
                </div>
                <div class="collapse {{ request()->hasAny(['log_file','fw_level']) ? 'show' : '' }}" id="searchPanelFramework">
                    <form method="GET" action="{{ route('admin.logs.index') }}"
                          class="p-3 d-flex flex-wrap gap-2 align-items-end ztr-log-filter">
                        <input type="hidden" name="tab" value="framework">
                        @if(count($logFiles) > 1)
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">Файл лога</label>
                            <select name="log_file" class="form-select form-select-sm ztr-log-select-log-file">
                                @foreach($logFiles as $lf)
                                <option value="{{ $lf }}" {{ $activeLogFile === $lf ? 'selected' : '' }}>{{ $lf }}</option>
                                @endforeach
                            </select>
                        </div>
                        @else
                        <input type="hidden" name="log_file" value="{{ $activeLogFile }}">
                        @endif
                        <div>
                            <label class="form-label mb-1 ztr-log-filter-label">Уровень</label>
                            @php
                            $fwLevels = [
                                'ERROR'     => 'Ошибка (ERROR)',
                                'CRITICAL'  => 'Критично (CRITICAL)',
                                'EMERGENCY' => 'Авария (EMERGENCY)',
                                'ALERT'     => 'Тревога (ALERT)',
                                'WARNING'   => 'Предупреждение (WARNING)',
                                'NOTICE'    => 'Уведомление (NOTICE)',
                                'INFO'      => 'Информация (INFO)',
                                'DEBUG'     => 'Отладка (DEBUG)',
                            ];
                            @endphp
                            <select name="fw_level" class="form-select form-select-sm ztr-log-select-fw-level">
                                <option value="">— Все уровни —</option>
                                @foreach($fwLevels as $lvl => $label)
                                <option value="{{ $lvl }}" {{ $fwLevelFilter === $lvl ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="d-flex gap-1 align-self-end">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-search me-1"></i>Применить
                            </button>
                            <a href="{{ route('admin.logs.index') }}?tab=framework" class="btn btn-sm btn-secondary">Сбросить</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="d-flex gap-2 p-3 border-bottom ztr-log-toolbar">
                @if($canExport && $activeLogFile && count($frameworkLogs) > 0)
                <a href="{{ route('admin.logs.framework.download', $activeLogFile) }}"
                   class="btn btn-sm btn-secondary">
                    <i class="bi bi-download me-1"></i>Скачать {{ $activeLogFile }}
                </a>
                @endif
                <div class="ms-auto d-flex align-items-center gap-2">
                    @if($activeLogFile)
                    <span class="text-muted ztr-log-shown-count">
                        Показано последних {{ count($frameworkLogs) }} записей
                    </span>
                    @if($canClear)
                    <button type="button" class="btn btn-sm btn-danger"
                        id="btnClearFramework" data-file="{{ $activeLogFile }}">
                        <i class="bi bi-trash me-1"></i>Очистить лог
                    </button>
                    @endif
                    @endif
                </div>
            </div>

            @if(empty($logFiles))
            <p class="text-muted text-center py-4 mb-0">
                Файлы логов не найдены в <code>storage/logs/</code>
            </p>
            @elseif(empty($frameworkLogs))
            <p class="text-muted text-center py-4 mb-0">Записей нет.</p>
            @else
            <div class="table-responsive">
            <table class="table table-sm align-middle mb-0 ztr-log-fw-table">
                <thead>
                    <tr>
                        <th class="ztr-log-col-date">Дата</th>
                        <th class="ztr-log-col-type">Уровень</th>
                        <th>Сообщение</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($frameworkLogs as $i => $entry)
                    @php
                        $lvlColor = match($entry['level']) {
                            'ERROR','CRITICAL','EMERGENCY','ALERT' => 'danger',
                            'WARNING' => 'warning',
                            'INFO','NOTICE'  => 'info',
                            default          => 'secondary',
                        };
                    @endphp
                    <tr>
                        <td class="ztr-log-fw-date">
                            {{ $entry['date'] }}
                        </td>
                        <td>
                            <span class="badge bg-{{ $lvlColor }} ztr-log-badge-count">{{ $entry['level'] }}</span>
                        </td>
                        <td>
                            <div>{{ Str::limit($entry['message'], 200) }}</div>
                            @if($entry['location'])
                            <div class="ztr-log-location">
                                {{ $entry['location'] }}
                            </div>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>
            @endif

        </div>
        @endif

    </div>
</div>

<div class="modal fade" id="clearFrameworkModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">
                    <i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Очистка лога фреймворка
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-3">Вы собираетесь очистить файл:</p>
                <div class="mb-3 p-2 rounded ztr-log-modal-filename">
                    storage/logs/<span id="fwClearFilename"></span>
                </div>
                <p class="mb-2 fw-semibold">Что произойдёт:</p>
                <ul class="mb-3 ztr-log-modal-list">
                    <li>Содержимое файла будет <strong>полностью удалено</strong> - все записи исчезнут без возможности восстановления</li>
                    <li>Сам файл <strong>останется на сервере</strong> (пустой) - Laravel продолжит писать в него дальше</li>
                    <li>Это действие затрагивает только <strong>данный файл</strong> - другие лог-файлы не изменятся</li>
                </ul>
                <div class="d-flex align-items-start gap-2 p-2 rounded ztr-log-modal-warning">
                    <i class="bi bi-shield-exclamation text-danger flex-shrink-0 mt-1"></i>
                    <span>Если вы хотите сохранить историю ошибок - сначала скачайте файл через кнопку <strong>«Скачать»</strong>, а затем очищайте.</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnClearFrameworkConfirm">
                    <i class="bi bi-trash me-1"></i>Да, очистить файл
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="clearModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Очистка журнала</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Удалить все записи из журнала?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnClearConfirm">
                    <i class="bi bi-trash me-1"></i>Очистить
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.ZentraConfig = {
    logsIndexUrl: '{{ route('admin.logs.index') }}'
};
</script>
<script src="{{ route('admin.asset', 'js/logs.js') }}"></script>
@endpush
