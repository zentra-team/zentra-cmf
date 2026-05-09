(function () {
    const cfg = window.ZentraConfig || {};
    const routes = cfg.routes || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    const dbSearchToggle = document.getElementById('searchToggleTables');
    if (dbSearchToggle) {
        dbSearchToggle.addEventListener('click', () => {
            const panel = document.getElementById('searchPanelTables');
            const chevron = document.querySelector('#searchToggleTables .ztr-db-search-chevron');
            const isOpen = panel.classList.contains('show');
            bootstrap.Collapse.getOrCreateInstance(panel).toggle();
            chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
        });
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]:not(#tablesBody *)').forEach((el) => {
        bootstrap.Tooltip.getOrCreateInstance(el, { trigger: 'hover' });
    });

    (function () {
        const tab = new URLSearchParams(location.search).get('tab');
        if (tab) {
            const el = document.getElementById('tab-' + tab);
            if (el) {new bootstrap.Tab(el).show();}
            history.replaceState(null, '', location.pathname);
        }
    })();

    let statsLoaded = false;

    async function loadStats() {
        const btn = document.getElementById('btnRefreshStats');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Обновить';
        }
        try {
            const res = await fetch(routes.stats);
            const json = await res.json();
            if (!json.ok) {
                showToast(json.message ?? 'Ошибка загрузки статистики', 'error');
                return;
            }

            document.getElementById('stat-db-size').textContent = json.db_size;
            document.getElementById('stat-pg-version').textContent = json.pg_version;
            document.getElementById('stat-connections').textContent = json.connections;

            const cacheEl = document.getElementById('stat-cache-hit');
            cacheEl.textContent = json.cache_hit;
            const cacheNum = parseFloat(json.cache_hit);
            if (!isNaN(cacheNum)) {
                cacheEl.style.color = cacheNum < 90 ? '#e06060' : cacheNum < 95 ? '#c8a840' : '#5cbf8c';
            }

            document.getElementById('stat-tables').textContent = json.table_count;
            document.getElementById('stat-rows').textContent = json.total_rows;

            const deadEl = document.getElementById('stat-dead');
            deadEl.textContent = json.dead_rows_fmt;
            deadEl.style.color = json.dead_rows > 100000 ? '#e06060' : json.dead_rows > 10000 ? '#c8a840' : '';

            const vacEl = document.getElementById('stat-vacuum');
            vacEl.textContent = json.tables_need_vacuum + ' табл.';
            vacEl.style.color = json.tables_need_vacuum > 0 ? '#c8a840' : '#5cbf8c';

            statsLoaded = true;
            if (btn) {showToast('Статистика обновлена', 'success');}
        } catch (e) {
            showToast('Ошибка загрузки статистики', 'error');
        } finally {
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="bi bi-arrow-clockwise me-1"></i>Обновить';
            }
        }
    }

    document.getElementById('btnRefreshStats').addEventListener('click', loadStats);
    document.getElementById('tab-overview').addEventListener('shown.bs.tab', () => {
        if (!statsLoaded) {loadStats();}
    });
    if (document.getElementById('tabOverview').classList.contains('show')) {loadStats();}

    let allTables = [];
    let tablesLoaded = false;

    async function loadTables() {
        document.getElementById('tablesLoading').classList.remove('ztr-db-hidden');
        document.getElementById('tablesContainer').classList.add('ztr-db-hidden');

        try {
            const res = await fetch(routes.tables);
            const json = await res.json();
            if (!json.ok) {
                showToast(json.message ?? 'Ошибка загрузки', 'error');
                return;
            }

            allTables = json.tables;
            tablesLoaded = true;
            renderTables();
        } catch (e) {
            showToast('Ошибка загрузки таблиц', 'error');
        } finally {
            document.getElementById('tablesLoading').classList.add('ztr-db-hidden');
            document.getElementById('tablesContainer').classList.remove('ztr-db-hidden');
        }
    }

    function renderTables() {
        const filter = document.getElementById('tableFilter').value.toLowerCase();
        const sort = document.getElementById('tableSort').value;

        const rows = allTables.filter((t) => t.name.includes(filter));

        rows.sort((a, b) => {
            switch (sort) {
                case 'name_asc':
                    return a.name.localeCompare(b.name);
                case 'name_desc':
                    return b.name.localeCompare(a.name);
                case 'total_bytes_desc':
                    return (b.total_bytes || 0) - (a.total_bytes || 0);
                case 'total_bytes_asc':
                    return (a.total_bytes || 0) - (b.total_bytes || 0);
                case 'live_rows_desc':
                    return (b.live_rows || 0) - (a.live_rows || 0);
                case 'live_rows_asc':
                    return (a.live_rows || 0) - (b.live_rows || 0);
                case 'dead_rows_desc':
                    return (b.dead_rows || 0) - (a.dead_rows || 0);
                case 'dead_rows_asc':
                    return (a.dead_rows || 0) - (b.dead_rows || 0);
                default:
                    return 0;
            }
        });

        const tbody = document.getElementById('tablesBody');
        tbody.innerHTML = '';

        rows.forEach((t) => {
            const deadRatio = t.live_rows > 0 ? t.dead_rows / t.live_rows : 0;
            const deadColor = deadRatio > 0.5 ? '#e06060' : deadRatio > 0.1 ? '#c8a840' : '';
            const vacType =
                t.vacuum_type === 'auto'
                    ? ' <span style="color:var(--ztr-text-muted);font-size:.7rem">(auto)</span>'
                    : '';
            const anaType =
                t.analyze_type === 'auto'
                    ? ' <span style="color:var(--ztr-text-muted);font-size:.7rem">(auto)</span>'
                    : '';

            tbody.insertAdjacentHTML(
                'beforeend',
                `
            <tr data-table="${t.name}">
                <td style="font-family:monospace;font-size:.78rem;font-weight:500;max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="${t.name}">${t.name}</td>
                <td class="text-center" style="font-size:.78rem">${Number(t.live_rows).toLocaleString('ru')}</td>
                <td class="text-center" style="font-size:.78rem;color:${deadColor || 'inherit'}">${Number(t.dead_rows).toLocaleString('ru')}</td>
                <td class="text-center" style="font-size:.78rem;font-weight:500">${t.total_size}</td>
                <td class="text-center" style="font-size:.75rem;color:var(--ztr-text-muted);white-space:nowrap">
                    ${t.last_vacuum ? t.last_vacuum + vacType : '<span style="color:#bf5c5c">—</span>'}
                </td>
                <td class="text-center" style="font-size:.75rem;color:var(--ztr-text-muted);white-space:nowrap">
                    ${t.last_analyze ? t.last_analyze + anaType : '<span style="color:var(--ztr-text-muted)">—</span>'}
                </td>
                <td class="text-center" style="white-space:nowrap">
                    <button class="btn btn-sm btn-secondary btn-tbl-maint py-0 px-1" style="font-size:.72rem"
                        data-type="vacuum" data-table="${t.name}"
                        data-bs-toggle="tooltip" data-bs-placement="auto" data-bs-html="true"
                        data-bs-title="<strong>VACUUM</strong> - убрать мусор<br><br>Когда вы удаляете или редактируете записи, старые данные не стираются сразу - они остаются как «мусор» и занимают место. VACUUM их убирает.<br><br>✅ Безопасно: сайт работает в обычном режиме.">V</button>
                    <button class="btn btn-sm btn-primary btn-tbl-maint py-0 px-1" style="font-size:.72rem"
                        data-type="vacuum_analyze" data-table="${t.name}"
                        data-bs-toggle="tooltip" data-bs-placement="auto" data-bs-html="true"
                        data-bs-title="<strong>VACUUM ANALYZE</strong> - убрать мусор + ускорить поиск<br><br>Делает всё то же, что VACUUM, и дополнительно обновляет статистику - база данных начинает лучше выполнять поисковые запросы.<br><br>✅ Рекомендуется как основная операция обслуживания. Безопасно.">VA</button>
                    <button class="btn btn-sm btn-secondary btn-tbl-maint py-0 px-1" style="font-size:.72rem"
                        data-type="analyze" data-table="${t.name}"
                        data-bs-toggle="tooltip" data-bs-placement="auto" data-bs-html="true"
                        data-bs-title="<strong>ANALYZE</strong> - ускорить поиск<br><br>Обновляет внутреннюю статистику таблицы, чтобы база данных быстрее находила нужные данные. Мусор при этом не убирается.<br><br>✅ Полезно после загрузки большого количества новых записей. Безопасно.">A</button>
                    <button class="btn btn-sm btn-secondary btn-tbl-maint py-0 px-1" style="font-size:.72rem"
                        data-type="reindex" data-table="${t.name}"
                        data-bs-toggle="tooltip" data-bs-placement="auto" data-bs-html="true"
                        data-bs-title="<strong>REINDEX TABLE</strong> - пересобрать индексы<br><br>Индексы - это как оглавление книги: помогают быстро находить записи. Со временем они засоряются и замедляют поиск.<br><br>⚠️ Запускайте если поиск по этой таблице заметно замедлился. Индексы временно недоступны во время выполнения.">RI</button>
                    <button class="btn btn-sm btn-danger btn-tbl-maint py-0 px-1" style="font-size:.72rem"
                        data-type="vacuum_full" data-table="${t.name}"
                        data-bs-toggle="tooltip" data-bs-placement="auto" data-bs-html="true"
                        data-bs-title="<strong>VACUUM FULL</strong> - глубокая очистка<br><br>Полностью перезаписывает таблицу и возвращает дисковое место. Помогает если таблица «раздулась» после массового удаления данных.<br><br>🚫 <strong>Таблица блокируется</strong> на всё время выполнения - запросы встанут в очередь. Запускайте только в нерабочее время.">VF</button>
                </td>
            </tr>`,
            );
        });

        document.getElementById('tablesSummary').textContent = `Показано: ${rows.length} из ${allTables.length} таблиц`;

        document.querySelectorAll('#tablesBody [data-bs-toggle="tooltip"]').forEach((el) => {
            bootstrap.Tooltip.getOrCreateInstance(el, { trigger: 'hover' });
        });
    }

    document.getElementById('tableFilter').addEventListener('input', renderTables);
    document.getElementById('tableSort').addEventListener('change', renderTables);
    document.getElementById('btnRefreshTables').addEventListener('click', loadTables);

    document.getElementById('tab-tables').addEventListener('shown.bs.tab', () => {
        if (!tablesLoaded) {loadTables();}
    });

    async function runMaintenance(type, table = null) {
        const progress = document.getElementById('maintProgress');
        const labels = {
            vacuum: 'VACUUM',
            vacuum_analyze: 'VACUUM ANALYZE',
            vacuum_full: 'VACUUM FULL',
            analyze: 'ANALYZE',
            reindex: 'REINDEX TABLE',
            reindex_db: 'REINDEX DATABASE',
            reset_sequences: 'Сброс последовательностей',
        };
        const label = labels[type] ?? type;

        document.getElementById('maintProgressText').textContent =
            `Выполняется ${label}${table ? ' для ' + table : ''}...`;
        progress.classList.remove('ztr-db-hidden');

        document
            .querySelectorAll('.btn-maintenance, .btn-maintenance-confirm, .btn-tbl-maint')
            .forEach((b) => (b.disabled = true));

        try {
            const body = { type };
            if (table) {body.table = table;}

            const res = await fetch(routes.maintenance, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(body),
            });
            const json = await res.json();

            if (json.ok) {
                showToast(json.message, 'success');
                statsLoaded = false;
                tablesLoaded = false;
                if (document.getElementById('tabOverview').classList.contains('active')) {loadStats();}
                if (document.getElementById('tabTables').classList.contains('active')) {loadTables();}
            } else {
                showToast(json.message ?? 'Ошибка', 'error');
            }
        } catch (e) {
            showToast('Ошибка соединения', 'error');
        } finally {
            progress.classList.add('ztr-db-hidden');
            document
                .querySelectorAll('.btn-maintenance, .btn-maintenance-confirm, .btn-tbl-maint')
                .forEach((b) => (b.disabled = false));
        }
    }

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-maintenance');
        if (btn) {runMaintenance(btn.dataset.type);}
    });

    const maintModal = new bootstrap.Modal(document.getElementById('maintModal'));
    let pendingMaintType = null;
    let pendingMaintTable = null;

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-maintenance-confirm');
        if (btn) {
            pendingMaintType = btn.dataset.type;
            pendingMaintTable = btn.dataset.table ?? null;
            document.getElementById('maintModalText').textContent = btn.dataset.confirm;
            maintModal.show();
        }
    });

    document.getElementById('btnMaintConfirm').addEventListener('click', () => {
        maintModal.hide();
        runMaintenance(pendingMaintType, pendingMaintTable);
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-tbl-maint');
        if (!btn) {return;}

        const type = btn.dataset.type;
        const table = btn.dataset.table;

        if (type === 'vacuum_full') {
            pendingMaintType = type;
            pendingMaintTable = table;
            document.getElementById('maintModalText').textContent =
                `VACUUM FULL для таблицы "${table}" - таблица будет заблокирована на время выполнения. Продолжить?`;
            maintModal.show();
        } else {
            runMaintenance(type, table);
        }
    });

    document.getElementById('btnCreateBackup').addEventListener('click', async () => {
        const filename = document.getElementById('backupFilename').value.trim();
        const saveLocal = document.getElementById('saveLocal').checked;

        if (!filename) {
            showToast('Введите имя файла', 'error');
            return;
        }

        const btn = document.getElementById('btnCreateBackup');
        const progress = document.getElementById('backupProgress');
        btn.disabled = true;
        progress.classList.remove('ztr-db-hidden');

        try {
            const res = await fetch(routes.backup, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ filename, save_local: saveLocal ? 1 : 0 }),
            });

            if (!res.ok) {
                const json = await res.json().catch(() => ({}));
                showToast(json.message ?? 'Ошибка создания резервной копии', 'error');
                return;
            }

            const blob = await res.blob();
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = filename.endsWith('.sql.gz') ? filename : filename + '.sql.gz';
            a.click();
            URL.revokeObjectURL(url);

            showToast('Резервная копия создана', 'success');
            if (saveLocal)
                {setTimeout(() => {
                    location.href = cfg.dbIndexUrl + '?tab=backups';
                }, 1200);}
        } catch {
            showToast('Ошибка соединения', 'error');
        } finally {
            btn.disabled = false;
            progress.classList.add('ztr-db-hidden');
        }
    });

    let restoreFilename = null;
    const restoreModal = new bootstrap.Modal(document.getElementById('restoreModal'));
    const restoreUploadModal = new bootstrap.Modal(document.getElementById('restoreUploadModal'));

    let deleteBackupName = null;
    const deleteBackupModal = new bootstrap.Modal(document.getElementById('deleteBackupModal'));

    const phpUploadLimitBytes = cfg.effectiveLimit;

    function formatFileSize(bytes) {
        if (bytes < 1024) {return bytes + ' Б';}
        if (bytes < 1048576) {return (bytes / 1024).toFixed(1) + ' КБ';}
        if (bytes < 1073741824) {return (bytes / 1048576).toFixed(1) + ' МБ';}
        return (bytes / 1073741824).toFixed(2) + ' ГБ';
    }

    document.getElementById('uploadBackupFile').addEventListener('change', function () {
        if (!this.files.length) {return;}
        const nameLower = this.files[0].name.toLowerCase();
        const allowed = ['.sql', '.sql.gz', '.dump'];
        if (!allowed.some((ext) => nameLower.endsWith(ext)) || nameLower.endsWith('.tar.gz')) {
            showToast(`Недопустимый формат файла. Разрешены только: .sql, .sql.gz, .dump`, 'error');
            this.value = '';
        }
    });

    document.getElementById('btnRestoreUpload').addEventListener('click', () => {
        const fileInput = document.getElementById('uploadBackupFile');
        if (!fileInput.files.length) {
            showToast('Выберите файл для восстановления', 'error');
            return;
        }
        const file = fileInput.files[0];
        const allowed = ['.sql', '.sql.gz', '.dump'];
        const nameLower = file.name.toLowerCase();
        if (!allowed.some((ext) => nameLower.endsWith(ext))) {
            showToast(`Недопустимый формат файла «${file.name}». Разрешены только: .sql, .sql.gz, .dump`, 'error');
            fileInput.value = '';
            return;
        }

        if (file.size > phpUploadLimitBytes) {
            showToast(
                `Файл (${formatFileSize(file.size)}) превышает лимит сервера (${cfg.phpUploadLimit}). ` +
                    `Увеличьте upload_max_filesize и post_max_size в php.ini.`,
                'error',
            );
            return;
        }

        document.getElementById('uploadFilename').textContent = file.name;
        restoreUploadModal.show();
    });

    document.getElementById('btnRestoreUploadConfirm').addEventListener('click', async () => {
        const btn = document.getElementById('btnRestoreUploadConfirm');
        const fileInput = document.getElementById('uploadBackupFile');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Восстановление...';
        restoreUploadModal.hide();

        const formData = new FormData();
        formData.append('backup_file', fileInput.files[0]);
        formData.append('_token', csrf);

        let res, text, json;
        try {
            res = await fetch(routes.restoreUpload, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: formData,
            });
            text = await res.text();
        } catch (err) {
            showToast('Не удалось отправить запрос на сервер', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i>Восстановить';
            return;
        }

        try {
            json = JSON.parse(text);
        } catch {
            showToast('Неожиданный ответ сервера (код ' + res.status + ')', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i>Восстановить';
            return;
        }

        if (json.ok) {
            showToast(json.message, 'success');
            fileInput.value = '';
        } else {
            const msg = json.message ?? json.errors?.backup_file?.[0] ?? 'Ошибка восстановления';
            showToast(msg, 'error');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i>Восстановить';
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-restore');
        if (btn) {
            restoreFilename = btn.dataset.name;
            document.getElementById('restoreFilename').textContent = restoreFilename;
            restoreModal.show();
        }

        const delBtn = e.target.closest('.btn-delete-backup');
        if (delBtn) {
            deleteBackupName = delBtn.dataset.name;
            document.getElementById('deleteBackupFilename').textContent = deleteBackupName;
            deleteBackupModal.show();
        }
    });

    document.getElementById('btnDeleteBackupConfirm').addEventListener('click', async () => {
        deleteBackupModal.hide();
        await deleteBackup(deleteBackupName);
    });

    document.getElementById('btnRestoreConfirm').addEventListener('click', async () => {
        const btn = document.getElementById('btnRestoreConfirm');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Восстановление...';

        const res = await fetch(`/admin/database/restore/${encodeURIComponent(restoreFilename)}`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const json = await res.json();

        if (json.ok) {
            showToast(json.message, 'success');
            restoreModal.hide();
        } else {
            showToast(json.message ?? 'Ошибка восстановления', 'error');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-arrow-counterclockwise me-1"></i>Восстановить';
    });

    async function deleteBackup(name) {
        const res = await fetch(`/admin/database/backup/${encodeURIComponent(name)}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message, 'success');
            document.querySelector(`tr[data-name="${name}"]`)?.remove();
        } else {
            showToast(json.message ?? 'Ошибка', 'error');
        }
    }
})();
