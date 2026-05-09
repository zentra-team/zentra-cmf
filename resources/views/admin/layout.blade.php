<!DOCTYPE html>
<html lang="ru" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $__mapsProvider = \App\Models\Setting::getValue('maps_provider', 'yandex');
        $__mapsKey = $__mapsProvider === 'google'
            ? \App\Models\Setting::getValue('google_maps_api_key', '')
            : \App\Models\Setting::getValue('yandex_maps_api_key', '');
    @endphp
    <script>
        window.ZentraMaps = {
            provider: @json($__mapsProvider),
            key:      @json($__mapsKey),
        };
    </script>
    <title>@yield('title', 'Панель управления') - Zentra</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="{{ route('admin.asset', 'css/zentra.css') }}">
    @stack('styles')
</head>
<body>

<header class="ztr-header">
    <div class="ztr-header-left">
        <button class="ztr-sidebar-toggle-btn" id="sidebarToggleBtn" title="Свернуть меню">
            <i class="bi bi-list" style="font-size:1.1rem"></i>
        </button>
        <div class="ztr-header-brand">Zentra <span>CMF</span></div>
        <div class="ztr-header-greeting">
            Привет, <strong>{{ auth()->user()?->name ?? 'Администратор' }}</strong>
        </div>
    </div>
    <div class="ztr-header-right">
        <a href="#" data-bs-toggle="modal" data-bs-target="#modalPlatformUpdate"
           id="headerUpdateBadge" class="btn btn-sm btn-warning text-dark d-none" title="Доступно обновление платформы">
            <i class="bi bi-arrow-up-circle me-1"></i><span id="headerUpdateVersion"></span>
        </a>
        <button class="btn btn-sm btn-outline-info" id="btnClearCache" title="Очистить кеш">
            <i class="bi bi-arrow-repeat me-1"></i> Очистить кеш
        </button>
        <a href="/" target="_blank" class="btn btn-sm btn-outline-success" title="Перейти на сайт">
            <i class="bi bi-box-arrow-up-right me-1"></i> На сайт
        </a>
        <form method="POST" action="{{ route('admin.logout') }}" class="d-inline" id="logoutForm">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-danger">
                <i class="bi bi-box-arrow-right me-1"></i> Выход
            </button>
        </form>
    </div>
</header>

<div class="ztr-body">
<div class="ztr-body-inner">

    @php
        use App\Support\Permission;
        $navUser = auth('admin')->user();
    @endphp
    <aside class="ztr-sidebar" id="sidebar">
        <nav class="ztr-nav">

            <div class="ztr-nav-section">Главная</div>
            <a href="{{ route('admin.dashboard') }}" class="ztr-nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-speedometer2"></i>
                <span class="ztr-nav-label">Дашборд</span>
            </a>

            @if($navUser && $navUser->hasPermission(Permission::DOCUMENTS_ACCESS))
            @php $contentVisible = true; @endphp
            @endif
            @if($navUser && $navUser->hasPermission(Permission::RUBRICS_ACCESS))
            @php $contentVisible = true; @endphp
            @endif
            @if($navUser && $navUser->hasPermission(Permission::BLOCKS_ACCESS))
            @php $contentVisible = true; @endphp
            @endif
            @if($navUser && $navUser->hasPermission(Permission::REQUESTS_ACCESS))
            @php $contentVisible = true; @endphp
            @endif
            @if(!empty($contentVisible))
            <div class="ztr-nav-section">Контент</div>
            @if($navUser->hasPermission(Permission::DOCUMENTS_ACCESS))
            <a href="{{ route('admin.documents.index') }}" class="ztr-nav-link {{ request()->routeIs('admin.documents.*') ? 'active' : '' }}">
                <i class="bi bi-file-text"></i>
                <span class="ztr-nav-label">Документы</span>
            </a>
            @endif
            @if($navUser->hasPermission(Permission::RUBRICS_ACCESS))
            <a href="{{ route('admin.rubrics.index') }}" class="ztr-nav-link {{ request()->routeIs('admin.rubrics.*') ? 'active' : '' }}">
                <i class="bi bi-folder2"></i>
                <span class="ztr-nav-label">Рубрики</span>
            </a>
            @endif
            @if($navUser->hasPermission(Permission::BLOCKS_ACCESS))
            <a href="{{ route('admin.blocks.index') }}" class="ztr-nav-link {{ request()->routeIs('admin.blocks.*') ? 'active' : '' }}">
                <i class="bi bi-puzzle"></i>
                <span class="ztr-nav-label">Блоки</span>
            </a>
            @endif
            @if($navUser->hasPermission(Permission::REQUESTS_ACCESS))
            <a href="{{ route('admin.requests.index') }}" class="ztr-nav-link {{ request()->routeIs('admin.requests.*') ? 'active' : '' }}">
                <i class="bi bi-inbox"></i>
                <span class="ztr-nav-label">Запросы</span>
            </a>
            @endif
            @endif

            @if($navUser && $navUser->hasAnyPermission([Permission::LAYOUTS_ACCESS, Permission::NAVIGATIONS_ACCESS, Permission::REDIRECTS_ACCESS]))
            <div class="ztr-nav-section">Дизайн</div>
            @if($navUser->hasPermission(Permission::LAYOUTS_ACCESS))
            <a href="{{ route('admin.layouts.index') }}" class="ztr-nav-link {{ request()->routeIs('admin.layouts.*') ? 'active' : '' }}">
                <i class="bi bi-layout-text-sidebar"></i>
                <span class="ztr-nav-label">Макеты</span>
            </a>
            @endif
            @if($navUser->hasPermission(Permission::NAVIGATIONS_ACCESS))
            <a href="{{ route('admin.navigations.index') }}" class="ztr-nav-link {{ request()->routeIs('admin.navigations.*') ? 'active' : '' }}">
                <i class="bi bi-list-nested"></i>
                <span class="ztr-nav-label">Навигация</span>
            </a>
            @endif
            @if($navUser->hasPermission(Permission::REDIRECTS_ACCESS))
            <a href="{{ route('admin.redirects.index') }}" class="ztr-nav-link {{ request()->routeIs('admin.redirects.*') ? 'active' : '' }}">
                <i class="bi bi-signpost-split"></i>
                <span class="ztr-nav-label">Редиректы</span>
            </a>
            @endif
            @endif

            @if($navUser && $navUser->hasAnyPermission([Permission::USERS_ACCESS, Permission::GROUPS_ACCESS, Permission::MODULES_LIST, Permission::MODULES_USE, Permission::MODULES_INSTALL, Permission::API_TOKENS_ACCESS, Permission::DB_ACCESS, Permission::LOGS_ACCESS, Permission::SETTINGS_ACCESS]))
            <div class="ztr-nav-section">Система</div>
            @if($navUser->hasPermission(Permission::USERS_ACCESS))
            <a href="{{ route('admin.users.index') }}" class="ztr-nav-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                <i class="bi bi-people"></i>
                <span class="ztr-nav-label">Пользователи</span>
            </a>
            @endif
            @if($navUser->hasPermission(Permission::GROUPS_ACCESS))
            <a href="{{ route('admin.user-groups.index') }}" class="ztr-nav-link {{ request()->routeIs('admin.user-groups.*') ? 'active' : '' }}">
                <i class="bi bi-shield-check"></i>
                <span class="ztr-nav-label">Группы / Права</span>
            </a>
            @endif
            @if($navUser->hasAnyPermission([Permission::MODULES_LIST, Permission::MODULES_USE, Permission::MODULES_INSTALL]))
            <a href="{{ route('admin.modules.index') }}" class="ztr-nav-link {{ request()->routeIs('admin.modules.*') ? 'active' : '' }}">
                <i class="bi bi-box-seam"></i>
                <span class="ztr-nav-label">Модули</span>
            </a>
            @endif
            @if($navUser->hasPermission(Permission::API_TOKENS_ACCESS))
            <a href="{{ route('admin.api-tokens.index') }}" class="ztr-nav-link {{ request()->routeIs('admin.api-tokens.*') ? 'active' : '' }}">
                <i class="bi bi-braces"></i>
                <span class="ztr-nav-label">API-токены</span>
            </a>
            @endif
            @if($navUser->hasPermission(Permission::DB_ACCESS))
            <a href="{{ route('admin.database') }}" class="ztr-nav-link {{ request()->routeIs('admin.database') ? 'active' : '' }}">
                <i class="bi bi-database"></i>
                <span class="ztr-nav-label">База данных</span>
            </a>
            @endif
            @if($navUser->hasPermission(Permission::LOGS_ACCESS))
            <a href="{{ route('admin.logs.index') }}" class="ztr-nav-link {{ request()->routeIs('admin.logs.*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i>
                <span class="ztr-nav-label">События</span>
            </a>
            @endif
            @if($navUser->hasPermission(Permission::SETTINGS_ACCESS))
            <a href="{{ route('admin.settings') }}" class="ztr-nav-link {{ request()->routeIs('admin.settings') ? 'active' : '' }}">
                <i class="bi bi-gear"></i>
                <span class="ztr-nav-label">Системные настройки</span>
            </a>
            @endif
            @endif

        </nav>

        <div class="ztr-sidebar-footer">
            <button class="ztr-sidebar-toggle" id="sidebarCollapseBtn">
                <i class="bi bi-arrow-bar-left" id="sidebarCollapseIcon"></i>
                <span class="ztr-nav-label">Свернуть меню</span>
            </button>
        </div>
    </aside>

    <main class="ztr-content">
        @yield('content')
    </main>

</div>

<footer class="ztr-footer">
    <div class="ztr-footer-left">
        <strong>Zentra CMF</strong> · © {{ date('Y') }}
    </div>
    <div class="ztr-footer-right">
        <a href="/" target="_blank" rel="noopener">Открыть сайт</a>
    </div>
</footer>

</div>

<div class="modal fade" id="modalClearCache" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Очистить кеш</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" style="font-size:.875rem">Очистить весь кеш приложения?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnClearCacheConfirm" data-route="{{ route('admin.cache.clear') }}">Очистить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalPlatformUpdate" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-up-circle me-2"></i>Версия платформы</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" id="updateCheckingBody">
                <div class="d-flex align-items-center gap-3 py-2">
                    <span class="spinner-border spinner-border-sm text-muted flex-shrink-0"></span>
                    <span class="text-muted" style="font-size:.875rem">Проверяем наличие обновлений…</span>
                </div>
            </div>

            <div class="modal-body d-none" id="updateUpToDateBody">
                <div class="d-flex align-items-center gap-3 py-1">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:1.6rem;flex-shrink:0"></i>
                    <div>
                        <div class="fw-semibold mb-1">Версия актуальна</div>
                        <div class="text-muted" style="font-size:.85rem">
                            Установлена последняя версия Zentra CMF
                            <span class="badge bg-primary ms-1" id="updateCurrentVersionUpToDate"></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-body d-none" id="updateModalBody">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="text-muted" style="font-size:.875rem">Текущая:</span>
                    <span class="badge bg-secondary" id="updateCurrentVersion"></span>
                    <i class="bi bi-arrow-right text-muted"></i>
                    <span class="badge bg-warning text-dark" id="updateNewVersion"></span>
                </div>
                <div id="updateChangelog" class="border rounded p-2 mb-0" style="max-height:220px;overflow-y:auto;font-size:.82rem;white-space:pre-wrap;background:var(--bs-body-bg)"></div>
            </div>

            <div class="modal-body d-none" id="updateManualBody">
                <div class="alert alert-warning mb-3" style="font-size:.875rem">
                    <i class="bi bi-exclamation-triangle me-2"></i>Нет прав на запись в директорию проекта. Обновите вручную.
                </div>
                <ol class="mb-2" id="updateManualSteps" style="font-size:.85rem;padding-left:1.2rem"></ol>
                <a href="#" id="updateDownloadLink" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-download me-1"></i>Скачать архив
                </a>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-outline-secondary btn-sm d-none" id="btnReinstall">
                    <i class="bi bi-arrow-clockwise me-1"></i>Переустановить текущую версию
                </button>
                <button type="button" class="btn btn-warning btn-sm text-dark d-none" id="btnPerformUpdate">
                    <i class="bi bi-arrow-up-circle me-1"></i>Обновить
                </button>
            </div>
        </div>
    </div>
</div>

<div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="{{ route('admin.asset', 'js/core.js') }}"></script>
<script>
window.ZentraPlatformCfg = {
    platformCheckUrl:  '{{ route('admin.platform.check') }}',
    platformUpdateUrl: '{{ route('admin.platform.update') }}',
    currentVersion:    '{{ config('app.version', '1.0.0') }}',
    csrfToken:         '{{ csrf_token() }}',
};
</script>
<script src="{{ route('admin.asset', 'js/platform-update.js') }}"></script>
<script>
(function () {
    var cached = sessionStorage.getItem('ztr_platform_update');
    if (!cached) {
        fetch('{{ route('admin.platform.check') }}')
            .then(function (r) { return r.ok ? r.json() : null; })
            .then(function (data) {
                if (!data) return;
                sessionStorage.setItem('ztr_platform_update', JSON.stringify(data));
                if (data.update) {
                    showHeaderUpdateBadge(data.update.version);
                }
            })
            .catch(function () {});
    } else {
        try {
            var data = JSON.parse(cached);
            if (data && data.update) {
                showHeaderUpdateBadge(data.update.version);
            }
        } catch (e) {}
    }

    function showHeaderUpdateBadge(version) {
        var badge = document.getElementById('headerUpdateBadge');
        var label = document.getElementById('headerUpdateVersion');
        if (badge && label) {
            label.textContent = '↑ v' + version;
            badge.classList.remove('d-none');
        }
    }
})();
</script>
<script>
@if(session('toast_success'))
    showToast(@json(session('toast_success')), 'success');
@endif
@if(session('toast_error'))
    showToast(@json(session('toast_error')), 'error');
@endif
@if(session('toast_warning'))
    showToast(@json(session('toast_warning')), 'warning');
@endif
@if(session('toast_info'))
    showToast(@json(session('toast_info')), 'info');
@endif
</script>
@stack('scripts')
</body>
</html>
