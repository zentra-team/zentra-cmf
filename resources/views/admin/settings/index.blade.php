@extends('admin.layout')

@section('title', 'Системные настройки')

@section('content')
<div class="ztr-page-title"><i class="bi bi-gear me-2"></i>Системные настройки</div>

<ul class="nav nav-tabs mb-4" id="settingsTabs">
    @if($canView['general'])
    <li class="nav-item">
        <a class="nav-link {{ $defaultTab === 'general' ? 'active' : '' }}" data-bs-toggle="tab" href="#tabGeneral">
            <i class="bi bi-sliders me-1"></i>Основные
        </a>
    </li>
    @endif
    @if($canView['env'])
    <li class="nav-item">
        <a class="nav-link {{ $defaultTab === 'env' ? 'active' : '' }}" data-bs-toggle="tab" href="#tabEnv">
            <i class="bi bi-terminal me-1"></i>Окружение
        </a>
    </li>
    @endif
    @if($canView['seo'])
    <li class="nav-item">
        <a class="nav-link {{ $defaultTab === 'seo' ? 'active' : '' }}" data-bs-toggle="tab" href="#tabSeo">
            <i class="bi bi-search me-1"></i>SEO & коды
        </a>
    </li>
    @endif
    @if($canView['cache'])
    <li class="nav-item">
        <a class="nav-link {{ $defaultTab === 'cache' ? 'active' : '' }}" data-bs-toggle="tab" href="#tabCache">
            <i class="bi bi-lightning me-1"></i>Кэш и сессии
        </a>
    </li>
    @endif
    @if($canView['maps'])
    <li class="nav-item">
        <a class="nav-link {{ $defaultTab === 'maps' ? 'active' : '' }}" data-bs-toggle="tab" href="#tabMaps">
            <i class="bi bi-geo-alt me-1"></i>Карты
        </a>
    </li>
    @endif
</ul>

<div class="tab-content">

@if($canView['general'])
<div class="tab-pane fade {{ $defaultTab === 'general' ? 'show active' : '' }}" id="tabGeneral">
    @if(!$canEdit['general'])
    <div class="alert alert-danger py-2 mb-3 ztr-readonly-banner">
        <i class="bi bi-eye me-1"></i>Режим только просмотр - у вас нет прав на сохранение этого таба
    </div>
    @endif
    <form id="formGeneral">
        @csrf

        <div class="card mb-3">
            <div class="card-header">Общие параметры</div>
            <div class="card-body">
                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Формат даты</label>
                        <select class="form-select" name="date_format">
                            @foreach($dateFormats as $fmt => $example)
                            <option value="{{ $fmt }}" {{ $settings['date_format'] === $fmt ? 'selected' : '' }}>
                                {{ $example }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Формат времени</label>
                        <select class="form-select" name="time_format">
                            @foreach($timeFormats as $fmt => $example)
                            <option value="{{ $fmt }}" {{ $settings['time_format'] === $fmt ? 'selected' : '' }}>
                                {{ $example }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Часовой пояс</label>
                        <select class="form-select" name="timezone">
                            @foreach($timezones as $group => $tzList)
                            <optgroup label="{{ $group }}">
                                @foreach($tzList as $tz => $tzLabel)
                                <option value="{{ $tz }}" {{ $settings['timezone'] === $tz ? 'selected' : '' }}>{{ $tzLabel }}</option>
                                @endforeach
                            </optgroup>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Страница 404 <span class="text-secondary">(ID документа)</span></label>
                        <input type="number" class="form-control" name="page_404_id" min="1"
                               value="{{ $settings['page_404_id'] }}"
                               placeholder="ID документа">
                    </div>
                </div>

                <div class="mb-0">
                    <label class="form-label">Сообщение об ошибке 403 <span class="text-secondary">(HTML)</span></label>
                    <textarea class="form-control font-monospace ztr-info-text" name="message_403" rows="3">{{ $settings['message_403'] }}</textarea>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">Хлебные крошки</div>
            <div class="card-body">
                <div class="d-flex flex-wrap gap-4 mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="breadcrumbsShowHome"
                               name="breadcrumbs_show_home" value="1"
                               {{ $settings['breadcrumbs_show_home'] === '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="breadcrumbsShowHome">Показывать «Главная»</label>
                    </div>
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="breadcrumbsLastLink"
                               name="breadcrumbs_last_link" value="1"
                               {{ $settings['breadcrumbs_last_link'] === '1' ? 'checked' : '' }}>
                        <label class="form-check-label" for="breadcrumbsLastLink">Последний элемент как ссылка</label>
                    </div>
                </div>
                <div>
                    <label class="form-label">Разделитель</label>
                    <input type="text" class="form-control ztr-breadcrumb-sep" name="breadcrumbs_separator"
                           value="{{ $settings['breadcrumbs_separator'] }}" maxlength="50" placeholder="/">
                </div>
            </div>
        </div>

        @if($canEdit['general'])
        <button type="submit" class="btn btn-sm btn-primary" id="btnSaveGeneral">
            <i class="bi bi-floppy me-1"></i>Сохранить
        </button>
        @endif
    </form>
</div>
@endif

@if($canView['env'])
<div class="tab-pane fade {{ $defaultTab === 'env' ? 'show active' : '' }}" id="tabEnv">

    @if(!$canEdit['env'])
    <div class="alert alert-danger py-2 mb-3 ztr-readonly-banner">
        <i class="bi bi-eye me-1"></i>Режим только просмотр - у вас нет прав на сохранение этого таба. Кнопки действий (тест почты, optimize, уведомления) также будут отклонены сервером.
    </div>
    @endif

    <div class="card mb-3">
        <div class="card-header">Системная информация</div>
        <div class="card-body">
            <div class="d-flex gap-4 flex-wrap ztr-sys-info">
                <span class="ztr-sys-item">PHP&nbsp;<code>{{ $sysInfo['php'] }}</code></span>
                <span class="ztr-sys-item">Laravel&nbsp;<code>{{ $sysInfo['laravel'] }}</code></span>
                <span class="ztr-sys-item">PostgreSQL&nbsp;<code>{{ preg_replace('/^PostgreSQL\s*/i', '', $sysInfo['db']) }}</code></span>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span>Пакеты Composer</span>
            <div class="d-flex align-items-center gap-2" id="composerHeaderArea">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnCheckPackages">
                    <i class="bi bi-box-seam me-1"></i>Проверить пакеты
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="env-alert env-alert-info mb-3 ztr-info-text">
                <i class="bi bi-info-circle-fill me-2"></i>
                Версии всех пакетов зафиксированы в <code>composer.lock</code>. Обновления <strong>не применяются автоматически</strong> - это намеренно, чтобы исключить неожиданные поломки. Здесь можно посмотреть, какие пакеты устарели, и получить команды для ручного обновления.
            </div>
            <div id="composerResults"></div>
        </div>
    </div>

    <form id="formEnv">
    @csrf

    @php
    $ev = $envValues;
    function envInput(string $key, array $meta, string $val): void {} // just for readability
    @endphp

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-gear ztr-card-icon"></i>Приложение
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label env-label">Название приложения <code>APP_NAME</code></label>
                    <input type="text" class="form-control form-control-sm" name="APP_NAME" value="{{ $ev['APP_NAME'] ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label env-label">URL сайта <code>APP_URL</code></label>
                    <input type="url" class="form-control form-control-sm" name="APP_URL" value="{{ $ev['APP_URL'] ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label env-label">Окружение <code>APP_ENV</code></label>
                    <select class="form-select form-select-sm" name="APP_ENV" id="envAppEnv">
                        <option value="production" {{ ($ev['APP_ENV'] ?? '') === 'production' ? 'selected' : '' }}>Продакшен</option>
                        <option value="local"      {{ ($ev['APP_ENV'] ?? 'local') === 'local'  ? 'selected' : '' }}>Разработка</option>
                    </select>
                </div>
                <div class="col-md-4" id="debugBlock">
                    <label class="form-label env-label">Режим отладки <code>APP_DEBUG</code></label>
                    <div class="form-check form-switch mt-1">
                        <input class="form-check-input" type="checkbox" name="APP_DEBUG" id="envAppDebug" value="1"
                               {{ in_array($ev['APP_DEBUG'] ?? '', ['true','1']) ? 'checked' : '' }}>
                        <label class="form-check-label ztr-debug-label" id="envAppDebugLabel">
                            {{ in_array($ev['APP_DEBUG'] ?? '', ['true','1']) ? 'Включён' : 'Выключен' }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-lightning-charge ztr-card-icon"></i>Оптимизация
        </div>
        <div class="card-body">
            <div class="env-alert env-alert-info mb-3 ztr-info-text">
                <i class="bi bi-info-circle-fill me-2"></i>
                Кеширование объединяет файлы конфигураций, файлы маршрутов и Blade-шаблоны в отдельные скомпилированные файлы - PHP не читает десятки файлов при каждом запросе, а загружает один.
                <strong>После любых изменений внесённых в файл <code>.env</code> или иные файлы конфигураций необходимо выполнить перекеширование</strong> - без него изменения не вступят в силу.
            </div>
            <div class="d-flex align-items-center gap-3 flex-wrap">
                <div id="cacheStatusBadges" class="d-flex gap-2 align-items-center">
                    <span class="spinner-border spinner-border-sm text-secondary"></span>
                </div>
                <div id="cacheActionButtons" class="d-flex gap-2"></div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-hdd-stack ztr-card-icon"></i>Сессии и кэш
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label env-label">Время сессии (мин) <code>SESSION_LIFETIME</code></label>
                    <input type="number" class="form-control form-control-sm" name="SESSION_LIFETIME"
                           value="{{ $ev['SESSION_LIFETIME'] ?? '120' }}" min="1">
                </div>
                <div class="col-md-4">
                    <label class="form-label env-label">Хранилище сессий <code>SESSION_DRIVER</code></label>
                    <select class="form-select form-select-sm" name="SESSION_DRIVER" id="envSessionDriver">
                        @foreach(['file' => 'Файлы на сервере', 'database' => 'База данных', 'redis' => 'Redis', 'cookie' => 'Cookie браузера'] as $val => $label)
                        <option value="{{ $val }}" {{ ($ev['SESSION_DRIVER'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label env-label">Хранилище кэша <code>CACHE_STORE</code></label>
                    <select class="form-select form-select-sm" name="CACHE_STORE" id="envCacheStore">
                        @foreach(['file' => 'Файлы на сервере', 'database' => 'База данных', 'redis' => 'Redis', 'array' => 'Память (только для тестов)'] as $val => $label)
                        <option value="{{ $val }}" {{ ($ev['CACHE_STORE'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div id="sessionHint" class="mt-3"></div>
            <div id="cacheHint" class="mt-2"></div>

            <div id="envCardRedis" class="ztr-redis-block">
                <hr class="ztr-redis-hr">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <i class="bi bi-hdd-network ztr-card-icon-redis"></i>
                    <strong class="ztr-redis-title">Подключение Redis</strong>
                    <span class="badge ms-1 ztr-redis-badge">требуется</span>
                </div>
                <div class="env-alert env-alert-info mb-3" id="redisInfoBox">
                    <i class="bi bi-info-circle me-2"></i>
                    Redis должен быть установлен и запущен на сервере. Для PHP требуется одно из:<br>
                    <span class="mt-1 d-inline-block">
                        расширение <code>phpredis</code> <span id="redisStatusPhpredis" class="ztr-redis-status-text">…</span>
                        &nbsp;или&nbsp;
                        пакет <code>predis/predis</code> <span id="redisStatusPredis" class="ztr-redis-status-text">…</span>
                    </span>
                </div>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label env-label">Клиент <code>REDIS_CLIENT</code></label>
                        <select class="form-select form-select-sm" name="REDIS_CLIENT">
                            @foreach(['phpredis','predis'] as $opt)
                            <option value="{{ $opt }}" {{ ($ev['REDIS_CLIENT'] ?? 'phpredis') === $opt ? 'selected' : '' }}>{{ $opt }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label env-label">Хост <code>REDIS_HOST</code></label>
                        <input type="text" class="form-control form-control-sm" name="REDIS_HOST"
                               value="{{ $ev['REDIS_HOST'] ?? '127.0.0.1' }}" placeholder="127.0.0.1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label env-label">Порт <code>REDIS_PORT</code></label>
                        <input type="number" class="form-control form-control-sm" name="REDIS_PORT"
                               value="{{ $ev['REDIS_PORT'] ?? '6379' }}" placeholder="6379">
                    </div>
                    <div class="col-md-5">
                        <label class="form-label env-label">Пароль <code>REDIS_PASSWORD</code></label>
                        <input type="password" class="form-control form-control-sm" name="REDIS_PASSWORD"
                               value="{{ ($ev['REDIS_PASSWORD'] ?? '') && ($ev['REDIS_PASSWORD'] ?? '') !== 'null' ? '••••••••' : '' }}"
                               placeholder="Оставьте пустым если без пароля" autocomplete="new-password">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-journal-text ztr-card-icon"></i>Логи
        </div>
        <div class="card-body">
            <div class="d-flex align-items-end gap-3 flex-wrap">
                <div>
                    <label class="form-label env-label mb-1 ztr-log-level-label">
                        Уровень логирования <code>LOG_LEVEL</code>
                        <button type="button" class="btn p-0 border-0 ms-1 align-middle ztr-log-info-btn"
                                id="logLevelInfoBtn"
                                tabindex="-1">
                            <i class="bi bi-question-circle ztr-log-info-icon"></i>
                        </button>
                    </label>
                    <select class="form-select form-select-sm ztr-log-select" name="LOG_LEVEL" id="envLogLevel">
                        @foreach([
                            'debug'     => 'Отладка (debug)',
                            'info'      => 'Информация (info)',
                            'notice'    => 'Уведомление (notice)',
                            'warning'   => 'Предупреждение (warning)',
                            'error'     => 'Ошибка (error)',
                            'critical'  => 'Критическая (critical)',
                            'alert'     => 'Тревога (alert)',
                            'emergency' => 'Чрезвычайная (emergency)',
                        ] as $val => $label)
                        <option value="{{ $val }}" {{ ($ev['LOG_LEVEL'] ?? '') === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div id="alertLogDebug" class="env-alert env-alert-warning mt-3 ztr-redis-block">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <strong>Рекомендация:</strong> уровень <code>debug</code> в production-окружении создаёт огромные лог-файлы и снижает производительность. Используйте <code>error</code> или <code>warning</code>.
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-bell ztr-card-icon"></i>Уведомления об ошибках
        </div>
        <div class="card-body">
            <p class="text-secondary mb-3 ztr-notify-desc">
                При записи в лог события указанного уровня или выше - система отправит уведомление на почту и/или в Telegram.
            </p>

            <div class="mb-3 ztr-notify-level-wrap">
                <label class="form-label env-label">Минимальный уровень для уведомлений</label>
                <select class="form-select form-select-sm" id="notifyLevel">
                    @foreach([
                        'debug'     => 'Отладка (debug)',
                        'info'      => 'Информация (info)',
                        'notice'    => 'Уведомление (notice)',
                        'warning'   => 'Предупреждение (warning)',
                        'error'     => 'Ошибка (error)',
                        'critical'  => 'Критическая (critical)',
                        'alert'     => 'Тревога (alert)',
                        'emergency' => 'Чрезвычайная (emergency)',
                    ] as $val => $label)
                    <option value="{{ $val }}" {{ $logNotify['level'] === $val ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            
            <div class="mb-3">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="notifyEmailEnabled"
                           {{ $logNotify['email_enabled'] === '1' ? 'checked' : '' }}>
                    <label class="form-check-label ztr-notify-label" for="notifyEmailEnabled">
                        <i class="bi bi-envelope me-1"></i>Отправлять на почту
                    </label>
                </div>
                <div id="notifyEmailFields" class="ztr-notify-fields ztr-notify-email-fields"
                     style="display: {{ $logNotify['email_enabled'] === '1' ? 'block' : 'none' }}">
                    <input type="email" class="form-control form-control-sm ztr-notify-email-input" id="notifyEmail"
                           value="{{ $logNotify['email'] }}" placeholder="info@yoursite.ru">
                    <div class="form-text">Адрес, на который будут приходить письма об ошибках</div>
                </div>
            </div>

            
            <div class="mb-4">
                <div class="form-check form-switch mb-2">
                    <input class="form-check-input" type="checkbox" id="notifyTelegramEnabled"
                           {{ $logNotify['telegram_enabled'] === '1' ? 'checked' : '' }}>
                    <label class="form-check-label ztr-notify-label" for="notifyTelegramEnabled">
                        <i class="bi bi-telegram me-1"></i>Отправлять в Telegram
                    </label>
                </div>
                <div id="notifyTelegramFields" class="ztr-notify-fields ztr-notify-telegram-fields"
                     style="display: {{ $logNotify['telegram_enabled'] === '1' ? 'block' : 'none' }}">
                    <div class="row g-2 ztr-notify-telegram-row">
                        <div class="col-md-7">
                            <label class="form-label env-label mb-1">Bot Token</label>
                            <input type="text" class="form-control form-control-sm" id="notifyTelegramToken"
                                   value="{{ $logNotify['telegram_token'] }}" placeholder="123456789:ABCdefGHI...">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label env-label mb-1">Chat ID</label>
                            <input type="text" class="form-control form-control-sm" id="notifyTelegramChatId"
                                   value="{{ $logNotify['telegram_chat_id'] }}" placeholder="-100123456789">
                        </div>
                    </div>
                    <div class="form-text">Создайте бота через <a href="https://t.me/BotFather" target="_blank" class="ztr-notify-link">@BotFather</a> и добавьте его в канал/группу. Chat ID можно узнать через <a href="https://t.me/userinfobot" target="_blank" class="ztr-notify-link">@userinfobot</a></div>
                </div>
            </div>

            <div class="d-flex gap-2 flex-wrap">
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveLogNotify">
                    <i class="bi bi-floppy me-1"></i>Сохранить уведомления
                </button>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnTestLogNotify">
                    <i class="bi bi-send me-1"></i>Отправить тестовое уведомление
                </button>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header d-flex align-items-center gap-2">
            <i class="bi bi-envelope ztr-card-icon"></i>Почта
        </div>
        <div class="card-body">
            <div class="row g-3 mb-3">
                <div class="col-md-4">
                    <label class="form-label env-label">Транспорт <code>MAIL_MAILER</code></label>
                    <select class="form-select form-select-sm" name="MAIL_MAILER" id="envMailMailer">
                        <option value="smtp" {{ ($ev['MAIL_MAILER'] ?? '') === 'smtp' ? 'selected' : '' }}>SMTP</option>
                        <option value="log"  {{ ($ev['MAIL_MAILER'] ?? '') === 'log'  ? 'selected' : '' }}>Только в лог</option>
                    </select>
                </div>
            </div>

            <div id="mailHint" class="mb-3"></div>

            <div id="smtpFields">
                <div class="row g-3 mb-3">
                    <div class="col-md-8">
                        <label class="form-label env-label">SMTP хост <code>MAIL_HOST</code></label>
                        <input type="text" class="form-control form-control-sm" name="MAIL_HOST"
                               value="{{ $ev['MAIL_HOST'] ?? '' }}" placeholder="smtp.example.com">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label env-label">Порт <code>MAIL_PORT</code></label>
                        <input type="number" class="form-control form-control-sm" name="MAIL_PORT"
                               value="{{ $ev['MAIL_PORT'] ?? '587' }}" placeholder="587">
                        <div class="form-text ztr-smtp-desc"><strong>587</strong> - STARTTLS (рекомендуется), <strong>465</strong> - SSL</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label env-label">Логин <code>MAIL_USERNAME</code></label>
                        <input type="text" class="form-control form-control-sm" name="MAIL_USERNAME"
                               value="{{ ($ev['MAIL_USERNAME'] ?? '') === 'null' ? '' : ($ev['MAIL_USERNAME'] ?? '') }}"
                               autocomplete="off">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label env-label">Пароль <code>MAIL_PASSWORD</code></label>
                        <input type="password" class="form-control form-control-sm" name="MAIL_PASSWORD"
                               value="{{ ($ev['MAIL_PASSWORD'] ?? '') && ($ev['MAIL_PASSWORD'] ?? '') !== 'null' ? '••••••••' : '' }}"
                               placeholder="Оставьте пустым, чтобы не менять" autocomplete="new-password">
                    </div>
                </div>
            </div>

            <div class="row g-3 mb-3">
                <div class="col-md-6">
                    <label class="form-label env-label">Email отправителя <code>MAIL_FROM_ADDRESS</code></label>
                    <input type="email" class="form-control form-control-sm" name="MAIL_FROM_ADDRESS"
                           value="{{ str_replace('"', '', $ev['MAIL_FROM_ADDRESS'] ?? '') }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label env-label">Имя отправителя <code>MAIL_FROM_NAME</code></label>
                    <input type="text" class="form-control form-control-sm" name="MAIL_FROM_NAME"
                           value="{{ str_replace(['"', '${APP_NAME}'], ['', config('app.name')], $ev['MAIL_FROM_NAME'] ?? '') }}">
                </div>
            </div>

            <hr class="ztr-redis-hr">

            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="bi bi-send ztr-card-icon"></i>
                <strong class="ztr-test-mail-title">Отправить тестовое письмо</strong>
            </div>
            <p class="text-secondary mb-3 ztr-info-text">
                Проверка с <strong>текущими значениями из формы</strong> (включая несохранённые). Если пароль не менялся - берётся сохранённый из <code>.env</code>.
                Рекомендуем сначала отправить тест, затем сохранить окружение.
            </p>
            <div class="d-flex gap-2 flex-wrap ztr-test-mail-row">
                <input type="email" class="form-control form-control-sm ztr-test-mail-input" id="testEmailInput"
                       placeholder="Куда отправить тестовое письмо">
                <button type="button" class="btn btn-secondary btn-sm text-nowrap" id="btnSendTest">
                    <i class="bi bi-send me-1"></i>Отправить тест
                </button>
            </div>
        </div>
    </div>

    @if($canEdit['env'])
    <button type="submit" class="btn btn-sm btn-primary mb-4" id="btnSaveEnv">
        <i class="bi bi-floppy me-1"></i>Сохранить изменения
    </button>
    @endif
    </form>
</div>
@endif

@if($canView['seo'])
<div class="tab-pane fade {{ $defaultTab === 'seo' ? 'show active' : '' }}" id="tabSeo">
    @if(!$canEdit['seo'])
    <div class="alert alert-danger py-2 mb-3 ztr-readonly-banner">
        <i class="bi bi-eye me-1"></i>Режим только просмотр - у вас нет прав на сохранение этого таба
    </div>
    @endif
    <form id="formSeo">
        @csrf

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-share ztr-card-icon"></i>
                Open Graph (социальные сети)
            </div>
            <div class="card-body">
                <div class="mb-0">
                    <label class="form-label">Изображение по умолчанию <small class="text-muted">(og:image)</small></label>
                    <input type="text" class="form-control" name="og_default_image"
                        value="{{ $settings['og_default_image'] ?? '' }}"
                        placeholder="https://example.com/og-image.jpg">
                    <div class="form-text">URL картинки-превью для соцсетей и мессенджеров, если у документа не задан собственный og:image. Рекомендуемый размер: 1200×630 пикселей.</div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-link-45deg ztr-card-icon"></i>
                Адреса страниц
            </div>
            <div class="card-body">
                <div class="mb-0 ztr-suffix-wrap">
                    <label class="form-label">Суффикс URL документов</label>
                    <select name="url_suffix" class="form-select">
                        <option value="" @selected(($settings['url_suffix'] ?? '') === '')>Без суффикса</option>
                        <option value=".htm" @selected(($settings['url_suffix'] ?? '') === '.htm')>.htm</option>
                        <option value=".html" @selected(($settings['url_suffix'] ?? '') === '.html')>.html</option>
                    </select>
                    <div class="form-text">Пример: <code>/news/novost<span id="suffixPreview">{{ $settings['url_suffix'] ?? '' }}</span></code></div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-bar-chart-line ztr-card-icon"></i>
                Счётчики аналитики
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Google Analytics (GA4)</label>
                        <input type="text" class="form-control" name="analytics_google"
                               value="{{ $settings['analytics_google'] }}"
                               placeholder="G-XXXXXXXXXX">
                        <div class="form-text">Измерительный идентификатор GA4</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Яндекс.Метрика</label>
                        <input type="text" class="form-control" name="analytics_yandex"
                               value="{{ $settings['analytics_yandex'] }}"
                               placeholder="12345678"
                               inputmode="numeric"
                               pattern="\d+">
                        <div class="form-text">Номер счётчика - только цифры</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-code-slash ztr-card-icon"></i>
                Произвольный подключаемый код
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Код в <code>&lt;head&gt;</code></label>
                    <textarea class="form-control font-monospace ztr-code-area" name="head_code" rows="5"
                              placeholder="<!-- CSS, мета-теги, шрифты, скрипты в head -->">{{ $settings['head_code'] }}</textarea>
                    <div class="form-text">Вставляется там, где указан тег <code>[head:code]</code> в макете. Если тег не указан - перед закрывающим <code>&lt;/head&gt;</code></div>
                </div>
                <div class="mb-0">
                    <label class="form-label">Код перед <code>&lt;/body&gt;</code></label>
                    <textarea class="form-control font-monospace ztr-code-area" name="body_code" rows="5"
                              placeholder="<!-- Чаты, пиксели, виджеты, скрипты в конце body -->">{{ $settings['body_code'] }}</textarea>
                    <div class="form-text">Вставляется там, где указан тег <code>[body:code]</code> в макете. Если тег не указан - перед закрывающим <code>&lt;/body&gt;</code></div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-robot ztr-card-icon"></i>
                Правила robots.txt
            </div>
            <div class="card-body">
                <textarea class="form-control font-monospace ztr-code-area" name="robots_txt" rows="8">{{ $robotsTxt }}</textarea>
                <div class="form-text">Файл <code>public/robots.txt</code> - инструкции для поисковых роботов</div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-diagram-3 ztr-card-icon"></i>
                    Управление Sitemap.xml
                </div>
                <div class="d-flex gap-1">
                    <a href="/sitemap.xml" target="_blank" class="btn btn-sm btn-outline-secondary"
                       data-bs-toggle="tooltip" data-bs-delay='{"show":600,"hide":100}'
                       title="Открыть sitemap.xml в новой вкладке">
                        <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                    @if($canEdit['seo'])
                    <button type="button" class="btn btn-sm btn-outline-warning" id="btnSitemapFlush"
                        data-url="{{ route('admin.settings.sitemap-flush') }}"
                        data-bs-toggle="tooltip" data-bs-delay='{"show":600,"hide":100}'
                        title="Очистить кеш sitemap">
                        <i class="bi bi-arrow-repeat"></i>
                    </button>
                    @endif
                </div>
            </div>
            <div class="card-body">
                <div class="form-text mb-3">
                    Sitemap.xml - это файл, содержащий карту ссылок на вашем сайте для поисковиков. По стандартам данный файл должен быть доступен по адресу <a href="/sitemap.xml" target="_blank"><code>/sitemap.xml</code></a>.
                    Глобальные параметры ниже определяют поведение по всему сайту;
                    конкретная рубрика или конкретный документ могут переопределять эти параметры 
                    через свои собственные поля (приоритет: документ → рубрика → глобальные параметры).
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="sitemap_enabled" id="sitemapEnabled"
                                value="1" @checked(($settings['sitemap_enabled'] ?? '1') === '1')>
                            <label class="form-check-label" for="sitemapEnabled">Sitemap включён</label>
                        </div>
                        <div class="form-text mb-3">Если выключено - <code>/sitemap.xml</code> возвращает 404.</div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="sitemap_include_homepage" id="sitemapIncludeHome"
                                value="1" @checked(($settings['sitemap_include_homepage'] ?? '1') === '1')>
                            <label class="form-check-label" for="sitemapIncludeHome">Включать главную страницу <code>/</code></label>
                        </div>
                        <div class="form-text mb-3">Главная всегда получает <code>priority=1.0</code>.</div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="sitemap_include_rubric_indexes" id="sitemapIncludeRubricIdx"
                                value="1" @checked(($settings['sitemap_include_rubric_indexes'] ?? '1') === '1')>
                            <label class="form-check-label" for="sitemapIncludeRubricIdx">Включать индексные страницы рубрик</label>
                        </div>
                        <div class="form-text">
                            Это URL вида <code>/news</code> - страница рубрики «Новости» (документ с пустым алиасом в рубрике).
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Какую дату учитывать как <code>lastmod</code></label>
                        <select name="sitemap_lastmod_source" class="form-select mb-3">
                            @foreach([
                                'updated_at'   => 'updated_at - дата последнего изменения (рекомендуется)',
                                'created_at'   => 'created_at - дата создания документа',
                                'published_at' => 'published_at - дата публикации документа',
                            ] as $val => $label)
                            <option value="{{ $val }}" @selected(($settings['sitemap_lastmod_source'] ?? 'updated_at') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <label class="form-label">Кеш <small class="text-muted">(секунды)</small></label>
                        <input type="number" name="sitemap_cache_ttl" class="form-control mb-1"
                            value="{{ $settings['sitemap_cache_ttl'] ?? '3600' }}" min="0" max="86400">
                        <div class="form-text mb-3">
                            <code>0</code> = без кеша (пересобирать на каждый запрос). По умолчанию час.
                            При сохранении любого документа или рубрики кеш сбрасывается автоматически.
                        </div>

                        <label class="form-label">Дробить sitemap-index при превышении</label>
                        <input type="number" name="sitemap_max_urls_per_file" class="form-control mb-1"
                            value="{{ $settings['sitemap_max_urls_per_file'] ?? '50000' }}" min="100" max="50000">
                        <div class="form-text">
                            Лимит протокола sitemap.xml - 50000 URL на файл. Если документов больше —
                            <code>/sitemap.xml</code> отдаёт sitemap-index, а сами URL раскладываются по
                            <code>/sitemap-1.xml</code>, <code>/sitemap-2.xml</code>, …
                        </div>
                    </div>
                </div>

                <hr class="my-3">
                <div class="form-text mb-2 fw-semibold">Параметры по умолчанию для документов и рубрик</div>
                <div class="form-text mb-3">
                    Эти значения будут автоматически подставляться в документы и рубрики как значения по умолчанию.
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label">Частота обновлений по умолчанию</label>
                        <div class="form-text mb-1">Используется в sitemap.xml для документов, у которых не задана собственная частота.</div>
                        <select name="sitemap_default_changefreq" class="form-select">
                            @foreach([
                                'always'  => 'always - меняется при каждом обращении',
                                'hourly'  => 'hourly - каждый час',
                                'daily'   => 'daily - каждый день',
                                'weekly'  => 'weekly - каждую неделю (рекомендуется)',
                                'monthly' => 'monthly - каждый месяц',
                                'yearly'  => 'yearly - каждый год',
                                'never'   => 'never - архивные данные',
                            ] as $val => $label)
                            <option value="{{ $val }}" @selected(($settings['sitemap_default_changefreq'] ?? 'weekly') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Приоритет по умолчанию <small class="text-muted">(0.0–1.0)</small></label>
                        <input type="number" name="sitemap_default_priority" class="form-control"
                            value="{{ $settings['sitemap_default_priority'] ?? '0.5' }}"
                            min="0" max="1" step="0.1">
                        <div class="form-text">
                            Главная всегда 1.0 (не настраивается). Рекомендация: рубрики 0.7–0.9, документы 0.5.
                        </div>
                    </div>
                </div>

                <div class="ztr-sitemap-preview" id="sitemapPreview"
                    data-url="{{ route('admin.settings.sitemap-preview') }}">
                    <div class="d-flex align-items-center gap-2 ztr-sitemap-preview-toggle" role="button" data-bs-toggle="collapse" data-bs-target="#sitemapPreviewBody">
                        <i class="bi bi-eye"></i>
                        <span class="fw-semibold">Что попадёт в sitemap</span>
                        <span class="text-muted small ms-auto" id="sitemapPreviewSummary">Нажмите чтобы посчитать</span>
                        <i class="bi bi-chevron-down"></i>
                    </div>
                    <div class="collapse" id="sitemapPreviewBody">
                        <div class="ztr-sitemap-preview-body" id="sitemapPreviewContent">
                            <div class="text-center py-3 text-muted">
                                <span class="spinner-border spinner-border-sm me-2"></span>Загрузка…
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-rss ztr-card-icon"></i>
                    RSS-фиды
                </div>
                @if(($settings['rss_site_feed_enabled'] ?? '0') === '1')
                <a href="/feed.xml" target="_blank" class="btn btn-sm btn-outline-secondary" title="Открыть site-wide фид">
                    <i class="bi bi-box-arrow-up-right"></i>
                </a>
                @endif
            </div>
            <div class="card-body">
                <div class="form-text mb-3">
                    RSS-фиды для рубрик с включённым параметром RSS доступны по адресу <code>/{префикс рубрики}/feed.xml</code>.
                    Включается через кнопку с иконкой RSS в строке рубрики (раздел <a href="{{ route('admin.rubrics.index') }}">«Рубрики»</a>).
                    Глобальный фид <code>/feed.xml</code> объединяет последние документы из всех рубрик с включённым RSS - настраивается ниже.
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="rss_enabled" id="rssEnabled"
                                value="1" @checked(($settings['rss_enabled'] ?? '1') === '1')>
                            <label class="form-check-label" for="rssEnabled">Разрешить RSS</label>
                        </div>
                        <div class="form-text mb-3">Если выключено - абсолютно все RSS-фиды отключаются и возвращают 404.</div>

                        <label class="form-label">Кеш фидов <small class="text-muted">(секунды)</small></label>
                        <input type="number" name="rss_cache_ttl" class="form-control mb-1"
                            value="{{ $settings['rss_cache_ttl'] ?? '1800' }}" min="0" max="86400">
                        <div class="form-text mb-3">
                            <code>0</code> = без кеша. По умолчанию 30 минут.
                            При сохранении документа или рубрики кеш сбрасывается автоматически.
                        </div>

                        <label class="form-label">Количество символов для поля <code>&lt;description&gt;</code></label>
                        <input type="number" name="rss_description_max_length" class="form-control mb-1"
                            value="{{ $settings['rss_description_max_length'] ?? '500' }}" min="0" max="5000">
                        <div class="form-text">
                            Все HTML-теги вырезаются. Текст обрезается при достижении указанного количества символов и будет продолжен многоточием.
                            <code>0</code> = без ограничения количества символов.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Документов в фиде по умолчанию</label>
                        <input type="number" name="rss_default_limit" class="form-control mb-3"
                            value="{{ $settings['rss_default_limit'] ?? '20' }}" min="1" max="500">

                        <hr class="my-3">
                        <div class="form-text mb-2 fw-semibold">Общий фид сайта <code>/feed.xml</code></div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="rss_site_feed_enabled" id="rssSiteFeed"
                                value="1" @checked(($settings['rss_site_feed_enabled'] ?? '0') === '1')>
                            <label class="form-check-label" for="rssSiteFeed">Включить общий фид сайта</label>
                        </div>
                        <div class="form-text mb-3">
                            Объединяет последние документы из всех рубрик с включённым RSS (отсортированные по дате).
                            Доступен по адресу <code>/feed.xml</code> когда включён.
                        </div>

                        <label class="form-label">Название общего фида сайта</label>
                        <input type="text" name="rss_site_feed_title" class="form-control mb-2"
                            value="{{ $settings['rss_site_feed_title'] ?? '' }}"
                            placeholder="По умолчанию - название сайта" maxlength="255">

                        <label class="form-label">Описание общего фида сайта</label>
                        <textarea name="rss_site_feed_description" class="form-control mb-2" rows="2"
                            maxlength="1000">{{ $settings['rss_site_feed_description'] ?? '' }}</textarea>

                        <label class="form-label">Документов в общем фиде</label>
                        <input type="number" name="rss_site_feed_limit" class="form-control"
                            value="{{ $settings['rss_site_feed_limit'] ?? '50' }}" min="1" max="500">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-braces ztr-card-icon"></i>
                JSON API
                <a href="{{ route('admin.api-tokens.index') }}" class="btn btn-sm btn-outline-success ms-auto" title="К списку API-токенов">
                    <i class="bi bi-key"></i> API-токены
                </a>
            </div>
            <div class="card-body">
                <div class="form-text mb-3">
                    JSON API позволяет внешним клиентам получать структурированные данные документов в формате JSON.
                    Доступ возможен по токену, переданному в заголовке <code>X-API-Key</code>. Только рубрики и поля с явно включённой опцией «API»
                    отдаются наружу.
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="api_enabled" id="apiEnabled"
                                value="1" @checked(($settings['api_enabled'] ?? '0') === '1')>
                            <label class="form-check-label" for="apiEnabled">Разрешить JSON API</label>
                        </div>
                        <div class="form-text mb-3">Если выключено - на все запросы будет возвращаться ответ с кодом <code>503</code>.</div>

                        <label class="form-label">URL-префикс</label>
                        <input type="text" name="api_url_prefix" class="form-control mb-1"
                            value="{{ $settings['api_url_prefix'] ?? '/api/v1' }}" placeholder="/api/v1" maxlength="50">
                        <div class="form-text mb-3">
                            По умолчанию <code>/api/v1</code>. Изменяйте только если понимаете, что делаете -
                            смена префикса может заблокировать работу всех подключенных клиентов.
                        </div>

                        <label class="form-label">Поддомен API (опционально)</label>
                        <input type="text" name="api_domain" class="form-control mb-1"
                            value="{{ $settings['api_domain'] ?? '' }}" placeholder="api.example.com" maxlength="255">
                        <div class="form-text">
                            Если задан - API будет доступен только с этого поддомена. <strong>URL-префикс выше продолжает действовать</strong>: это значит, что финальный адрес API будет <code>https://api.example.com/api/v1/...</code>. 
                            Чтобы API работал на корне поддомена - просто очистите поле префикса.
                            Если поддомен не задан - API работает на основном домене по URL-префиксу.
                            Кеш маршрутов сбрасывается автоматически при сохранении настроек.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Кеш ответов (TTL в секундах)</label>
                        <input type="number" name="api_cache_ttl" class="form-control mb-1"
                            value="{{ $settings['api_cache_ttl'] ?? '300' }}" min="0" max="86400">
                        <div class="form-text mb-3">
                            Сколько секунд держать в кеше JSON-ответы. <code>0</code> - без кеша (генерация на каждый запрос).
                            При сохранении документа/рубрики кеш сбрасывается автоматически.
                        </div>

                        <label class="form-label">Размер страницы по умолчанию</label>
                        <input type="number" name="api_default_per_page" class="form-control mb-1"
                            value="{{ $settings['api_default_per_page'] ?? '20' }}" min="1" max="1000">
                        <div class="form-text mb-3">
                            Сколько документов отдавать на страницу, если клиент не передал параметр <code>per_page</code>. Можно переопределить для каждой конкретной рубрики.
                        </div>

                        <label class="form-label">Максимум на страницу</label>
                        <input type="number" name="api_max_per_page" class="form-control mb-1"
                            value="{{ $settings['api_max_per_page'] ?? '100' }}" min="1" max="1000">
                        <div class="form-text mb-3">
                            Максимальный лимит: клиент не сможет получить больше, если передаст параметр <code>per_page</code> превышающий это значение (защита от нагрузки).
                        </div>

                        <label class="form-label">Лимит запросов в минуту по умолчанию</label>
                        <input type="number" name="api_default_rate_limit" class="form-control mb-1"
                            value="{{ $settings['api_default_rate_limit'] ?? '60' }}" min="0" max="100000">
                        <div class="form-text">
                            Каждому токену можно задать лимит индивидуально. Это значение будет использовано по умолчанию.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex align-items-center gap-2">
                <i class="bi bi-signpost-split ztr-card-icon"></i>
                Редиректы
            </div>
            <div class="card-body">
                <div class="form-text mb-3">
                    Настройки глобальных параметров. Сами редиректы создаются в отдельном разделе
                    <a href="{{ route('admin.redirects.index') }}">Редиректы</a>.
                </div>

                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="redirects_enabled" id="redirectsEnabled"
                                value="1" @checked(($settings['redirects_enabled'] ?? '1') === '1')>
                            <label class="form-check-label" for="redirectsEnabled">
                                Разрешить Редиректы
                            </label>
                        </div>
                        <div class="form-text mb-3">
                            Если выключено - все запросы продолжают работать без учета проверки таблицы редиректов.
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="redirects_use_alias_history" id="redirectsUseAlias"
                                value="1" @checked(($settings['redirects_use_alias_history'] ?? '1') === '1')>
                            <label class="form-check-label" for="redirectsUseAlias">
                                Авто-редиректы при смене алиасов документов и рубрик
                            </label>
                        </div>
                        <div class="form-text mb-3">
                            При смене URL документа или рубрики старый URL автоматически отдаёт <code>301</code> на новый.
                            Это работает через служебную историю алиасов - отдельно от таблицы редиректов; вручную создавать ничего не нужно.
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="redirects_track_hits" id="redirectsTrackHits"
                                value="1" @checked(($settings['redirects_track_hits'] ?? '1') === '1')>
                            <label class="form-check-label" for="redirectsTrackHits">
                                Считать срабатывания редиректов
                            </label>
                        </div>
                        <div class="form-text mb-3">
                            Каждое срабатывание увеличивает счётчик и обновляет дату.
                            Полезно для аналитики и выявления неэффективных или мёртвых редиректов.
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="redirects_log_misses" id="redirectsLogMisses"
                                value="1" @checked(($settings['redirects_log_misses'] ?? '0') === '1')>
                            <label class="form-check-label" for="redirectsLogMisses">
                                Логировать битые ссылки (404)
                            </label>
                        </div>
                        <div class="form-text">
                            Все 404-запросы записываются в журнал «<a href="{{ route('admin.redirects.misses') }}">Битые ссылки</a>»
                            с группировкой по URL и счётчиком попаданий. По любой записи можно одним кликом создать редирект.
                            Это <strong>не дубликат</strong> вкладки 404 в разделе «<a href="{{ route('admin.logs.index') }}?tab=404">События</a>»: там - диагностика
                            (IP, user-agent, referer, по одной записи на IP в сутки), а здесь сведения о редиректах с приоритизацией по числу попаданий.
                            Выключите, если база данных разрастается от ботов.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Тип нового редиректа по умолчанию</label>
                        <select name="redirects_default_type" class="form-select mb-3">
                            @foreach([
                                301 => '301 Moved Permanently (постоянный) - рекомендуется для SEO',
                                302 => '302 Found (временный)',
                            ] as $val => $label)
                            <option value="{{ $val }}" @selected((string)($settings['redirects_default_type'] ?? '301') === (string)$val)>{{ $label }}</option>
                            @endforeach
                        </select>

                        <label class="form-label">Максимальная длина цепочки</label>
                        <input type="number" name="redirects_max_hops" class="form-control mb-2"
                            value="{{ $settings['redirects_max_hops'] ?? '10' }}" min="1" max="50">
                        <div class="form-text">
                            Защита от циклов: если цепочка редиректов (A→B→C→…) превысит это число - система остановится
                            на текущем шаге и запишет предупреждение в лог. Разумное значение: от 5 до 10 переходов.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($canEdit['seo'])
        <button type="submit" class="btn btn-sm btn-primary" id="btnSaveSeo">
            <i class="bi bi-floppy me-1"></i>Сохранить
        </button>
        @endif
    </form>
</div>
@endif

@if($canView['cache'])
<div class="tab-pane fade {{ $defaultTab === 'cache' ? 'show active' : '' }}" id="tabCache">

    @if(!$canEdit['cache'])
    <div class="alert alert-danger py-2 mb-3 ztr-readonly-banner">
        <i class="bi bi-eye me-1"></i>Режим только просмотр - у вас нет прав на очистку кэша (требуется право «Очистка кеша»)
    </div>
    @endif

    <div class="row g-3 mb-4" id="cacheCards">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center py-3">
                    <div class="text-secondary mb-1 ztr-cache-card-label">Кэш приложения</div>
                    <div class="fw-semibold" id="cacheAppValue">
                        <span class="spinner-border spinner-border-sm text-secondary"></span>
                    </div>
                    <div class="text-secondary ztr-cache-card-value" id="cacheAppLabel"></div>
                    <div class="text-secondary ztr-cache-card-hint" id="cacheAppHint"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center py-3">
                    <div class="text-secondary mb-1 ztr-cache-card-label">Кэш шаблонов</div>
                    <div class="fw-semibold" id="cacheViewsSize">
                        <span class="spinner-border spinner-border-sm text-secondary"></span>
                    </div>
                    <div class="text-secondary ztr-cache-card-value">Файлы</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center py-3">
                    <div class="text-secondary mb-1 ztr-cache-card-label">Сессии</div>
                    <div class="fw-semibold" id="cacheSessionsValue">
                        <span class="spinner-border spinner-border-sm text-secondary"></span>
                    </div>
                    <div class="text-secondary ztr-cache-card-value" id="cacheSessionsLabel"></div>
                    <div class="text-secondary ztr-cache-card-hint" id="cacheSessionsHint"></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center py-3">
                    <div class="text-secondary mb-1 ztr-cache-card-label">Конфиг / Роуты</div>
                    <div id="cacheConfigStatus">
                        <span class="spinner-border spinner-border-sm text-secondary"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if($canEdit['cache'])
    <div class="card mb-4">
        <div class="card-header">Управление кэшем</div>
        <div class="card-body p-0">
            <table class="table table-hover mb-0 ztr-cache-table" id="cacheClearTable">
                <thead>
                    <tr>
                        <th class="ztr-cache-table-type">Тип</th>
                        <th>Описание</th>
                        <th class="ztr-cache-table-action"></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><i class="bi bi-lightning text-secondary me-2"></i>Кэш приложения</td>
                        <td class="text-secondary">Временно сохранённые данные: списки документов, выборки, sitemap, RSS. После очистки система пересчитает их при первом обращении.</td>
                        <td><button class="btn btn-sm btn-outline-danger w-100 btn-clear-cache" data-type="framework">Очистить</button></td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-file-code text-secondary me-2"></i>Скомпилированные шаблоны</td>
                        <td class="text-secondary">Готовые версии шаблонов сайта и панели управления. Очистите, если после правки макета или блока на странице остались старые элементы.</td>
                        <td><button class="btn btn-sm btn-outline-danger w-100 btn-clear-cache" data-type="views">Очистить</button></td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-gear text-secondary me-2"></i>Кэш конфигурации</td>
                        <td class="text-secondary">Объединённые системные параметры для ускорения старта. Сбрасывайте после изменения окружения или при обновлении системы.</td>
                        <td><button class="btn btn-sm btn-outline-danger w-100 btn-clear-cache" data-type="config">Очистить</button></td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-signpost text-secondary me-2"></i>Кэш маршрутов</td>
                        <td class="text-secondary">Ускоренный список адресов сайта и панели управления. Сбрасывайте после установки или удаления модуля, чтобы система увидела новые адреса.</td>
                        <td><button class="btn btn-sm btn-outline-danger w-100 btn-clear-cache" data-type="routes">Очистить</button></td>
                    </tr>
                    <tr>
                        <td><i class="bi bi-person-badge text-secondary me-2"></i>Сессии пользователей</td>
                        <td class="text-secondary">Принудительно разлогинит всех пользователей панели управления. Используйте, когда нужно срочно завершить чужие сессии.</td>
                        <td>
                            <span class="d-inline-block w-100" data-sessions-wrap tabindex="0">
                                <button class="btn btn-sm btn-outline-danger w-100" id="btnClearSessions">Очистить</button>
                            </span>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <button class="btn btn-sm btn-danger" id="btnClearAll">
        <i class="bi bi-trash me-1"></i>Очистить весь кэш
    </button>
    @endif

    @if($canEdit['cache'])
    <hr class="my-4">
    <h5 class="mb-1"><i class="bi bi-globe me-2"></i>Кэш публичных страниц</h5>
    <div class="form-text mb-3">
        Middleware-уровень HTTP-кеш для публичных страниц сайта. Хранит готовый HTML
        ответ по ключу URL и отдаёт его последующим запросам без перерендера.
        Не путать с кешем выборок документов (<code>requests.cache_time</code>) - это разные слои.
    </div>

    <form id="formPublicCache" class="ztr-public-cache-form">
        @csrf

        <div class="card mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
                <span><i class="bi bi-toggles me-2"></i>Основные параметры</span>
                <button type="button" class="btn btn-sm btn-outline-warning" id="btnFlushPublicCache"
                    data-url="{{ route('admin.settings.public-cache-flush') }}">
                    <i class="bi bi-arrow-repeat me-1"></i>Очистить кэш публичных страниц
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="public_cache_enabled" id="publicCacheEnabled"
                                value="1" @checked(($settings['public_cache_enabled'] ?? '0') === '1')>
                            <label class="form-check-label" for="publicCacheEnabled">Кэш публичных страниц</label>
                        </div>
                        <div class="form-text mb-3">
                            По умолчанию <strong>выключен</strong>. На свежеустановленной системе включайте только тогда, когда сайт настроен и наполнен контентом - иначе посетители сайта могут увидеть страницы из кеша, которые ещё не готовы к финальной публикации.
                        </div>

                        <label class="form-label">TTL по умолчанию <small class="text-muted">(секунды)</small></label>
                        <input type="number" name="public_cache_default_ttl" class="form-control mb-1"
                            value="{{ $settings['public_cache_default_ttl'] ?? '3600' }}" min="0" max="604800">
                        <div class="form-text mb-3">
                            Время жизни одной кеш-записи. Можно переопределить для каждой рубрики и каждого документа по отдельности.
                            При сохранении любого документа/рубрики/блока/макета кеш сбрасывается автоматически.
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="public_cache_skip_authenticated" id="publicCacheSkipAuth"
                                value="1" @checked(($settings['public_cache_skip_authenticated'] ?? '1') === '1')>
                            <label class="form-check-label" for="publicCacheSkipAuth">Не кешировать для авторизованных</label>
                        </div>
                        <div class="form-text mb-3">
                            Если посетитель залогинен - кешированный ответ не отдаётся и новый не сохраняется (BYPASS). Сейчас работает для администраторов: позволяет видеть актуальный HTML после правок и не кладёт в кеш страницы с CSRF-токеном админ-сессии. Модуль регистрации посетителей сможет подключиться к этой же опции, чтобы зарегистрированные пользователи не получали HTML, собранный для гостей (и наоборот).
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="public_cache_skip_with_csrf" id="publicCacheSkipCsrf"
                                value="1" @checked(($settings['public_cache_skip_with_csrf'] ?? '1') === '1')>
                            <label class="form-check-label" for="publicCacheSkipCsrf">Не кешировать страницы с CSRF-токеном</label>
                        </div>
                        <div class="form-text mb-3">
                            Если HTML страница содержит <code>name="_token"</code> (CSRF-токен формы для защиты) - кеш не сохраняется. В противном случае токен администратора может быть доступен всем посетителям.
                        </div>

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" name="public_cache_send_headers" id="publicCacheHeaders"
                                value="1" @checked(($settings['public_cache_send_headers'] ?? '1') === '1')>
                            <label class="form-check-label" for="publicCacheHeaders">Подставлять заголовок <code>X-Cache</code> в ответе</label>
                        </div>
                        <div class="form-text">
                            Удобно для отладки: каждый ответ дополняется заголовком <code>X-Cache: HIT</code> / <code>MISS</code> / <code>BYPASS</code>.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Стратегия обработки query-параметров</label>
                        <select name="public_cache_query_strategy" id="publicCacheQueryStrategy" class="form-select mb-1">
                            @foreach([
                                'blacklist'  => 'Кешируется всё, кроме того, что в чёрном списке (рекомендуется)',
                                'whitelist'  => 'Кешируется только то, что попадает в белый список',
                                'ignore_all' => 'Все query-параметры игнорируются полностью',
                                'include_all'=> 'Все параметры кешируются (может раздувать кеш)',
                            ] as $val => $label)
                            <option value="{{ $val }}" @selected(($settings['public_cache_query_strategy'] ?? 'blacklist') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text mb-3">
                            Поддерживаются wildcards со звёздочкой (например <code>utm_*</code>).
                        </div>

                        <label class="form-label">Чёрный список (каждый параметр на отдельной строке)</label>
                        <textarea name="public_cache_query_blacklist" class="form-control font-monospace mb-3" rows="4"
                            placeholder="utm_*&#10;fbclid&#10;yclid&#10;gclid&#10;ref">{{ $settings['public_cache_query_blacklist'] ?? '' }}</textarea>

                        <label class="form-label">Белый список (каждый параметр на отдельной строке)</label>
                        <textarea name="public_cache_query_whitelist" class="form-control font-monospace mb-3" rows="3"
                            placeholder="page&#10;sort">{{ $settings['public_cache_query_whitelist'] ?? '' }}</textarea>

                        <label class="form-label">HTML-маркеры пропуска (каждый маркер на отдельной строке)</label>
                        <textarea name="public_cache_skip_markers" class="form-control font-monospace mb-1" rows="3"
                            placeholder="&lt;form&#10;mod-comment">{{ $settings['public_cache_skip_markers'] ?? '' }}</textarea>
                        <div class="form-text">
                            Если в готовой HTML странице найдена хотя бы одна из этих подстрок - страница не кешируется.
                            Удобно использовать для исключения страниц с пользовательским контентом
                            (комментарии, корзина, формы обратной связи).
                        </div>
                    </div>
                </div>

                <button type="submit" class="btn btn-sm btn-primary mt-3" id="btnSavePublicCache">
                    <i class="bi bi-floppy me-1"></i>Сохранить настройки кэша
                </button>
            </div>
        </div>
    </form>
    @endif
</div>
@endif

@if($canView['maps'])
<div class="tab-pane fade {{ $defaultTab === 'maps' ? 'show active' : '' }}" id="tabMaps">
    @if(!$canEdit['maps'])
    <div class="alert alert-danger py-2 mb-3 ztr-readonly-banner">
        <i class="bi bi-eye me-1"></i>Режим только просмотр - у вас нет прав на сохранение этого таба
    </div>
    @endif

    <form id="formMaps" class="ztr-doc-seo-form" data-initial-provider="{{ $settings['maps_provider'] ?? 'yandex' }}">
        @csrf
        <div class="alert alert-info py-2 mb-3 small">
            <i class="bi bi-info-circle me-1"></i>
            Эти настройки применяются ко всем полям типа «Карта» и публичным виджетам карт. Поле работает и без ключа - координаты можно вводить руками, но в админке не будет интерактивной карты.
        </div>

        <div class="mb-3">
            <label class="form-label">Провайдер карт</label>
            <select class="form-select" name="maps_provider" @if(!$canEdit['maps']) disabled @endif>
                <option value="yandex" {{ ($settings['maps_provider'] ?? 'yandex') === 'yandex' ? 'selected' : '' }}>Яндекс.Карты</option>
                <option value="google" {{ ($settings['maps_provider'] ?? '') === 'google' ? 'selected' : '' }}>Google Maps</option>
            </select>
            <div class="form-text">Будет использоваться для всех полей типа «Карта» в системе.</div>
        </div>

        <div class="mb-3">
            <label class="form-label">API-ключ Яндекс.Карт</label>
            <div class="input-group">
                <input type="text" class="form-control font-monospace" name="yandex_maps_api_key" id="yandexMapsKeyInput"
                       value="{{ $settings['yandex_maps_api_key'] ?? '' }}"
                       placeholder="00000000-0000-0000-0000-000000000000"
                       @if(!$canEdit['maps']) readonly @endif>
                <button type="button" class="btn btn-sm btn-outline-secondary ztr-maps-check-key" data-provider="yandex" data-target="#yandexMapsKeyInput">
                    <i class="bi bi-check-circle me-1"></i>Проверить
                </button>
            </div>
            <div class="form-text">
                Получить: <a href="https://developer.tech.yandex.ru/services/" target="_blank" rel="noopener">developer.tech.yandex.ru → JavaScript API</a>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">API-ключ Google Maps</label>
            <div class="input-group">
                <input type="text" class="form-control font-monospace" name="google_maps_api_key" id="googleMapsKeyInput"
                       value="{{ $settings['google_maps_api_key'] ?? '' }}"
                       placeholder="AIzaSy…"
                       @if(!$canEdit['maps']) readonly @endif>
                <button type="button" class="btn btn-sm btn-outline-secondary ztr-maps-check-key" data-provider="google" data-target="#googleMapsKeyInput">
                    <i class="bi bi-check-circle me-1"></i>Проверить
                </button>
            </div>
            <div class="form-text">
                Получить: <a href="https://console.cloud.google.com/google/maps-apis/" target="_blank" rel="noopener">Google Cloud Console → Maps Platform</a>. Включите «Maps JavaScript API» и «Maps Embed API».
            </div>
        </div>

        @if($canEdit['maps'])
        <button type="submit" class="btn btn-sm btn-primary" id="btnSaveMaps">
            <i class="bi bi-floppy me-1"></i>Сохранить
        </button>
        @endif
    </form>
</div>
@endif

</div>

<div class="modal fade" id="modalClearAll" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Очистить весь кэш</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 ztr-modal-text">Будут очищены: кэш приложения, шаблоны, конфигурация и маршруты.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnClearAllConfirm">Очистить всё</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalClearSessions" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-person-badge me-2"></i>Очистить сессии</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2 ztr-modal-text">Все пользователи будут выброшены из системы, включая вас. Продолжить?</p>
                <div id="sessionsRedisWarn" class="d-none alert alert-warning py-2 mb-0 ztr-modal-small">
                    <i class="bi bi-exclamation-triangle me-1"></i>Сессии и кэш приложения используют одно Redis-подключение. Очистка затронет весь кэш в этом Redis DB.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnClearSessionsConfirm">Да, очистить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEnvSwitch" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-shield-exclamation ztr-modal-icon-warning me-2"></i>Предупреждение безопасности</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body ztr-modal-text">
                <p>Вы переключаетесь с режима <strong>Продакшен</strong> на <strong>Разработка</strong>.</p>
                <div class="env-alert env-alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Если ваш сайт работает в боевой среде, это переключение потенциально опасно: при включённом режиме отладки посетители будут видеть трассировки стека, переменные окружения и другую служебную информацию при любой ошибке на сайте.
                </div>
                <p class="mt-3 mb-0">Переключить режим окружения?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" id="btnEnvSwitchCancel">Отмена</button>
                <button type="button" class="btn btn-warning btn-sm" id="btnEnvSwitchConfirm">Да, переключить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalOptimize" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalOptimizeTitle"><i class="bi bi-lightning-charge me-2"></i>Запустить кеширование</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body ztr-modal-text">
                <p>Будут выполнены команды:</p>
                <ul class="mb-3">
                    <li><code>php artisan config:cache</code> - конфигурация в один файл</li>
                    <li><code>php artisan route:cache</code> - маршруты в один файл</li>
                    <li><code>php artisan view:cache</code> - предкомпиляция всех Blade-шаблонов</li>
                </ul>
                <div class="env-alert env-alert-warning">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    После операции кеширования все изменения внесенные в файл <code>.env</code> или файлы конфигураций вступят в силу <strong>только после процесса перекеширования</strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnOptimizeConfirm">
                    <i class="bi bi-lightning-charge me-1"></i>Запустить
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/settings.css') }}">
@endpush

@push('scripts')
<script>
window.ZentraConfig = {
    routes: {
        saveGeneral: '{{ route('admin.settings.save-general') }}',
        saveEnv: '{{ route('admin.settings.save-env') }}',
        saveSeo: '{{ route('admin.settings.save-seo') }}',
        saveMaps: '{{ route('admin.settings.save-maps') }}',
        mapsCheckKey: '{{ route('admin.settings.maps-check-key') }}',
        emailTest: '{{ route('admin.settings.email-test') }}',
        cacheStats: '{{ route('admin.settings.cache-stats') }}',
        cacheClear: '{{ route('admin.settings.cache-clear') }}',
        cacheClearAll: '{{ route('admin.settings.cache-clear-all') }}',
        cacheClearSessions: '{{ route('admin.settings.cache-clear-sessions') }}',
        optimize:           '{{ route('admin.settings.optimize') }}',
        optimizeClear:      '{{ route('admin.settings.optimize-clear') }}',
        composerOutdated:   '{{ route('admin.settings.composer-outdated') }}',
        redisCheck:         '{{ route('admin.settings.redis-check') }}',
        logNotify:          '{{ route('admin.settings.log-notify') }}',
        logNotifyTest:      '{{ route('admin.settings.log-notify-test') }}',
        sitemapPreview:     '{{ route('admin.settings.sitemap-preview') }}',
        sitemapFlush:       '{{ route('admin.settings.sitemap-flush') }}',
        savePublicCache:    '{{ route('admin.settings.save-public-cache') }}',
        flushPublicCache:   '{{ route('admin.settings.public-cache-flush') }}'
    }
};
</script>
<script src="{{ route('admin.asset', 'js/settings.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash;
    if (!hash) return;
    const target = document.querySelector(hash);
    if (!target) return;
    setTimeout(() => target.scrollIntoView({ behavior: 'smooth', block: 'center' }), 150);
});
</script>
@endpush
