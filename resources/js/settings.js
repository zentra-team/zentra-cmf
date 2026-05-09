(function () {
    const cfg = window.ZentraConfig || {};
    const routes = cfg.routes || {};
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    async function ajaxPost(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': CSRF, Accept: 'application/json' },
            body,
        });
        const text = await res.text();
        let data;
        try {
            data = JSON.parse(text);
        } catch {
            data = { ok: false, message: text || 'Неизвестная ошибка' };
        }
        return { ok: res.ok, data };
    }

    function setBtn(btn, loading, origHtml) {
        btn.disabled = loading;
        btn.innerHTML = loading ? '<span class="spinner-border spinner-border-sm me-1"></span>Сохранение...' : origHtml;
    }

    function envAlert(type, html) {
        const colors = {
            danger: { bg: 'rgba(239,68,68,.08)', border: 'rgba(239,68,68,.3)', text: '#fca5a5' },
            warning: { bg: 'rgba(245,158,11,.08)', border: 'rgba(245,158,11,.3)', text: '#fcd34d' },
            info: { bg: 'rgba(59,130,246,.08)', border: 'rgba(59,130,246,.3)', text: '#93c5fd' },
            success: { bg: 'rgba(34,197,94,.08)', border: 'rgba(34,197,94,.3)', text: '#86efac' },
        };
        const c = colors[type] || colors.info;
        return `<div style="font-size:.8125rem;padding:.5rem .875rem;border-radius:5px;border:1px solid ${c.border};background:${c.bg};color:${c.text}">${html}</div>`;
    }

    const formGeneral = document.getElementById('formGeneral');
    if (formGeneral) {
        formGeneral.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSaveGeneral');
            if (!btn) {return;}
            const orig = btn.innerHTML;
            setBtn(btn, true, orig);
            const { ok, data } = await ajaxPost(routes.saveGeneral, new FormData(e.target));
            showToast(data.message, ok && data.ok ? 'success' : 'error');
            setBtn(btn, false, orig);
        });
    }

    const formEnv = document.getElementById('formEnv');
    if (formEnv) {
        formEnv.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSaveEnv');
            if (!btn) {return;}
            const orig = btn.innerHTML;
            setBtn(btn, true, orig);
            const { ok, data } = await ajaxPost(routes.saveEnv, new FormData(e.target));
            showToast(data.message, ok && data.ok ? 'success' : 'error');
            setBtn(btn, false, orig);
        });

        const envAppEnv = document.getElementById('envAppEnv');
        const envAppDebug = document.getElementById('envAppDebug');
        let prevEnvValue = envAppEnv.value;

        function updateAppAlerts() {
            const isProd = envAppEnv.value === 'production';

            if (isProd) {
                envAppDebug.checked = false;
                envAppDebug.disabled = true;
                document.getElementById('debugBlock').style.opacity = '0.45';
                document.getElementById('debugBlock').style.pointerEvents = 'none';
            } else {
                envAppDebug.disabled = false;
                document.getElementById('debugBlock').style.opacity = '';
                document.getElementById('debugBlock').style.pointerEvents = '';
            }

            document.getElementById('envAppDebugLabel').textContent = envAppDebug.checked ? 'Включён' : 'Выключен';
        }

        const modalEnvSwitch = new bootstrap.Modal(document.getElementById('modalEnvSwitch'));

        async function saveEnvMode() {
            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('APP_ENV', envAppEnv.value);
            fd.append('APP_DEBUG', envAppDebug.checked ? '1' : '0');
            const { ok, data } = await ajaxPost(routes.saveEnv, fd);
            const label = envAppEnv.value === 'production' ? 'Продакшен' : 'Разработка';
            showToast(
                ok && data.ok ? `Режим окружения переключён на «${label}»` : (data.message ?? 'Ошибка сохранения'),
                ok && data.ok ? 'success' : 'error',
            );
            if (ok && data.ok) {loadOptimizeStatus();}
        }

        envAppEnv.addEventListener('change', () => {
            const fromProd = prevEnvValue === 'production';
            const toLocal = envAppEnv.value === 'local';

            if (fromProd && toLocal) {
                modalEnvSwitch.show();
                return;
            }

            prevEnvValue = envAppEnv.value;
            updateAppAlerts();
            updateLogAlert();
            saveEnvMode();
        });

        document.getElementById('btnEnvSwitchCancel').addEventListener('click', () => {
            envAppEnv.value = prevEnvValue;
            modalEnvSwitch.hide();
        });

        document.getElementById('btnEnvSwitchConfirm').addEventListener('click', () => {
            prevEnvValue = envAppEnv.value;
            modalEnvSwitch.hide();
            updateAppAlerts();
            updateLogAlert();
            saveEnvMode();
        });

        envAppDebug.addEventListener('change', updateAppAlerts);
        updateAppAlerts();

        const modalOptimize = new bootstrap.Modal(document.getElementById('modalOptimize'));

        const BADGE_YES = (label) =>
            `<span class="badge" style="background:rgba(34,197,94,.12);color:#86efac;border:1px solid rgba(34,197,94,.25);font-weight:500"><i class="bi bi-check-circle me-1"></i>${label}</span>`;
        const BADGE_NO = (label) =>
            `<span class="badge" style="background:rgba(100,116,139,.1);color:var(--ztr-text-muted);border:1px solid rgba(100,116,139,.2);font-weight:500"><i class="bi bi-x-circle me-1"></i>${label}</span>`;

        async function loadOptimizeStatus() {
            const badgesEl = document.getElementById('cacheStatusBadges');
            const buttonsEl = document.getElementById('cacheActionButtons');
            badgesEl.innerHTML = '<span class="spinner-border spinner-border-sm text-secondary"></span>';
            buttonsEl.innerHTML = '';

            try {
                const res = await fetch(routes.cacheStats, { headers: { Accept: 'application/json' } });
                if (!res.ok) {
                    badgesEl.innerHTML =
                        '<span style="font-size:.8125rem;color:var(--ztr-text-muted)">Нет доступа к данным</span>';
                    return;
                }
                const data = await res.json();

                const isCached = data.config_cached && data.routes_cached;

                badgesEl.innerHTML =
                    (data.config_cached ? BADGE_YES('Конфиг') : BADGE_NO('Конфиг')) +
                    (data.routes_cached ? BADGE_YES('Маршруты') : BADGE_NO('Маршруты'));

                if (isCached) {
                    buttonsEl.innerHTML =
                        '<button type="button" class="btn btn-sm btn-outline-secondary" id="btnRecache"><i class="bi bi-arrow-repeat me-1"></i>Перекешировать</button>' +
                        '<button type="button" class="btn btn-sm btn-outline-danger"    id="btnClearOptimize"><i class="bi bi-x-lg me-1"></i>Очистить кеш</button>';
                    document.getElementById('btnRecache').addEventListener('click', () => {
                        document.getElementById('modalOptimizeTitle').innerHTML =
                            '<i class="bi bi-arrow-repeat me-2"></i>Перекешировать';
                        modalOptimize.show();
                    });
                    document.getElementById('btnClearOptimize').addEventListener('click', runClearOptimize);
                } else {
                    buttonsEl.innerHTML =
                        '<button type="button" class="btn btn-sm btn-primary" id="btnRunOptimize"><i class="bi bi-lightning-charge me-1"></i>Запустить кеширование</button>';
                    document.getElementById('btnRunOptimize').addEventListener('click', () => {
                        document.getElementById('modalOptimizeTitle').innerHTML =
                            '<i class="bi bi-lightning-charge me-2"></i>Запустить кеширование';
                        modalOptimize.show();
                    });
                }
            } catch {
                badgesEl.innerHTML =
                    '<span style="font-size:.8125rem;color:var(--ztr-text-muted)">Не удалось загрузить статус</span>';
            }
        }

        async function runClearOptimize() {
            const btn = document.getElementById('btnClearOptimize');
            if (!btn) {return;}
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            const fd = new FormData();
            fd.append('_token', CSRF);
            const { ok, data } = await ajaxPost(routes.optimizeClear, fd);
            showToast(data.message ?? (ok ? 'Кеш очищен' : 'Ошибка'), ok && data.ok ? 'success' : 'error');
            loadOptimizeStatus();
        }

        document.getElementById('btnOptimizeConfirm').addEventListener('click', async () => {
            const btn = document.getElementById('btnOptimizeConfirm'),
                orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Выполняется...';
            const fd = new FormData();
            fd.append('_token', CSRF);
            const { ok, data } = await ajaxPost(routes.optimize, fd);
            modalOptimize.hide();
            btn.disabled = false;
            btn.innerHTML = orig;
            showToast(data.message ?? (ok ? 'Готово' : 'Ошибка'), ok && data.ok ? 'success' : 'error');
            loadOptimizeStatus();
        });

        const envTabEl = document.querySelector('#settingsTabs a[href="#tabEnv"]');
        if (envTabEl) {
            envTabEl.addEventListener('shown.bs.tab', loadOptimizeStatus);
            if (document.getElementById('tabEnv').classList.contains('show')) {
                loadOptimizeStatus();
            }
        }

        const envLogLevel = document.getElementById('envLogLevel');
        function updateLogAlert() {
            const isProd = envAppEnv.value === 'production';
            const isDebug = envLogLevel.value === 'debug';
            document.getElementById('alertLogDebug').style.display = isProd && isDebug ? 'block' : 'none';
        }
        envLogLevel.addEventListener('change', updateLogAlert);
        updateLogAlert();

        const sessionHints = {
            file: () =>
                envAlert(
                    'success',
                    '<i class="bi bi-check-circle me-2"></i>Сессии хранятся в файлах на сервере. Работает сразу, никакой дополнительной настройки не нужно.',
                ),
            database: () =>
                envAlert(
                    'success',
                    '<i class="bi bi-check-circle me-2"></i>Сессии хранятся в таблице <code>sessions</code> в базе данных. Таблица уже создана при установке системы - дополнительных действий не требуется.',
                ),
            redis: () =>
                envAlert(
                    'warning',
                    '<i class="bi bi-exclamation-triangle me-2"></i><strong>Требуется дополнительная настройка сервера.</strong> Для работы Redis необходимо: установить и запустить сервер Redis, а также подключить PHP-расширение <code>phpredis</code> или установить пакет <code>predis/predis</code> через Composer. Без этого сайт перестанет работать. Укажите параметры подключения в блоке <strong>Redis</strong> ниже.',
                ),
            cookie: () =>
                envAlert(
                    'warning',
                    '<i class="bi bi-exclamation-triangle me-2"></i>Сессия хранится в cookie браузера. Ограничение - <strong>~4 КБ</strong>. Не подходит если в сессии хранится много данных. Для защиты данных рекомендуется включить шифрование сессий (<code>SESSION_ENCRYPT=true</code> в <code>.env</code>).',
                ),
        };

        const cacheHints = {
            file: () =>
                envAlert(
                    'success',
                    '<i class="bi bi-check-circle me-2"></i>Кэш хранится в файлах в <code>storage/framework/cache</code>. Работает сразу без дополнительной настройки.',
                ),
            database: () =>
                envAlert(
                    'success',
                    '<i class="bi bi-check-circle me-2"></i>Кэш хранится в таблице <code>cache</code> в базе данных. Таблица уже создана при установке системы - дополнительных действий не требуется.',
                ),
            redis: () =>
                envAlert(
                    'warning',
                    '<i class="bi bi-exclamation-triangle me-2"></i><strong>Требуется дополнительная настройка сервера.</strong> Для работы Redis необходимо: установить и запустить сервер Redis, а также подключить PHP-расширение <code>phpredis</code> или установить пакет <code>predis/predis</code> через Composer. Без этого кэш работать не будет. Укажите параметры подключения в блоке <strong>Redis</strong> ниже.',
                ),
            array: () =>
                envAlert(
                    'warning',
                    '<i class="bi bi-exclamation-triangle me-2"></i><strong>Только для тестирования.</strong> Кэш хранится в памяти PHP и полностью теряется между запросами. Не используйте в продакшене.',
                ),
        };

        const envSessionDriver = document.getElementById('envSessionDriver');
        const envCacheStore = document.getElementById('envCacheStore');

        let redisChecked = false;

        async function checkRedisPackages() {
            if (redisChecked) {return;}
            redisChecked = true;

            try {
                const res = await fetch(routes.redisCheck, { headers: { Accept: 'application/json' } });
                const data = await res.json();

                const yes = '<span style="color:#86efac">(установлен)</span>';
                const no = '<span style="color:#fca5a5">(не установлен)</span>';

                document.getElementById('redisStatusPhpredis').innerHTML = data.phpredis ? yes : no;
                document.getElementById('redisStatusPredis').innerHTML = data.predis ? yes : no;

                if (!data.phpredis && !data.predis) {
                    const box = document.getElementById('redisInfoBox');
                    box.classList.remove('env-alert-info');
                    box.classList.add('env-alert-warning');
                    box.querySelector('i').className = 'bi bi-exclamation-triangle me-2';
                }
            } catch {
                document.getElementById('redisStatusPhpredis').textContent = '';
                document.getElementById('redisStatusPredis').textContent = '';
            }
        }

        function updateRedisBlock() {
            const needRedis = envCacheStore.value === 'redis' || envSessionDriver.value === 'redis';
            document.getElementById('envCardRedis').style.display = needRedis ? 'block' : 'none';
            if (needRedis) {checkRedisPackages();}
        }

        function updateSessionHint() {
            const hint = sessionHints[envSessionDriver.value];
            document.getElementById('sessionHint').innerHTML = hint ? hint() : '';
            updateRedisBlock();
        }
        envSessionDriver.addEventListener('change', updateSessionHint);
        updateSessionHint();

        function updateCacheHint() {
            const hint = cacheHints[envCacheStore.value];
            document.getElementById('cacheHint').innerHTML = hint ? hint() : '';
            updateRedisBlock();
        }
        envCacheStore.addEventListener('change', updateCacheHint);
        updateCacheHint();

        const mailHints = {
            smtp: null,
            log: () =>
                envAlert(
                    'success',
                    '<i class="bi bi-check-circle me-2"></i>Письма <strong>не отправляются</strong> - записываются в лог Laravel (<code>storage/logs/laravel.log</code>). Идеально для разработки и отладки.',
                ),
        };

        const envMailMailer = document.getElementById('envMailMailer');
        function updateMailBlock() {
            const isSmtp = envMailMailer.value === 'smtp';
            document.getElementById('smtpFields').style.display = isSmtp ? '' : 'none';
            const hint = mailHints[envMailMailer.value];
            document.getElementById('mailHint').innerHTML = hint ? hint() : '';
        }
        envMailMailer.addEventListener('change', updateMailBlock);
        updateMailBlock();

        document.getElementById('btnSendTest').addEventListener('click', async () => {
            const input = document.getElementById('testEmailInput');
            const email = input.value.trim();
            if (!email) {
                showToast('Введите email для теста', 'warning');
                input.focus();
                return;
            }

            const btn = document.getElementById('btnSendTest'),
                orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Отправка...';

            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('test_email', email);

            const mailFields = [
                'MAIL_MAILER',
                'MAIL_HOST',
                'MAIL_PORT',
                'MAIL_USERNAME',
                'MAIL_PASSWORD',
                'MAIL_FROM_ADDRESS',
                'MAIL_FROM_NAME',
            ];
            mailFields.forEach((k) => {
                const el = formEnv.elements[k];
                if (el && el.value !== '') {fd.append(k, el.value);}
            });

            const { ok, data } = await ajaxPost(routes.emailTest, fd);
            showToast(data.message, ok && data.ok ? 'success' : 'error');
            btn.disabled = false;
            btn.innerHTML = orig;
        });

        const STATUS_LABEL = {
            'semver-safe-update': {
                text: 'обновление',
                color: '#fcd34d',
                bg: 'rgba(245,158,11,.12)',
                border: 'rgba(245,158,11,.3)',
            },
            'update-possible': {
                text: 'мажорное обновление',
                color: '#fca5a5',
                bg: 'rgba(239,68,68,.10)',
                border: 'rgba(239,68,68,.25)',
            },
        };

        function renderComposerResults(packages, fresh) {
            const el = document.getElementById('composerResults');

            if (!packages.length) {
                el.innerHTML = `<div style="font-size:.875rem;color:#86efac"><i class="bi bi-check-circle me-2"></i>Все пакеты актуальны${fresh ? '' : ' <span style="color:var(--ztr-text-muted)">(данные из кеша)</span>'}</div>`;
                return;
            }

            const rows = packages
                .map((p) => {
                    const s = STATUS_LABEL[p['latest-status']] || STATUS_LABEL['semver-safe-update'];
                    const badge = `<span style="font-size:.75rem;padding:.15em .5em;border-radius:4px;background:${s.bg};color:${s.color};border:1px solid ${s.border};white-space:nowrap">${s.text}</span>`;
                    return `<tr style="font-size:.8125rem">
                    <td style="padding:.35rem .5rem"><code style="font-size:.8rem">${p.name}</code></td>
                    <td style="padding:.35rem .5rem;color:var(--ztr-text-muted)">${p.version}</td>
                    <td style="padding:.35rem .5rem"><code style="font-size:.8rem;color:#86efac">${p.latest}</code></td>
                    <td style="padding:.35rem .5rem">${badge}</td>
                </tr>`;
                })
                .join('');

            const hasMajor = packages.some((p) => p['latest-status'] === 'update-possible');
            const cacheNote = fresh
                ? ''
                : ' <span style="color:var(--ztr-text-muted);font-size:.75rem">(данные из кеша)</span>';

            const recText = hasMajor
                ? 'Среди доступных обновлений есть <strong>мажорные версии</strong> - они могут содержать несовместимые изменения. Рекомендуем проверить список изменений каждого такого пакета перед обновлением.'
                : 'Доступны обновления пакетов, в которых могут быть исправлены ошибки и уязвимости безопасности. Обновление не является обязательным, однако рекомендуется поддерживать зависимости в актуальном состоянии.';

            el.innerHTML = `
                <div style="font-size:.8125rem;color:var(--ztr-text-muted);margin-bottom:.75rem">
                    Найдено устаревших пакетов: <strong style="color:#fcd34d">${packages.length}</strong>${cacheNote}
                </div>
                <div style="overflow-x:auto;margin-bottom:1rem">
                    <table style="width:100%;border-collapse:collapse">
                        <thead>
                            <tr style="font-size:.75rem;color:var(--ztr-text-muted);border-bottom:1px solid var(--ztr-border)">
                                <th style="padding:.25rem .5rem;font-weight:500;text-align:left">Пакет</th>
                                <th style="padding:.25rem .5rem;font-weight:500;text-align:left">Текущая</th>
                                <th style="padding:.25rem .5rem;font-weight:500;text-align:left">Доступна</th>
                                <th style="padding:.25rem .5rem;font-weight:500;text-align:left">Тип</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
                <div class="env-alert env-alert-warning" style="font-size:.8125rem">
                    <i class="bi bi-info-circle me-2"></i>${recText}
                    <div style="margin-top:.5rem;color:var(--ztr-text-muted)">
                        Для обновления обратитесь к вашему техническому специалисту. На сервере достаточно выполнить команду <code>composer update</code> в корневой директории проекта.
                    </div>
                </div>`;
        }

        async function loadComposerOutdated(fresh = false) {
            const btn = document.getElementById('btnCheckPackages');
            const el = document.getElementById('composerResults');

            const origHtml = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Проверка...';
            el.innerHTML = '';

            try {
                const url = routes.composerOutdated + (fresh ? '?fresh=1' : '');
                const res = await fetch(url, { headers: { Accept: 'application/json' } });
                const data = await res.json();

                if (!data.ok) {
                    el.innerHTML = `<div style="font-size:.8125rem;color:#fca5a5"><i class="bi bi-exclamation-circle me-2"></i>${data.message ?? 'Ошибка'}</div>`;
                    btn.innerHTML = origHtml;
                    btn.disabled = false;
                    return;
                }

                renderComposerResults(data.packages, fresh);

                btn.innerHTML = '<i class="bi bi-arrow-repeat me-1"></i>Обновить информацию о пакетах';
                btn.disabled = false;
                btn.onclick = () => loadComposerOutdated(true);
            } catch {
                el.innerHTML = `<div style="font-size:.8125rem;color:#fca5a5"><i class="bi bi-exclamation-circle me-2"></i>Ошибка соединения</div>`;
                btn.innerHTML = origHtml;
                btn.disabled = false;
            }
        }

        document.getElementById('btnCheckPackages').addEventListener('click', () => loadComposerOutdated(false));

        (function () {
            const btn = document.getElementById('logLevelInfoBtn');
            if (!btn) {return;}
            const html = [
                '<strong>debug</strong> - всё, включая отладочные сообщения',
                '<strong>info</strong> - информационные события',
                '<strong>notice</strong> - важные, но не ошибочные',
                '<strong>warning</strong> - предупреждения',
                '<strong>error</strong> - ошибки, не останавливающие приложение',
                '<strong>critical</strong> - критические ошибки',
                '<strong>alert</strong> - немедленное вмешательство',
                '<strong>emergency</strong> - система неработоспособна',
            ].join('<br>');
            new bootstrap.Popover(btn, {
                html: true,
                trigger: 'focus',
                placement: 'right',
                title: 'Уровни логирования',
                content: html,
            });
        })();

        function syncNotifyFields(checkboxId, fieldsId) {
            const cb = document.getElementById(checkboxId);
            const fields = document.getElementById(fieldsId);
            if (!cb || !fields) {return;}
            const apply = () => {
                fields.style.display = cb.checked ? '' : 'none';
            };
            cb.addEventListener('change', apply);
            apply();
        }
        syncNotifyFields('notifyEmailEnabled', 'notifyEmailFields');
        syncNotifyFields('notifyTelegramEnabled', 'notifyTelegramFields');

        function collectNotifyData() {
            const fd = new FormData();
            fd.append('_token', CSRF);
            fd.append('level', document.getElementById('notifyLevel').value);
            fd.append('email_enabled', document.getElementById('notifyEmailEnabled').checked ? '1' : '0');
            fd.append('email', document.getElementById('notifyEmail').value.trim());
            fd.append('telegram_enabled', document.getElementById('notifyTelegramEnabled').checked ? '1' : '0');
            fd.append('telegram_token', document.getElementById('notifyTelegramToken').value.trim());
            fd.append('telegram_chat_id', document.getElementById('notifyTelegramChatId').value.trim());
            return fd;
        }

        document.getElementById('btnSaveLogNotify').addEventListener('click', async () => {
            const btn = document.getElementById('btnSaveLogNotify'),
                orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Сохранение...';
            const { ok, data } = await ajaxPost(routes.logNotify, collectNotifyData());
            showToast(data.message ?? (ok ? 'Сохранено' : 'Ошибка'), ok && data.ok ? 'success' : 'error');
            btn.disabled = false;
            btn.innerHTML = orig;
        });

        document.getElementById('btnTestLogNotify').addEventListener('click', async () => {
            const emailEnabled = document.getElementById('notifyEmailEnabled').checked;
            const telegramEnabled = document.getElementById('notifyTelegramEnabled').checked;
            if (!emailEnabled && !telegramEnabled) {
                showToast('Включите хотя бы один способ уведомлений', 'warning');
                return;
            }
            const btn = document.getElementById('btnTestLogNotify'),
                orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Отправка...';
            const { ok, data } = await ajaxPost(routes.logNotifyTest, collectNotifyData());
            showToast(data.message ?? (ok ? 'Отправлено' : 'Ошибка'), ok && data.ok ? 'success' : 'error');
            btn.disabled = false;
            btn.innerHTML = orig;
        });
    }

    const formSeo = document.getElementById('formSeo');
    if (formSeo) {
        const suffixSelect = formSeo.querySelector('[name="url_suffix"]');
        const suffixPreview = document.getElementById('suffixPreview');
        if (suffixSelect && suffixPreview) {
            suffixSelect.addEventListener('change', () => {
                suffixPreview.textContent = suffixSelect.value;
            });
        }

        formSeo.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSaveSeo');
            if (!btn) {return;}
            const orig = btn.innerHTML;
            setBtn(btn, true, orig);
            const { ok, data } = await ajaxPost(routes.saveSeo, new FormData(e.target));
            showToast(data.message, ok && data.ok ? 'success' : 'error');
            setBtn(btn, false, orig);

            if (ok && data.ok) {sitemapPreviewLoaded = false;}
        });

        const sitemapPreview = document.getElementById('sitemapPreview');
        const sitemapPreviewBody = document.getElementById('sitemapPreviewBody');
        const sitemapPreviewSummary = document.getElementById('sitemapPreviewSummary');
        const sitemapPreviewContent = document.getElementById('sitemapPreviewContent');
        let sitemapPreviewLoaded = false;

        async function loadSitemapPreview() {
            if (sitemapPreviewLoaded) {return;}
            try {
                const res = await fetch(routes.sitemapPreview, { headers: { Accept: 'application/json' } });
                const data = await res.json();
                if (!res.ok || !data.ok) {
                    sitemapPreviewContent.innerHTML =
                        '<div class="text-danger small">Ошибка: ' + (data.message || 'не удалось загрузить') + '</div>';
                    return;
                }
                const p = data.preview;
                if (!data.enabled) {
                    sitemapPreviewSummary.textContent = 'sitemap выключен';
                    sitemapPreviewContent.innerHTML =
                        '<div class="text-muted small">Sitemap отключён глобально - на /sitemap.xml вернётся 404. Включите чекбокс выше и сохраните.</div>';
                } else {
                    sitemapPreviewSummary.textContent = 'попадёт ' + p.total + ' URL';
                    let html = '<div class="row g-2">';
                    html += '<div class="col-md-6">';
                    html += '<table class="table table-sm mb-0 ztr-sitemap-preview-table">';
                    html += '<tr><td>Всего URL</td><td class="text-end fw-semibold">' + p.total + '</td></tr>';
                    html +=
                        '<tr><td>Главная страница</td><td class="text-end">' +
                        (p.homepage ? '<span class="text-success">✓</span>' : '<span class="text-muted">—</span>') +
                        '</td></tr>';
                    html +=
                        '<tr><td>Индексные страницы рубрик</td><td class="text-end">' + p.rubric_indexes + '</td></tr>';
                    html += '<tr><td>Документы</td><td class="text-end">' + p.documents + '</td></tr>';
                    if (p.chunks > 1) {
                        html +=
                            '<tr><td>Файлов в sitemap-index</td><td class="text-end fw-semibold text-warning">' +
                            p.chunks +
                            '</td></tr>';
                    }
                    html += '</table></div>';
                    html += '<div class="col-md-6">';
                    html += '<table class="table table-sm mb-0 ztr-sitemap-preview-table text-muted">';
                    html +=
                        '<tr><td>Исключено: (документы с noindex)</td><td class="text-end">' +
                        p.excluded_noindex +
                        '</td></tr>';
                    html +=
                        '<tr><td>Исключено: (документ не опубликован)</td><td class="text-end">' +
                        p.excluded_unpublished +
                        '</td></tr>';
                    html +=
                        '<tr><td>Исключено: (запрет у рубрики)</td><td class="text-end">' +
                        p.excluded_rubric +
                        '</td></tr>';
                    if (data.cache_ttl > 0) {
                        html += '<tr><td>Кеш TTL</td><td class="text-end">' + data.cache_ttl + ' сек</td></tr>';
                    }
                    html += '</table></div></div>';
                    sitemapPreviewContent.innerHTML = html;
                }
                sitemapPreviewLoaded = true;
            } catch (e) {
                sitemapPreviewContent.innerHTML = '<div class="text-danger small">Ошибка соединения</div>';
            }
        }

        if (sitemapPreviewBody) {
            sitemapPreviewBody.addEventListener('shown.bs.collapse', loadSitemapPreview);

            const sitemapPreviewToggle = sitemapPreview?.querySelector('.ztr-sitemap-preview-toggle');
            sitemapPreviewToggle?.addEventListener('click', () => {
                setTimeout(loadSitemapPreview, 50);
            });

            if (sitemapPreviewBody.classList.contains('show')) {
                loadSitemapPreview();
            }
        }

        const btnSitemapFlush = document.getElementById('btnSitemapFlush');
        btnSitemapFlush?.addEventListener('click', async () => {
            const orig = btnSitemapFlush.innerHTML;
            btnSitemapFlush.disabled = true;
            try {
                const res = await fetch(btnSitemapFlush.dataset.url, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                });
                const data = await res.json();
                showToast(data.message || 'Готово', res.ok && data.ok ? 'success' : 'error');
                if (res.ok && data.ok) {sitemapPreviewLoaded = false;}
            } catch (e) {
                showToast('Ошибка соединения', 'error');
            } finally {
                btnSitemapFlush.disabled = false;
                btnSitemapFlush.innerHTML = orig;
            }
        });
    }

    const formMaps = document.getElementById('formMaps');
    if (formMaps) {
        formMaps.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnSaveMaps');
            if (!btn) {return;}
            const orig = btn.innerHTML;
            setBtn(btn, true, orig);
            const initialProvider = formMaps.dataset.initialProvider || '';
            const newProvider = formMaps.querySelector('[name="maps_provider"]')?.value || '';
            const { ok, data } = await ajaxPost(routes.saveMaps, new FormData(e.target));
            showToast(data.message, ok && data.ok ? 'success' : 'error');
            if (ok && data.ok && initialProvider && newProvider && initialProvider !== newProvider) {
                showToast('Перезагрузите открытые вкладки админки, чтобы карта подхватила новый SDK', 'info');
                formMaps.dataset.initialProvider = newProvider;
            }
            setBtn(btn, false, orig);
        });

        formMaps.querySelectorAll('.ztr-maps-check-key').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const target = formMaps.querySelector(btn.dataset.target);
                if (!target) {return;}
                const provider = btn.dataset.provider;
                const key = (target.value || '').trim();
                if (!key) {
                    showToast('Сначала введите ключ', 'error');
                    return;
                }
                const orig = btn.innerHTML;
                setBtn(btn, true, orig);

                if (provider === 'yandex') {

                    const sdkUrl = `https://api-maps.yandex.ru/v3/?apikey=${encodeURIComponent(key)}&lang=ru_RU`;
                    try {
                        document.querySelector('script[src*="api-maps.yandex.ru/v3/"]')?.remove();
                        delete window.ymaps3;
                        await new Promise((resolve, reject) => {
                            const s = document.createElement('script');
                            s.src = sdkUrl;
                            s.onload = resolve;
                            s.onerror = () => reject(new Error('Яндекс отказал: ключ недействителен или домен не добавлен в настройках ключа'));
                            document.head.appendChild(s);
                        });
                        await Promise.race([
                            ymaps3.ready,
                            new Promise((_, reject) => setTimeout(() => reject(new Error('Таймаут — Яндекс не ответил за 5 с')), 5000)),
                        ]);
                        showToast('Ключ работает', 'success');
                    } catch (e) {
                        showToast(e.message || 'Ключ недействителен', 'error');
                    }
                    setBtn(btn, false, orig);
                    return;
                }

                const fd = new FormData();
                fd.append('provider', provider);
                fd.append('key', key);
                const { ok, data } = await ajaxPost(routes.mapsCheckKey, fd);
                showToast(data.message || 'Не удалось выполнить проверку', ok && data.ok ? 'success' : 'error');
                setBtn(btn, false, orig);
            });
        });
    }

    const cacheTabLink = document.querySelector('#settingsTabs a[href="#tabCache"]');
    if (cacheTabLink) {
        let cacheStatsLoaded = false;
        let sessionsRedisShared = false;

        function cacheStatsError() {
            document.getElementById('cacheAppLabel').textContent = '';
            document.getElementById('cacheAppValue').textContent = '—';
            document.getElementById('cacheAppHint').textContent = '';
            document.getElementById('cacheViewsSize').textContent = '—';
            document.getElementById('cacheSessionsLabel').textContent = '';
            document.getElementById('cacheSessionsValue').textContent = '—';
            document.getElementById('cacheSessionsHint').textContent = '';
            document.getElementById('cacheConfigStatus').textContent = 'Ошибка загрузки';
        }

        function applySessionsSupport(supported, hint) {
            const btn = document.getElementById('btnClearSessions');
            if (!btn) {return;}
            const wrapper = btn.closest('[data-sessions-wrap]');
            btn.disabled = !supported;
            if (!supported && hint) {
                wrapper.setAttribute('data-bs-toggle', 'tooltip');
                wrapper.setAttribute('data-bs-title', hint);
                bootstrap.Tooltip.getOrCreateInstance(wrapper);
            } else {
                const tip = bootstrap.Tooltip.getInstance(wrapper);
                if (tip) {tip.dispose();}
                wrapper.removeAttribute('data-bs-toggle');
                wrapper.removeAttribute('data-bs-title');
            }
        }

        async function loadCacheStats() {
            if (cacheStatsLoaded) {return;}
            try {
                const res = await fetch(routes.cacheStats, { headers: { Accept: 'application/json' } });
                if (!res.ok) {
                    cacheStatsError();
                    return;
                }
                const text = await res.text();
                let data;
                try {
                    data = JSON.parse(text);
                } catch {
                    cacheStatsError();
                    return;
                }

                const cache = data.cache || {};
                document.getElementById('cacheAppLabel').textContent = cache.label || '';
                document.getElementById('cacheAppValue').textContent = cache.value || '—';
                document.getElementById('cacheAppHint').textContent = cache.hint || '';

                document.getElementById('cacheViewsSize').textContent = data.views ?? '—';

                const sess = data.sessions || {};
                document.getElementById('cacheSessionsLabel').textContent = sess.label || '';
                document.getElementById('cacheSessionsValue').textContent = sess.value || '—';
                document.getElementById('cacheSessionsHint').textContent = sess.hint || '';
                sessionsRedisShared = sess.driver === 'redis' && !!sess.shared;
                applySessionsSupport(sess.supported !== false, sess.hint || '');

                const cfgEl = document.getElementById('cacheConfigStatus');
                cfgEl.innerHTML =
                    (data.config_cached
                        ? '<span class="badge bg-warning text-dark me-1">Конфиг</span>'
                        : '<span class="badge bg-secondary me-1">Нет конфига</span>') +
                    (data.routes_cached
                        ? '<span class="badge bg-warning text-dark">Роуты</span>'
                        : '<span class="badge bg-secondary">Нет роутов</span>');

                cacheStatsLoaded = true;
            } catch {
                cacheStatsError();
            }
        }

        cacheTabLink.addEventListener('shown.bs.tab', loadCacheStats);

        document.querySelectorAll('.btn-clear-cache').forEach((btn) => {
            btn.addEventListener('click', async () => {
                const orig = btn.innerHTML;
                btn.disabled = true;
                btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                const fd = new FormData();
                fd.append('_token', CSRF);
                fd.append('type', btn.dataset.type);
                const { ok, data } = await ajaxPost(routes.cacheClear, fd);
                showToast(data.message, ok && data.ok ? 'success' : 'error');
                btn.disabled = false;
                btn.innerHTML = orig;
                cacheStatsLoaded = false;
                loadCacheStats();
            });
        });

        const modalClearSessions = new bootstrap.Modal(document.getElementById('modalClearSessions'));
        const btnClearSessions = document.getElementById('btnClearSessions');
        if (btnClearSessions) {
            btnClearSessions.addEventListener('click', () => {
                const warn = document.getElementById('sessionsRedisWarn');
                if (warn) {warn.classList.toggle('d-none', !sessionsRedisShared);}
                modalClearSessions.show();
            });
        }

        document.getElementById('btnClearSessionsConfirm').addEventListener('click', async () => {
            modalClearSessions.hide();
            const btn = document.getElementById('btnClearSessions');
            if (!btn) {return;}
            const orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            const fd = new FormData();
            fd.append('_token', CSRF);
            const { ok, data } = await ajaxPost(routes.cacheClearSessions, fd);
            showToast(data.message, ok && data.ok ? 'success' : 'error');
            if (ok && data.ok && data.logged_out && data.redirect_to) {
                setTimeout(() => {
                    window.location.href = data.redirect_to;
                }, 1200);
                return;
            }
            btn.disabled = false;
            btn.innerHTML = orig;
            cacheStatsLoaded = false;
            loadCacheStats();
        });

        const modalClearAll = new bootstrap.Modal(document.getElementById('modalClearAll'));
        const btnClearAll = document.getElementById('btnClearAll');
        if (btnClearAll) {
            btnClearAll.addEventListener('click', () => modalClearAll.show());
        }

        document.getElementById('btnClearAllConfirm').addEventListener('click', async () => {
            const btn = document.getElementById('btnClearAllConfirm'),
                orig = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Очистка...';
            const fd = new FormData();
            fd.append('_token', CSRF);
            const { ok, data } = await ajaxPost(routes.cacheClearAll, fd);
            modalClearAll.hide();
            showToast(data.message, ok && data.ok ? 'success' : 'error');
            btn.disabled = false;
            btn.innerHTML = orig;
            cacheStatsLoaded = false;
            loadCacheStats();
        });

        if (document.getElementById('tabCache').classList.contains('show')) {
            loadCacheStats();
        }

        const formPublicCache = document.getElementById('formPublicCache');
        if (formPublicCache) {
            formPublicCache.addEventListener('submit', async (e) => {
                e.preventDefault();
                const btn = document.getElementById('btnSavePublicCache');
                if (!btn) {return;}
                const orig = btn.innerHTML;
                setBtn(btn, true, orig);
                const { ok, data } = await ajaxPost(routes.savePublicCache, new FormData(e.target));
                showToast(data.message, ok && data.ok ? 'success' : 'error');
                setBtn(btn, false, orig);
            });
        }

        const btnFlushPC = document.getElementById('btnFlushPublicCache');
        btnFlushPC?.addEventListener('click', async () => {
            if (btnFlushPC.disabled) {return;}
            const orig = btnFlushPC.innerHTML;
            btnFlushPC.disabled = true;
            try {
                const res = await fetch(routes.flushPublicCache, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                    },
                });
                const data = await res.json();
                showToast(data.message || 'Готово', res.ok && data.ok ? 'success' : 'error');
            } catch (e) {
                showToast('Ошибка соединения', 'error');
            } finally {
                btnFlushPC.disabled = false;
                btnFlushPC.innerHTML = orig;
            }
        });
    }

    (function () {
        const hash = location.hash;
        if (!hash) {return;}
        const tabMap = {
            '#tabGeneral': '#settingsTabs a[href="#tabGeneral"]',
            '#tabEnv': '#settingsTabs a[href="#tabEnv"]',
            '#tabSeo': '#settingsTabs a[href="#tabSeo"]',
            '#tabCache': '#settingsTabs a[href="#tabCache"]',
        };
        const selector = tabMap[hash];
        if (!selector) {return;}
        const tabEl = document.querySelector(selector);
        if (tabEl) {bootstrap.Tab.getOrCreateInstance(tabEl).show();}
        if (hash === '#tabCache') {
            const cacheLink = document.querySelector('#settingsTabs a[href="#tabCache"]');
            if (cacheLink) {cacheLink.dispatchEvent(new Event('shown.bs.tab'));}
        }
    })();
})();
