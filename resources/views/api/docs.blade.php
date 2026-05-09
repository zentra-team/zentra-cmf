<!DOCTYPE html>
<html lang="ru" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex">
    <title>API - {{ $siteName }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500&display=swap" rel="stylesheet">
    <style>
        :root {
            --apidoc-bg:        #0e1116;
            --apidoc-side-bg:   #11151c;
            --apidoc-card-bg:   #161b24;
            --apidoc-code-bg:   #0a0d12;
            --apidoc-border:    #232a36;
            --apidoc-text:      #d8dde6;
            --apidoc-muted:     #7d8693;
            --apidoc-accent:    #6e8cff;
            --apidoc-accent-2:  #a892ff;
            --apidoc-success:   #4ec9b0;
            --apidoc-warning:   #e6b070;
            --apidoc-danger:    #e88090;
            --apidoc-side-w:    320px;
        }
        html { scroll-behavior: smooth; }
        body {
            background: var(--apidoc-bg);
            color: var(--apidoc-text);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
            font-size: 15px;
            line-height: 1.65;
            -webkit-font-smoothing: antialiased;
        }
        code, pre { font-family: 'JetBrains Mono', ui-monospace, monospace; }

        .apidoc-shell { display: grid; grid-template-columns: var(--apidoc-side-w) 1fr; min-height: 100vh; }

        /* ─── Sidebar ─── */
        .apidoc-side {
            background: var(--apidoc-side-bg);
            border-right: 1px solid var(--apidoc-border);
            padding: 1.75rem 1.25rem;
            position: sticky; top: 0; height: 100vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #2a313d transparent;
        }
        .apidoc-side::-webkit-scrollbar { width: 6px; }
        .apidoc-side::-webkit-scrollbar-thumb { background: #2a313d; border-radius: 3px; }
        .apidoc-brand {
            display: flex; align-items: center; gap: 0.65rem;
            font-weight: 700; font-size: 1.1rem;
            color: #fff;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--apidoc-border);
        }
        .apidoc-brand-icon {
            width: 32px; height: 32px;
            background: linear-gradient(135deg, var(--apidoc-accent), var(--apidoc-accent-2));
            border-radius: 8px;
            display: grid; place-items: center;
            color: #fff; font-size: 1rem;
        }
        .apidoc-brand small {
            display: block;
            font-weight: 400;
            font-size: 0.72rem;
            color: var(--apidoc-muted);
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-top: 2px;
        }
        .apidoc-toc-section {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: var(--apidoc-muted);
            font-weight: 600;
            margin: 1.25rem 0 0.5rem 0.75rem;
        }
        .apidoc-toc-section:first-child { margin-top: 0; }
        .apidoc-toc a {
            display: flex; align-items: center; gap: 0.55rem;
            color: #b3bac6;
            text-decoration: none;
            padding: 0.4rem 0.75rem;
            font-size: 0.93rem;
            border-radius: 6px;
            border-left: 2px solid transparent;
            margin-bottom: 1px;
        }
        .apidoc-toc a:hover { background: rgba(255,255,255,0.04); color: #fff; }
        .apidoc-toc a.active {
            background: rgba(110,140,255,0.10);
            color: #fff;
            border-left-color: var(--apidoc-accent);
        }
        .apidoc-toc a.is-endpoint {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.83rem;
            padding-left: 0.85rem;
        }
        .apidoc-toc-method {
            display: inline-block;
            min-width: 32px;
            font-size: 0.65rem;
            font-weight: 700;
            color: var(--apidoc-success);
            letter-spacing: 0.05em;
        }

        /* ─── Main ─── */
        .apidoc-main {
            padding: 3rem 3.5rem;
            max-width: 1080px;
            margin: 0 auto;
            width: 100%;
        }
        .apidoc-main h1 {
            font-size: 2.25rem; font-weight: 700;
            color: #fff;
            margin-bottom: 0.5rem;
            letter-spacing: -0.01em;
        }
        .apidoc-main h2 {
            font-size: 1.5rem; font-weight: 700;
            color: #fff;
            margin-top: 3.5rem; margin-bottom: 1rem;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid var(--apidoc-border);
            scroll-margin-top: 1rem;
        }
        .apidoc-main h3 {
            font-size: 1rem; font-weight: 600;
            color: #c8d0db;
            margin-top: 1.5rem; margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-size: 0.78rem;
        }
        .apidoc-main p { margin-bottom: 0.85rem; color: #c8d0db; }
        .apidoc-main p.lead {
            font-size: 1.1rem;
            color: var(--apidoc-muted);
            margin-bottom: 1.5rem;
        }

        /* ─── Inline code & code blocks ─── */
        .apidoc-main :not(pre) > code {
            color: var(--apidoc-accent-2);
            background: rgba(168,146,255,0.1);
            padding: 0.12rem 0.4rem;
            border-radius: 4px;
            font-size: 0.85em;
            border: 1px solid rgba(168,146,255,0.18);
        }
        .apidoc-codeblock {
            position: relative;
            background: var(--apidoc-code-bg);
            border: 1px solid var(--apidoc-border);
            border-radius: 8px;
            margin: 0.85rem 0 1.4rem;
            overflow: hidden;
        }
        .apidoc-codeblock-head {
            display: flex; align-items: center; justify-content: space-between;
            padding: 0.45rem 0.85rem;
            background: rgba(255,255,255,0.02);
            border-bottom: 1px solid var(--apidoc-border);
            font-size: 0.78rem;
            color: var(--apidoc-muted);
        }
        .apidoc-codeblock-lang {
            text-transform: uppercase;
            letter-spacing: 0.06em;
            font-weight: 600;
        }
        .apidoc-copy-btn {
            background: transparent;
            border: 0;
            color: var(--apidoc-muted);
            font-size: 0.78rem;
            padding: 0.15rem 0.5rem;
            border-radius: 4px;
            cursor: pointer;
            transition: all 0.12s;
        }
        .apidoc-copy-btn:hover { color: #fff; background: rgba(255,255,255,0.06); }
        .apidoc-copy-btn.copied { color: var(--apidoc-success); }
        .apidoc-codeblock pre {
            background: transparent;
            margin: 0;
            padding: 0.95rem 1.05rem;
            overflow-x: auto;
            font-size: 0.86rem;
            line-height: 1.6;
            color: #d4d4d4;
        }
        .apidoc-codeblock pre::-webkit-scrollbar { height: 6px; }
        .apidoc-codeblock pre::-webkit-scrollbar-thumb { background: #2a313d; border-radius: 3px; }
        .apidoc-codeblock pre code { background: transparent; padding: 0; color: #d4d4d4; border: 0; font-size: inherit; }

        /* ─── Endpoint cards ─── */
        .apidoc-endpoint {
            background: var(--apidoc-card-bg);
            border: 1px solid var(--apidoc-border);
            border-radius: 12px;
            padding: 1.5rem 1.75rem;
            margin-bottom: 1.5rem;
            scroll-margin-top: 1rem;
        }
        .apidoc-endpoint-head {
            display: flex; align-items: center; gap: 0.7rem;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.95rem;
            background: var(--apidoc-code-bg);
            border: 1px solid var(--apidoc-border);
            padding: 0.65rem 0.95rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            word-break: break-all;
        }
        .apidoc-method {
            flex-shrink: 0;
            padding: 0.18rem 0.6rem;
            border-radius: 4px;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.05em;
            background: rgba(78,201,176,0.15);
            color: var(--apidoc-success);
            border: 1px solid rgba(78,201,176,0.3);
        }
        .apidoc-url-param { color: var(--apidoc-accent-2); }

        /* ─── Tables ─── */
        .apidoc-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        .apidoc-table th, .apidoc-table td {
            padding: 0.6rem 0.85rem;
            border-bottom: 1px solid var(--apidoc-border);
            text-align: left;
            vertical-align: top;
        }
        .apidoc-table thead th {
            background: rgba(255,255,255,0.02);
            color: #fff;
            font-weight: 600;
            font-size: 0.83rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .apidoc-table tbody tr:last-child td { border-bottom: 0; }
        .apidoc-table td code { font-size: 0.83em; }

        /* ─── Banner ─── */
        .apidoc-banner {
            background: rgba(232,128,144,0.08);
            border: 1px solid rgba(232,128,144,0.3);
            color: #f5b9c2;
            padding: 0.85rem 1.1rem;
            border-radius: 8px;
            margin-bottom: 1.75rem;
            display: flex; gap: 0.6rem; align-items: flex-start;
        }
        .apidoc-banner i { font-size: 1.1rem; flex-shrink: 0; margin-top: 0.1rem; }

        /* ─── Rubric cards ─── */
        .apidoc-rubric-grid { display: grid; gap: 0.75rem; margin-bottom: 1rem; }
        .apidoc-rubric-card {
            background: var(--apidoc-card-bg);
            border: 1px solid var(--apidoc-border);
            border-radius: 8px;
            padding: 0.85rem 1.1rem;
        }
        .apidoc-rubric-card-head {
            display: flex; align-items: baseline; gap: 0.55rem;
            margin-bottom: 0.3rem;
        }
        .apidoc-rubric-alias {
            font-family: 'JetBrains Mono', monospace;
            color: var(--apidoc-accent-2);
            font-weight: 600;
            font-size: 0.95rem;
        }
        .apidoc-rubric-title { color: var(--apidoc-muted); font-size: 0.92rem; }
        .apidoc-rubric-fields {
            color: var(--apidoc-muted);
            font-size: 0.85rem;
            display: flex; flex-wrap: wrap; gap: 0.35rem; align-items: center;
        }
        .apidoc-field-pill {
            display: inline-flex; align-items: baseline; gap: 0.3rem;
            background: rgba(255,255,255,0.04);
            border: 1px solid var(--apidoc-border);
            padding: 0.12rem 0.55rem;
            border-radius: 12px;
            font-size: 0.8rem;
            color: var(--apidoc-text);
        }
        .apidoc-field-pill code { color: var(--apidoc-accent-2); background: transparent; border: 0; padding: 0; font-size: 0.85em; }
        .apidoc-field-pill .pill-type { color: var(--apidoc-muted); font-family: 'JetBrains Mono', monospace; font-size: 0.78em; }
        .apidoc-empty {
            color: var(--apidoc-muted);
            background: rgba(230,176,112,0.05);
            border: 1px solid rgba(230,176,112,0.2);
            padding: 0.85rem 1.1rem;
            border-radius: 8px;
            display: flex; gap: 0.6rem; align-items: flex-start;
        }

        /* ─── Status badges ─── */
        .apidoc-status {
            display: inline-block;
            padding: 0.15rem 0.55rem;
            border-radius: 4px;
            font-size: 0.78rem;
            font-weight: 700;
            font-family: 'JetBrains Mono', monospace;
        }
        .apidoc-status-401 { background: rgba(232,128,144,0.15); color: var(--apidoc-danger); }
        .apidoc-status-403 { background: rgba(232,128,144,0.15); color: var(--apidoc-danger); }
        .apidoc-status-404 { background: rgba(230,176,112,0.15); color: var(--apidoc-warning); }
        .apidoc-status-429 { background: rgba(230,176,112,0.15); color: var(--apidoc-warning); }
        .apidoc-status-503 { background: rgba(230,176,112,0.15); color: var(--apidoc-warning); }

        /* ─── Misc ─── */
        ul { padding-left: 1.4rem; color: #c8d0db; }
        ul li { margin-bottom: 0.3rem; }
        hr { border-color: var(--apidoc-border); }
        .apidoc-footer {
            margin-top: 4rem;
            padding-top: 2rem;
            border-top: 1px solid var(--apidoc-border);
            color: var(--apidoc-muted);
            font-size: 0.85rem;
            text-align: center;
        }

        /* ─── Responsive ─── */
        @media (max-width: 1100px) {
            .apidoc-main { padding: 2rem 1.75rem; }
        }
        @media (max-width: 900px) {
            .apidoc-shell { grid-template-columns: 1fr; }
            .apidoc-side {
                position: static; height: auto;
                border-right: 0; border-bottom: 1px solid var(--apidoc-border);
            }
        }
    </style>
</head>
<body>

@php
    $sampleAlias    = $sampleRubric?->alias ?? 'blog';
    $sampleDocAlias = $sampleDoc?->alias ?? 'hello-world';
    $tokenSample    = 'zcm_a1b2c3d4e5f6...';
@endphp

<div class="apidoc-shell">
    <aside class="apidoc-side">
        <div class="apidoc-brand">
            <div class="apidoc-brand-icon"><i class="bi bi-braces"></i></div>
            <div>
                {{ $siteName }}
                <small>JSON API · v1</small>
            </div>
        </div>
        <nav class="apidoc-toc" id="apidocToc">
            <div class="apidoc-toc-section">Начало</div>
            <a href="#intro">Введение</a>
            <a href="#base-url">Базовый URL</a>
            <a href="#auth">Аутентификация</a>
            <a href="#limits">Лимиты и кеш</a>

            <div class="apidoc-toc-section">Эндпоинты</div>
            <a href="#endpoint-rubrics" class="is-endpoint"><span class="apidoc-toc-method">GET</span>/rubrics</a>
            <a href="#endpoint-rubric" class="is-endpoint"><span class="apidoc-toc-method">GET</span>/rubrics/&#123;alias&#125;</a>
            <a href="#endpoint-docs" class="is-endpoint"><span class="apidoc-toc-method">GET</span>/.../documents</a>
            <a href="#endpoint-doc" class="is-endpoint"><span class="apidoc-toc-method">GET</span>/.../documents/&#123;alias&#125;</a>

            <div class="apidoc-toc-section">Справочники</div>
            <a href="#available-rubrics">Доступные рубрики</a>
            <a href="#field-types">Типы полей</a>
            <a href="#errors">Ошибки</a>
            <a href="#pagination">Пагинация</a>
        </nav>
    </aside>

    <main class="apidoc-main" id="apidocMain">

        @if (! $apiEnabled)
            <div class="apidoc-banner">
                <i class="bi bi-exclamation-triangle"></i>
                <div>
                    <strong>API временно отключён администратором.</strong> Эта страница описывает контракт; запросы будут возвращать <code>503 api_disabled</code>, пока API не включат в <em>Настройках → SEO и коды → JSON API</em>.
                </div>
            </div>
        @endif

        <h1>JSON API</h1>
        <p class="lead">
            Read-only HTTP API для внешних клиентов: получайте структурированные данные документов сайта в формате JSON. Каждый запрос требует токен и проходит per-token rate-limit.
        </p>

        <h2 id="intro">Введение</h2>
        <p>API позволяет получить:</p>
        <ul>
            <li>Список рубрик, открытых для API.</li>
            <li>Метаданные одной рубрики и список её публичных полей.</li>
            <li>Список документов рубрики (с пагинацией и сортировкой).</li>
            <li>Один документ со всеми его публичными полями и SEO-meta.</li>
        </ul>
        <p>API не позволяет создавать, изменять или удалять данные - это сознательное ограничение. Все CRUD-операции идут через админку.</p>

        <h2 id="base-url">Базовый URL</h2>
        <p>Все примеры в этой документации используют ваш реальный базовый URL:</p>
        <div class="apidoc-codeblock">
            <div class="apidoc-codeblock-head">
                <span class="apidoc-codeblock-lang">URL</span>
                <button type="button" class="apidoc-copy-btn" data-copy-target>
                    <i class="bi bi-clipboard"></i> Копировать
                </button>
            </div>
            <pre><code>{{ $baseUrl }}</code></pre>
        </div>
        <p>
            Базовый URL формируется из настроек <code>api_domain</code> и <code>api_url_prefix</code>. Если домен не задан - API живёт на основном хосте по префиксу пути.
        </p>

        <h2 id="auth">Аутентификация</h2>
        <p>Каждый запрос обязан содержать заголовок <code>X-API-Key</code> с токеном клиента:</p>
        <div class="apidoc-codeblock">
            <div class="apidoc-codeblock-head">
                <span class="apidoc-codeblock-lang">HTTP Header</span>
                <button type="button" class="apidoc-copy-btn" data-copy-target>
                    <i class="bi bi-clipboard"></i> Копировать
                </button>
            </div>
            <pre><code>X-API-Key: {{ $tokenSample }}</code></pre>
        </div>
        <p>
            Токен начинается с префикса <code>zcm_</code>, имеет 44 символа всего. Получите токен у администратора сайта - у каждого клиента свой. Никогда не публикуйте токен в открытых репозиториях, логах и фронтенд-бандлах.
        </p>

        <h2 id="limits">Лимиты и кеш</h2>
        <p>
            <strong>Rate limit.</strong> По умолчанию <code>{{ $rateDefault }}</code> запросов в минуту на токен. Точный лимит для конкретного токена устанавливает администратор. При превышении возвращается <code>429 rate_limited</code> с заголовком <code>Retry-After</code>.
        </p>
        <p>
            <strong>Серверный кеш.</strong> Ответы кешируются на стороне сервера на <code>{{ $cacheTtl }}</code> секунд. Изменения в админке инвалидируют кеш автоматически - клиенту не нужно посылать <code>Cache-Control</code>.
        </p>

        <h2 id="endpoints">Эндпоинты</h2>

        
        <div id="endpoint-rubrics" class="apidoc-endpoint">
            <h3 style="margin-top:0">Список рубрик</h3>
            <div class="apidoc-endpoint-head">
                <span class="apidoc-method">GET</span>
                <span>{{ $baseUrl }}/rubrics</span>
            </div>
            <p>Возвращает все рубрики, открытые для API. Если у токена ограниченный список рубрик (per-token ACL) - отфильтровано по этому списку.</p>

            <h3>Запрос</h3>
            <div class="apidoc-codeblock">
                <div class="apidoc-codeblock-head">
                    <span class="apidoc-codeblock-lang">cURL</span>
                    <button type="button" class="apidoc-copy-btn" data-copy-target>
                        <i class="bi bi-clipboard"></i> Копировать
                    </button>
                </div>
                <pre><code>curl -H "X-API-Key: {{ $tokenSample }}" \
     "{{ $baseUrl }}/rubrics"</code></pre>
            </div>

            <h3>Ответ <code>200 OK</code></h3>
            <div class="apidoc-codeblock">
                <div class="apidoc-codeblock-head">
                    <span class="apidoc-codeblock-lang">JSON</span>
                    <button type="button" class="apidoc-copy-btn" data-copy-target>
                        <i class="bi bi-clipboard"></i> Копировать
                    </button>
                </div>
                <pre><code>{{ $samples['rubrics'] }}</code></pre>
            </div>
        </div>

        
        <div id="endpoint-rubric" class="apidoc-endpoint">
            <h3 style="margin-top:0">Одна рубрика</h3>
            <div class="apidoc-endpoint-head">
                <span class="apidoc-method">GET</span>
                <span>{{ $baseUrl }}/rubrics/<span class="apidoc-url-param">{alias}</span></span>
            </div>
            <p>Метаданные рубрики и список её полей с <code>in_api=true</code> - клиент знает, что ожидать в <code>fields</code> у документов.</p>

            <h3>Запрос</h3>
            <div class="apidoc-codeblock">
                <div class="apidoc-codeblock-head">
                    <span class="apidoc-codeblock-lang">cURL</span>
                    <button type="button" class="apidoc-copy-btn" data-copy-target>
                        <i class="bi bi-clipboard"></i> Копировать
                    </button>
                </div>
                <pre><code>curl -H "X-API-Key: {{ $tokenSample }}" \
     "{{ $baseUrl }}/rubrics/{{ $sampleAlias }}"</code></pre>
            </div>

            <h3>Ответ <code>200 OK</code></h3>
            <div class="apidoc-codeblock">
                <div class="apidoc-codeblock-head">
                    <span class="apidoc-codeblock-lang">JSON</span>
                    <button type="button" class="apidoc-copy-btn" data-copy-target>
                        <i class="bi bi-clipboard"></i> Копировать
                    </button>
                </div>
                <pre><code>{{ $samples['rubric'] }}</code></pre>
            </div>
        </div>

        
        <div id="endpoint-docs" class="apidoc-endpoint">
            <h3 style="margin-top:0">Список документов рубрики</h3>
            <div class="apidoc-endpoint-head">
                <span class="apidoc-method">GET</span>
                <span>{{ $baseUrl }}/rubrics/<span class="apidoc-url-param">{alias}</span>/documents</span>
            </div>
            <p>Документы рубрики с пагинацией. Поля документов в этом эндпоинте - только те, что помечены <code>in_api</code>.</p>

            <h3>Параметры</h3>
            <table class="apidoc-table">
                <thead>
                    <tr><th>Параметр</th><th>Тип</th><th>По умолчанию</th><th>Описание</th></tr>
                </thead>
                <tbody>
                    <tr><td><code>page</code></td><td>int</td><td><code>1</code></td><td>Номер страницы.</td></tr>
                    <tr><td><code>per_page</code></td><td>int</td><td><code>20</code></td><td>Документов на страницу (1–100).</td></tr>
                    <tr><td><code>sort</code></td><td>string</td><td><code>-created_at</code></td><td>Поле сортировки. Префикс <code>-</code> - по убыванию. Допустимы: <code>created_at</code>, <code>updated_at</code>, <code>published_at</code>, <code>position</code>, <code>title</code>.</td></tr>
                </tbody>
            </table>

            <h3>Запрос</h3>
            <div class="apidoc-codeblock">
                <div class="apidoc-codeblock-head">
                    <span class="apidoc-codeblock-lang">cURL</span>
                    <button type="button" class="apidoc-copy-btn" data-copy-target>
                        <i class="bi bi-clipboard"></i> Копировать
                    </button>
                </div>
                <pre><code>curl -H "X-API-Key: {{ $tokenSample }}" \
     "{{ $baseUrl }}/rubrics/{{ $sampleAlias }}/documents?page=1&per_page=10&sort=-published_at"</code></pre>
            </div>

            <h3>Ответ <code>200 OK</code></h3>
            <div class="apidoc-codeblock">
                <div class="apidoc-codeblock-head">
                    <span class="apidoc-codeblock-lang">JSON</span>
                    <button type="button" class="apidoc-copy-btn" data-copy-target>
                        <i class="bi bi-clipboard"></i> Копировать
                    </button>
                </div>
                <pre><code>{{ $samples['documents'] }}</code></pre>
            </div>
        </div>

        
        <div id="endpoint-doc" class="apidoc-endpoint">
            <h3 style="margin-top:0">Один документ</h3>
            <div class="apidoc-endpoint-head">
                <span class="apidoc-method">GET</span>
                <span>{{ $baseUrl }}/rubrics/<span class="apidoc-url-param">{alias}</span>/documents/<span class="apidoc-url-param">{docAlias}</span></span>
            </div>
            <p>Полные данные одного документа: SEO-meta и все поля с <code>in_api=true</code>.</p>

            <h3>Запрос</h3>
            <div class="apidoc-codeblock">
                <div class="apidoc-codeblock-head">
                    <span class="apidoc-codeblock-lang">cURL</span>
                    <button type="button" class="apidoc-copy-btn" data-copy-target>
                        <i class="bi bi-clipboard"></i> Копировать
                    </button>
                </div>
                <pre><code>curl -H "X-API-Key: {{ $tokenSample }}" \
     "{{ $baseUrl }}/rubrics/{{ $sampleAlias }}/documents/{{ $sampleDocAlias }}"</code></pre>
            </div>

            <h3>Ответ <code>200 OK</code></h3>
            <div class="apidoc-codeblock">
                <div class="apidoc-codeblock-head">
                    <span class="apidoc-codeblock-lang">JSON</span>
                    <button type="button" class="apidoc-copy-btn" data-copy-target>
                        <i class="bi bi-clipboard"></i> Копировать
                    </button>
                </div>
                <pre><code>{{ $samples['document'] }}</code></pre>
            </div>
        </div>

        <h2 id="available-rubrics">Доступные рубрики</h2>
        @if ($rubrics->isEmpty())
            <div class="apidoc-empty">
                <i class="bi bi-info-circle"></i>
                <div>
                    Сейчас ни одна рубрика не открыта для API. Администратор должен включить чекбокс «Отдавать в API» на нужных рубриках, а у полей этих рубрик - отметить «<code>in_api</code>».
                </div>
            </div>
        @else
            <p>На сайте открыто {{ $rubrics->count() }} {{ trans_choice('рубрика|рубрики|рубрик', $rubrics->count()) }}:</p>
            <div class="apidoc-rubric-grid">
                @foreach ($rubrics as $r)
                    <div class="apidoc-rubric-card">
                        <div class="apidoc-rubric-card-head">
                            <span class="apidoc-rubric-alias">{{ $r->alias }}</span>
                            <span class="apidoc-rubric-title">— {{ $r->title }}</span>
                        </div>
                        @if ($r->apiFields->isEmpty())
                            <div class="apidoc-rubric-fields">
                                <i class="bi bi-exclamation-triangle text-warning me-1"></i>
                                нет полей с <code>in_api=true</code> - будут возвращаться только базовые метаданные
                            </div>
                        @else
                            <div class="apidoc-rubric-fields">
                                @foreach ($r->apiFields as $f)
                                    <span class="apidoc-field-pill">
                                        <code>{{ $f->alias }}</code>
                                        <span class="pill-type">{{ $f->type }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <h2 id="field-types">Типы полей и сериализация</h2>
        <p>Значения <code>fields</code> сериализуются в зависимости от типа поля:</p>
        <table class="apidoc-table">
            <thead>
                <tr><th>Тип поля</th><th>JSON-тип</th><th>Пример</th></tr>
            </thead>
            <tbody>
                <tr><td><code>text</code>, <code>textarea</code>, <code>markdown</code>, <code>code</code>, <code>email</code>, <code>url</code>, <code>phone</code>, <code>color</code>, <code>icon</code></td><td>string</td><td><code>"hello"</code></td></tr>
                <tr><td><code>number</code>, <code>rating</code>, <code>slider</code></td><td>int / float</td><td><code>42</code></td></tr>
                <tr><td><code>checkbox</code></td><td>bool</td><td><code>true</code></td></tr>
                <tr><td><code>date</code>, <code>datetime</code>, <code>time</code></td><td>string (ISO 8601)</td><td><code>"2026-05-07T12:34:56+07:00"</code></td></tr>
                <tr><td><code>tags</code>, <code>checkbox_list</code></td><td>array&lt;string&gt;</td><td><code>["a","b"]</code></td></tr>
                <tr><td><code>price</code></td><td>object</td><td><code>{"amount": 100, "currency": "RUB"}</code></td></tr>
                <tr><td><code>image</code>, <code>file</code>, <code>doc_link</code></td><td>object</td><td><code>{"url":"...", "alt":"..."}</code></td></tr>
                <tr><td><code>gallery</code></td><td>array&lt;object&gt;</td><td><code>[{"url":"...","alt":"..."}]</code></td></tr>
                <tr><td><code>map</code></td><td>object</td><td><code>{"lat": 55.75, "lng": 37.62}</code></td></tr>
                <tr><td><code>video</code>, <code>key_value</code>, <code>repeater</code></td><td>object / array</td><td>зависит от структуры</td></tr>
                <tr><td><code>relation</code>, <code>relation_multi</code></td><td>int / array&lt;int&gt;</td><td><code>[3, 7]</code></td></tr>
            </tbody>
        </table>
        <p style="color:var(--apidoc-muted); font-size:0.9rem">Если значение поля пустое - возвращается <code>null</code>.</p>

        <h2 id="errors">Ошибки</h2>
        <p>Все ошибки имеют единый формат:</p>
        <div class="apidoc-codeblock">
            <div class="apidoc-codeblock-head">
                <span class="apidoc-codeblock-lang">JSON</span>
                <button type="button" class="apidoc-copy-btn" data-copy-target>
                    <i class="bi bi-clipboard"></i> Копировать
                </button>
            </div>
            <pre><code>{
    "error": {
        "code": "invalid_token",
        "message": "Token is invalid or revoked."
    }
}</code></pre>
        </div>
        <table class="apidoc-table">
            <thead><tr><th>HTTP</th><th>code</th><th>Когда</th></tr></thead>
            <tbody>
                <tr><td><span class="apidoc-status apidoc-status-401">401</span></td><td><code>missing_token</code></td><td>Заголовок <code>X-API-Key</code> не передан.</td></tr>
                <tr><td><span class="apidoc-status apidoc-status-401">401</span></td><td><code>invalid_token</code></td><td>Токен не найден в системе или удалён.</td></tr>
                <tr><td><span class="apidoc-status apidoc-status-401">401</span></td><td><code>token_inactive</code></td><td>Токен временно деактивирован администратором.</td></tr>
                <tr><td><span class="apidoc-status apidoc-status-401">401</span></td><td><code>token_expired</code></td><td>У токена истёк срок действия.</td></tr>
                <tr><td><span class="apidoc-status apidoc-status-403">403</span></td><td><code>rubric_forbidden</code></td><td>Токен не имеет доступа к запрошенной рубрике.</td></tr>
                <tr><td><span class="apidoc-status apidoc-status-404">404</span></td><td><code>not_found</code></td><td>Рубрика/документ не найдены или не помечены как доступные через API.</td></tr>
                <tr><td><span class="apidoc-status apidoc-status-429">429</span></td><td><code>rate_limited</code></td><td>Превышен лимит запросов в минуту. См. заголовок <code>Retry-After</code>.</td></tr>
                <tr><td><span class="apidoc-status apidoc-status-503">503</span></td><td><code>api_disabled</code></td><td>API временно отключён администратором.</td></tr>
            </tbody>
        </table>

        <h2 id="pagination">Пагинация</h2>
        <p>На эндпоинтах со списками возвращаются блоки <code>meta</code> и <code>links</code>:</p>
        <div class="apidoc-codeblock">
            <div class="apidoc-codeblock-head">
                <span class="apidoc-codeblock-lang">JSON</span>
                <button type="button" class="apidoc-copy-btn" data-copy-target>
                    <i class="bi bi-clipboard"></i> Копировать
                </button>
            </div>
            <pre><code>"meta": {
    "page":      1,
    "per_page":  20,
    "total":     142,
    "last_page": 8
},
"links": {
    "self":  "{{ $baseUrl }}/rubrics/{{ $sampleAlias }}/documents?page=1",
    "next":  "{{ $baseUrl }}/rubrics/{{ $sampleAlias }}/documents?page=2",
    "prev":  null,
    "first": "{{ $baseUrl }}/rubrics/{{ $sampleAlias }}/documents?page=1",
    "last":  "{{ $baseUrl }}/rubrics/{{ $sampleAlias }}/documents?page=8"
}</code></pre>
        </div>
        <p style="color:var(--apidoc-muted); font-size:0.9rem">
            Для страниц-листингов лучше итерировать по <code>links.next</code> - это гарантированно правильный URL со всеми query-параметрами вашего исходного запроса.
        </p>

        <div class="apidoc-footer">
            Документация сгенерирована автоматически из текущих настроек {{ $siteName }} в {{ now()->format('d.m.Y H:i') }}.
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // Copy code from sibling <pre> for any [data-copy-target] button.
    document.querySelectorAll('.apidoc-copy-btn').forEach(btn => {
        btn.addEventListener('click', async () => {
            const block = btn.closest('.apidoc-codeblock');
            const code  = block?.querySelector('pre')?.innerText;
            if (! code) return;
            try {
                await navigator.clipboard.writeText(code);
                btn.classList.add('copied');
                btn.innerHTML = '<i class="bi bi-check2"></i> Скопировано';
                setTimeout(() => {
                    btn.classList.remove('copied');
                    btn.innerHTML = '<i class="bi bi-clipboard"></i> Копировать';
                }, 1400);
            } catch {}
        });
    });

    // Manual scroll-spy: highlight TOC link of section currently in viewport.
    const links = Array.from(document.querySelectorAll('#apidocToc a[href^="#"]'));
    const targets = links.map(a => document.getElementById(a.getAttribute('href').slice(1))).filter(Boolean);
    function setActive() {
        const top = 80;
        let idx = 0;
        for (let i = 0; i < targets.length; i++) {
            if (targets[i].getBoundingClientRect().top - top <= 0) idx = i;
        }
        links.forEach(l => l.classList.remove('active'));
        const id = targets[idx]?.id;
        if (id) document.querySelector('#apidocToc a[href="#' + id + '"]')?.classList.add('active');
    }
    setActive();
    window.addEventListener('scroll', setActive, { passive: true });
    window.addEventListener('hashchange', setActive);
</script>
</body>
</html>
