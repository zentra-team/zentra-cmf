@extends('admin.layout')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/database.css') }}">
@endpush

@section('title', 'Управление базой данных')

@section('content')
<div class="ztr-page-title"><i class="bi bi-database me-2"></i>Управление базой данных</div>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="dbTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tabOverview" id="tab-overview">
                    <i class="bi bi-speedometer2 me-1"></i>Обзор
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tabTables" id="tab-tables">
                    <i class="bi bi-table me-1"></i>Таблицы
                </a>
            </li>
            @if($canOptimize)
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tabMaintenance" id="tab-maintenance">
                    <i class="bi bi-wrench-adjustable me-1"></i>Обслуживание
                </a>
            </li>
            @endif
            @if($canBackup)
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tabBackups" id="tab-backups">
                    <i class="bi bi-archive me-1"></i>Резервные копии
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tabCreate" id="tab-create">
                    <i class="bi bi-plus-circle me-1"></i>Создать копию
                </a>
            </li>
            @endif
        </ul>
    </div>
    <div class="card-body tab-content">

        <div class="tab-pane fade show active" id="tabOverview">

            <div class="d-flex align-items-center justify-content-between mb-3">
                <div class="text-muted ztr-db-fs-82">Статистика загружается из системных каталогов PostgreSQL</div>
                <button class="btn btn-sm btn-secondary" id="btnRefreshStats">
                    <i class="bi bi-arrow-clockwise me-1"></i>Обновить
                </button>
            </div>

            <div class="row g-3 mb-4" id="statsCards">
                @foreach([
                    ['id' => 'stat-db-size',     'label' => 'Размер базы данных',     'icon' => 'bi-database',        'color' => '#7c5cbf'],
                    ['id' => 'stat-pg-version',  'label' => 'Версия PostgreSQL',      'icon' => 'bi-info-circle',     'color' => '#5c8cbf'],
                    ['id' => 'stat-connections', 'label' => 'Активных соединений',    'icon' => 'bi-plug',            'color' => '#5cbf8c'],
                    ['id' => 'stat-cache-hit',   'label' => 'Cache Hit Ratio',        'icon' => 'bi-lightning-charge','color' => '#bfaa5c'],
                    ['id' => 'stat-tables',      'label' => 'Таблиц в БД',            'icon' => 'bi-grid-3x3',        'color' => '#7c5cbf'],
                    ['id' => 'stat-rows',        'label' => 'Живых строк (примерно)', 'icon' => 'bi-list-ol',         'color' => '#5c8cbf'],
                    ['id' => 'stat-dead',        'label' => 'Мёртвых строк',          'icon' => 'bi-exclamation-triangle', 'color' => '#bf7c5c'],
                    ['id' => 'stat-vacuum',      'label' => 'Нужен VACUUM',           'icon' => 'bi-wind',            'color' => '#bf5c5c'],
                ] as $card)
                <div class="col-md-3 col-sm-6">
                    <div class="p-3 rounded h-100 ztr-db-card">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi {{ $card['icon'] }} ztr-db-fs-100" style="color:{{ $card['color'] }}"></i>
                            <span class="text-muted ztr-db-stat-label">{{ $card['label'] }}</span>
                        </div>
                        <div class="fw-semibold stat-value ztr-db-fs-110" id="{{ $card['id'] }}">
                            <span class="spinner-border spinner-border-sm text-secondary"></span>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <div class="ztr-alert-info">
                <i class="bi bi-info-circle-fill ztr-alert-icon"></i>
                <div class="ztr-db-full-width">
                    <div class="fw-semibold mb-2">Справка по показателям</div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <p class="mb-1"><i class="bi-lightning-charge ztr-db-color-amber"></i> <strong class="ztr-db-ref-label">Cache Hit Ratio</strong> - доля запросов, обработанных из кэша (буферного пула). Норма: &gt; 95%. Значение ниже говорит о недостатке RAM.</p>
                            <p class="mb-0 mt-3"><i class="bi-exclamation-triangle ztr-db-color-orange"></i> <strong class="ztr-db-ref-label">Мёртвые строки</strong> - удалённые или обновлённые строки, ещё не очищенные VACUUM. Высокое значение ведёт к bloat (раздуванию таблиц).</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><i class="bi-wind ztr-db-color-coral"></i>  <strong class="ztr-db-ref-label">Нужен VACUUM</strong> - количество таблиц, где мёртвых строк более 10% от живых. Рекомендуется запустить VACUUM ANALYZE.</p>
                            <p class="mb-0 mt-3"><i class="bi-list-ol ztr-db-color-blue"></i> <strong class="ztr-db-ref-label">Живые строки</strong> - приближённое значение из pg_stat_user_tables (статистика, не точный COUNT). Для точного значения используйте вкладку Таблицы.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="tabTables">

            <div class="d-flex align-items-center gap-2 mb-2 ztr-db-search-toggle" id="searchToggleTables">
                <i class="bi bi-search ztr-db-search-icon"></i>
                <span class="ztr-db-search-label">Фильтр и сортировка таблиц</span>
                <i class="bi bi-chevron-down ms-auto ztr-db-search-chevron"></i>
            </div>
            <div class="collapse mb-3" id="searchPanelTables">
                <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" class="form-control form-control-sm ztr-db-filter-input" id="tableFilter"
                                   placeholder="Фильтр по названию...">
                            <select class="form-select form-select-sm ztr-db-sort-select" id="tableSort">
                                <option value="name_asc" selected>По имени A→Z</option>
                                <option value="name_desc">По имени Z→A</option>
                                <option value="total_bytes_desc">По размеру ↓ (больше)</option>
                                <option value="total_bytes_asc">По размеру ↑ (меньше)</option>
                                <option value="live_rows_desc">По строкам ↓ (больше)</option>
                                <option value="live_rows_asc">По строкам ↑ (меньше)</option>
                                <option value="dead_rows_desc">По мёртвым ↓ (больше)</option>
                                <option value="dead_rows_asc">По мёртвым ↑ (меньше)</option>
                            </select>
                        </div>
                        <button class="btn btn-sm btn-secondary" id="btnRefreshTables">
                            <i class="bi bi-arrow-clockwise me-1"></i>Обновить
                        </button>
                    </div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3 p-2 rounded ztr-db-card">
                <div class="d-flex align-items-center gap-2 pe-3 ztr-db-legend-sep">
                    <button class="btn btn-sm btn-secondary py-0 px-1 ztr-db-legend-btn">V</button>
                    <span class="ztr-db-legend-text">Убрать мусор</span>
                </div>
                <div class="d-flex align-items-center gap-2 pe-3 ztr-db-legend-sep">
                    <button class="btn btn-sm btn-primary py-0 px-1 ztr-db-legend-btn">VA</button>
                    <span class="ztr-db-legend-text">Убрать мусор + ускорить поиск <span class="ztr-db-hint-success">✓ рекомендуется</span></span>
                </div>
                <div class="d-flex align-items-center gap-2 pe-3 ztr-db-legend-sep">
                    <button class="btn btn-sm btn-secondary py-0 px-1 ztr-db-legend-btn">A</button>
                    <span class="ztr-db-legend-text">Ускорить поиск</span>
                </div>
                <div class="d-flex align-items-center gap-2 pe-3 ztr-db-legend-sep">
                    <button class="btn btn-sm btn-secondary py-0 px-1 ztr-db-legend-btn">RI</button>
                    <span class="ztr-db-legend-text">Пересобрать индексы</span>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <button class="btn btn-sm btn-danger py-0 px-1 ztr-db-legend-btn">VF</button>
                    <span class="ztr-db-legend-text">Глубокая очистка <span class="ztr-db-hint-danger">⚠ блокирует таблицу</span></span>
                </div>
            </div>

            <div id="tablesLoading" class="text-center py-4 text-muted">
                <span class="spinner-border spinner-border-sm me-2"></span>Загрузка статистики таблиц...
            </div>

            <div id="tablesContainer" class="ztr-db-hidden">
                <div class="table-responsive">
                    <table class="table table-sm align-middle ztr-db-fs-80">
                        <thead>
                            <tr>
                                <th class="ztr-db-col-name">Таблица</th>
                                <th class="text-center ztr-db-col-rows">Строки</th>
                                <th class="text-center ztr-db-col-dead">Мёртвые</th>
                                <th class="text-center ztr-db-col-size">Размер</th>
                                <th class="text-center ztr-db-col-vacuum">VACUUM</th>
                                <th class="text-center ztr-db-col-analyze">ANALYZE</th>
                                <th class="text-center ztr-db-col-actions-tables">Действия</th>
                            </tr>
                        </thead>
                        <tbody id="tablesBody"></tbody>
                    </table>
                </div>
                <div class="text-muted mt-1 ztr-db-fs-75" id="tablesSummary"></div>
            </div>
        </div>

        @if($canOptimize)
        <div class="tab-pane fade" id="tabMaintenance">

            <div class="mb-4">
                <div class="fw-semibold mb-3 ztr-db-fs-90">
                    <i class="bi bi-lightning-charge me-1 ztr-db-color-purple"></i>
                    Операции для всей базы данных
                </div>
                <div class="row g-3">

                    <div class="col-md-4">
                        <div class="p-3 rounded h-100 d-flex flex-column ztr-db-card">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-wind ztr-db-color-blue ztr-db-fs-120"></i>
                                <span class="fw-semibold ztr-db-fs-88">VACUUM</span>
                            </div>
                            <p class="text-muted mb-3 ztr-db-fs-78">
                                Когда вы удаляете или редактируете записи, старые данные не стираются сразу - они помечаются как «мусор» и занимают место. VACUUM убирает этот мусор. Сайт при этом продолжает работать в обычном режиме.
                            </p>
                            <button class="btn btn-sm btn-secondary btn-maintenance w-100 mt-auto" data-type="vacuum">
                                <i class="bi bi-play me-1"></i>Запустить VACUUM
                            </button>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded h-100 d-flex flex-column ztr-db-card">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-stars ztr-db-color-purple ztr-db-fs-120"></i>
                                <span class="fw-semibold ztr-db-fs-88">VACUUM ANALYZE</span>
                            </div>
                            <p class="text-muted mb-3 ztr-db-fs-78">
                                Делает всё то же, что VACUUM, плюс помогает базе данных лучше выбирать, как выполнять поисковые запросы. Рекомендуется как основная операция для регулярного обслуживания - раз в неделю или после большого объёма правок.
                            </p>
                            <button class="btn btn-sm btn-primary btn-maintenance w-100 mt-auto" data-type="vacuum_analyze">
                                <i class="bi bi-play me-1"></i>Запустить VACUUM ANALYZE
                            </button>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 rounded h-100 d-flex flex-column ztr-db-card">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-graph-up ztr-db-color-green ztr-db-fs-120"></i>
                                <span class="fw-semibold ztr-db-fs-88">ANALYZE</span>
                            </div>
                            <p class="text-muted mb-3 ztr-db-fs-78">
                                Помогает базе данных быстрее находить нужные данные, не затрагивая накопившийся мусор. Полезно после загрузки большого количества новых записей - например, при импорте данных.
                            </p>
                            <button class="btn btn-sm btn-secondary btn-maintenance w-100 mt-auto" data-type="analyze">
                                <i class="bi bi-play me-1"></i>Запустить ANALYZE
                            </button>
                        </div>
                    </div>

                </div>

                <div class="row g-3 mt-1">

                    <div class="col-md-6">
                        <div class="p-3 rounded h-100 d-flex flex-column ztr-db-card-danger">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-fire ztr-db-color-red ztr-db-fs-120"></i>
                                <span class="fw-semibold ztr-db-fs-88">VACUUM FULL</span>
                                <span class="badge ztr-db-badge-danger">опасно</span>
                            </div>
                            <p class="text-muted mb-3 ztr-db-fs-78">
                                Полностью перезаписывает все таблицы и возвращает дисковое место - помогает, если база данных «раздулась» после удаления большого количества данных.
                                После перезаписи автоматически запускается VACUUM ANALYZE - статистика и счётчики мёртвых строк обновятся сразу.
                                <strong class="ztr-db-color-red">Во время выполнения сайт не сможет записывать данные.</strong>
                                Запускайте только в нерабочее время. Для большой базы может занять несколько минут.
                            </p>
                            <button class="btn btn-sm btn-danger btn-maintenance-confirm w-100 mt-auto"
                                data-type="vacuum_full"
                                data-confirm="VACUUM FULL перепишет все таблицы. Во время выполнения база данных будет заблокирована для записи. Продолжить?">
                                <i class="bi bi-fire me-1"></i>Запустить VACUUM FULL
                            </button>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 rounded h-100 d-flex flex-column ztr-db-card-warning">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-arrow-repeat ztr-db-color-yellow ztr-db-fs-120"></i>
                                <span class="fw-semibold ztr-db-fs-88">REINDEX DATABASE</span>
                                <span class="badge ztr-db-badge-warning">медленно</span>
                            </div>
                            <p class="text-muted mb-3 ztr-db-fs-78">
                                Индексы - это как оглавление книги: они помогают базе быстро находить данные. Со временем они могут «засориться» и замедлить поиск. Эта операция пересобирает их заново.
                                <strong class="ztr-db-color-yellow">Может замедлить работу сайта</strong> на время выполнения. Запускайте только если поиск или фильтрация стали заметно медленнее.
                            </p>
                            <button class="btn btn-sm btn-warning btn-maintenance-confirm w-100 mt-auto"
                                data-type="reindex_db"
                                data-confirm="REINDEX DATABASE перестроит все индексы. Операция может занять длительное время. Продолжить?">
                                <i class="bi bi-arrow-repeat me-1"></i>Запустить REINDEX DATABASE
                            </button>
                        </div>
                    </div>

                </div>
            </div>

            <div class="row g-3 mt-0">
                <div class="col-12">
                    <div class="p-3 rounded d-flex flex-column ztr-db-card-info">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-arrow-clockwise ztr-db-color-info ztr-db-fs-120"></i>
                            <span class="fw-semibold ztr-db-fs-88">Сбросить последовательности</span>
                        </div>
                        <p class="text-muted mb-3 ztr-db-fs-78">
                            Синхронизирует автоинкрементные счётчики всех таблиц с реальными данными.
                            Нужно после ручного восстановления базы из резервной копии — если новые записи не сохраняются с ошибкой <code>duplicate key value violates unique constraint</code>.
                            Безопасно в любое время: данные не изменяются, только счётчики.
                        </p>
                        <button class="btn btn-sm btn-info btn-maintenance-confirm align-self-start"
                            data-type="reset_sequences"
                            data-confirm="Сбросить последовательности? Счётчики будут выставлены по максимальному ID каждой таблицы. Данные не изменятся.">
                            <i class="bi bi-arrow-clockwise me-1"></i>Сбросить последовательности
                        </button>
                    </div>
                </div>
            </div>

            <hr class="ztr-db-hr">

            <div id="maintProgress" class="p-3 rounded mb-3 ztr-db-hidden ztr-db-card">
                <div class="d-flex align-items-center gap-2 text-muted ztr-db-fs-85">
                    <span class="spinner-border spinner-border-sm"></span>
                    <span id="maintProgressText">Выполнение операции...</span>
                </div>
                <div class="text-muted mt-1 ztr-db-fs-75">Операция выполняется на сервере. Не закрывайте страницу.</div>
            </div>

            <div class="ztr-alert-info">
                <i class="bi bi-info-circle-fill ztr-alert-icon"></i>
                <div>
                    <div class="fw-semibold mb-2">С чего начать?</div>
                    <ol class="mb-0 ps-3 ztr-db-ol-spacing">
                        <li>Откройте вкладку <strong>Обзор</strong> - посмотрите на показатели «Мёртвые строки» и «Нужен VACUUM»</li>
                        <li>Если эти цифры высокие - запустите <strong>VACUUM ANALYZE</strong>. Это безопасно и не мешает работе сайта</li>
                        <li>Если база данных стала занимать неожиданно много места (например, после удаления тысяч записей) - запустите <strong>VACUUM FULL</strong>, но только в нерабочее время</li>
                        <li>Если поиск или фильтрация работают заметно медленнее - запустите <strong>REINDEX DATABASE</strong></li>
                        <li>Для точечной работы с конкретной таблицей - перейдите во вкладку <strong>Таблицы</strong> и воспользуйтесь кнопками обслуживания в нужной строке</li>
                    </ol>
                </div>
            </div>
        </div>
        @endif

        @if($canBackup)
        <div class="tab-pane fade" id="tabBackups">

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="p-3 rounded ztr-db-card">
                        <div class="text-muted mb-1 ztr-db-fs-75">Размер базы данных</div>
                        <div class="fw-semibold ztr-db-fs-110">{{ $dbSize }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded ztr-db-card">
                        <div class="text-muted mb-1 ztr-db-fs-75">Последний бэкап</div>
                        <div class="fw-semibold">{{ $lastBackup?->date ?? '—' }}</div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 rounded ztr-db-card">
                        <div class="text-muted mb-1 ztr-db-fs-75">Копий на сервере</div>
                        <div class="fw-semibold ztr-db-fs-110">{{ $backups->count() }}</div>
                    </div>
                </div>
                @if($diskFree)
                <div class="col-md-3">
                    <div class="p-3 rounded ztr-db-card">
                        <div class="text-muted mb-1 ztr-db-fs-75">
                            Свободно на диске
                            <span style="color:{{ $diskFreeColor }}">
                                (@if($diskFreeColor === '#5cbf8c')достаточно@elseif($diskFreeColor === '#c8a840')заканчивается@else мало!@endif)
                            </span>
                        </div>
                        <div class="fw-semibold ztr-db-fs-110" style="color:{{ $diskFreeColor }}">{{ $diskFree }}</div>
                    </div>
                </div>
                @endif
            </div>

            @if($canRestore)
            <div class="mb-4">
                <div class="fw-semibold mb-2 ztr-db-fs-85">
                    <i class="bi bi-upload me-1"></i>Загрузить бэкап с компьютера
                </div>
                <div class="d-flex align-items-center gap-2">
                    <input type="file" id="uploadBackupFile" class="form-control form-control-sm ztr-db-upload-input"
                        accept=".sql,.gz,.dump">
                    <button type="button" class="btn btn-sm btn-warning" id="btnRestoreUpload">
                        <i class="bi bi-arrow-counterclockwise me-1"></i>Восстановить
                    </button>
                    <i class="bi bi-question-circle text-muted ms-2"
                       style="cursor:pointer;font-size:1rem"
                       data-bs-toggle="tooltip"
                       data-bs-placement="right"
                       data-bs-html="true"
                       title="Поддерживаются: <code>.sql</code>, <code>.sql.gz</code>, <code>.dump</code>.<br>Файлы <code>.tar.gz</code> и другие архивы не поддерживаются.<br>Максимальный размер: <strong>{{ $phpUploadLimit }}</strong>"></i>
                </div>
            </div>
            @endif

            <hr class="ztr-db-hr">

            <div class="fw-semibold mb-2 ztr-db-fs-85">
                <i class="bi bi-hdd me-1"></i>Сохранённые копии на сервере
            </div>

            @if($backups->isEmpty())
            <p class="text-muted ztr-db-fs-85">Резервных копий на сервере нет.</p>
            @else
            <table class="table table-sm align-middle">
                <thead>
                    <tr>
                        <th>Имя файла</th>
                        <th class="text-center ztr-db-col-backup-size">Размер</th>
                        <th class="text-center ztr-db-col-backup-date">Дата создания</th>
                        <th class="text-center ztr-db-col-backup-actions">Действия</th>
                    </tr>
                </thead>
                <tbody id="backupsTableBody">
                    @foreach($backups as $backup)
                    <tr data-name="{{ $backup->name }}">
                        <td class="ztr-db-cell-filename" title="{{ $backup->name }}">{{ $backup->name }}</td>
                        <td class="text-center ztr-db-cell-muted">{{ $backup->size }}</td>
                        <td class="text-center ztr-db-cell-muted-nowrap">{{ $backup->date }}</td>
                        <td class="text-center ztr-db-nowrap">
                            <div class="d-flex gap-2 justify-content-center">
                                <a href="{{ route('admin.database.download', $backup->name) }}"
                                   class="btn btn-sm btn-outline-success" title="Скачать на компьютер">
                                    <i class="bi bi-download"></i>
                                </a>
                                @if($canRestore)
                                <button type="button" class="btn btn-sm btn-outline-warning btn-restore"
                                    data-name="{{ $backup->name }}" title="Восстановить базу из этого файла">
                                    <i class="bi bi-arrow-counterclockwise"></i>
                                </button>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete-backup"
                                    data-name="{{ $backup->name }}" title="Удалить резервную копию">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        <div class="tab-pane fade" id="tabCreate">

            <div class="ztr-db-create-container">
                <p class="text-muted mb-3 ztr-db-fs-85">
                    Создаёт резервную копию через <code>pg_dump</code> и сжимает её через <code>gzip</code>.
                    Файл будет скачан автоматически и/или сохранён на сервере.
                </p>

                <div class="mb-3">
                    <label class="form-label">Имя файла <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <input type="text" id="backupFilename" class="form-control"
                            value="backup_{{ now()->format('d-m-y_His') }}">
                        <span class="input-group-text ztr-db-input-suffix">.sql.gz</span>
                    </div>
                    <div class="form-text">Только латинские буквы, цифры, дефис и подчёркивание. Расширение <code>.sql.gz</code> добавляется автоматически.</div>
                </div>

                <div class="mb-4">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="saveLocal" checked>
                        <label class="form-check-label" for="saveLocal">
                            Сохранить копию на сервере
                        </label>
                        <div class="form-text">Файл будет доступен во вкладке «Резервные копии».</div>
                    </div>
                </div>

                <button type="button" class="btn btn-sm btn-primary" id="btnCreateBackup">
                    <i class="bi bi-archive me-1"></i>Создать резервную копию
                </button>

                <div id="backupProgress" class="mt-3 ztr-db-hidden">
                    <div class="d-flex align-items-center gap-2 text-muted ztr-db-fs-85">
                        <span class="spinner-border spinner-border-sm"></span>
                        <span>Выполняется резервное копирование...</span>
                    </div>
                </div>
            </div>
        </div>
        @endif

    </div>
</div>

@if($canBackup)
<div class="modal fade" id="deleteBackupModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-trash me-2 text-danger"></i>Удалить резервную копию</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Удалить файл <strong id="deleteBackupFilename"></strong>?
                <div class="text-muted mt-1 ztr-db-fs-82">Это действие необратимо.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteBackupConfirm">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($canRestore)
<div class="modal fade" id="restoreUploadModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-upload me-2"></i>Восстановление из файла</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="ztr-alert-info d-flex gap-2 mb-3">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                    <div>Восстановление <strong>перезапишет текущую базу данных</strong>. Все несохранённые данные будут утеряны. Это действие необратимо.</div>
                </div>
                <div>Восстановить базу из файла <strong id="uploadFilename">—</strong>?</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-warning btn-sm" id="btnRestoreUploadConfirm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Восстановить
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="restoreModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Восстановление базы данных</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="ztr-alert-info d-flex gap-2 mb-3">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                    <div>Восстановление перезапишет текущую базу данных. Это действие необратимо.</div>
                </div>
                Восстановить из файла <strong id="restoreFilename"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-warning btn-sm" id="btnRestoreConfirm">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Восстановить
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($canOptimize)
<div class="modal fade" id="maintModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Подтверждение операции</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="ztr-alert-info d-flex gap-2">
                    <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                    <div id="maintModalText"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnMaintConfirm">
                    <i class="bi bi-play me-1"></i>Запустить
                </button>
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
window.ZentraConfig = {
    routes: {
        stats: '{{ route('admin.database.stats') }}',
        tables: '{{ route('admin.database.tables') }}',
        maintenance: '{{ route('admin.database.maintenance') }}',
        backup: '{{ route('admin.database.backup') }}',
        restoreUpload: '{{ route('admin.database.restore-upload') }}'
    },
    dbIndexUrl: '{{ route('admin.database') }}',
    effectiveLimit: {{ $effectiveLimit }},
    phpUploadLimit: '{{ $phpUploadLimit }}'
};
</script>
<script src="{{ route('admin.asset', 'js/database.js') }}"></script>
@endpush
