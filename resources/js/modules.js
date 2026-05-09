(function () {
    const cfg = window.ZentraConfig || {};
    const routes = cfg.routes || {};
    const CSRF = document.querySelector('meta[name="csrf-token"]').content;

    async function apiPost(url, data) {
        const fd = new FormData();
        fd.append('_token', CSRF);
        Object.entries(data).forEach(([k, v]) => fd.append(k, v));

        const res = await fetch(url, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: fd,
        });

        const text = await res.text();
        let json;
        try {
            json = JSON.parse(text);
        } catch {
            json = { ok: false, message: text || 'Неизвестная ошибка' };
        }
        return { ok: res.ok, data: json };
    }

    function setSpinner(btn, loading, label) {
        label = label || '';
        btn.disabled = loading;
        if (loading) {
            btn.dataset.orig = btn.innerHTML;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>' + label;
        } else {
            btn.innerHTML = btn.dataset.orig || btn.innerHTML;
        }
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        bootstrap.Tooltip.getOrCreateInstance(el, { trigger: 'hover' });
    });

    document.querySelectorAll('.btn-copy-tag').forEach((btn) => {
        btn.addEventListener('click', () => {
            navigator.clipboard.writeText(btn.dataset.tag).then(() => {
                showToast('Тег скопирован', 'success');
            });
        });
    });

    document.querySelectorAll('.toggle-module').forEach((toggle) => {
        toggle.addEventListener('change', async () => {
            const sysName = toggle.dataset.sysName;
            const active = toggle.checked;

            const { ok, data } = await apiPost(routes.toggle, {
                sys_name: sysName,
                active: active ? 1 : 0,
            });
            showToast(data.message, ok && data.ok ? 'success' : 'error');
            if (!ok || !data.ok) {toggle.checked = !active;}
        });
    });

    document.querySelectorAll('.btn-install').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const sysName = btn.dataset.sysName;
            setSpinner(btn, true, 'Установка...');

            const { ok, data } = await apiPost(routes.install, { sys_name: sysName });
            showToast(data.message, ok && data.ok ? 'success' : 'error');
            setSpinner(btn, false);

            if (ok && data.ok) {
                document.getElementById('avail_' + sysName)?.remove();
                setTimeout(() => location.reload(), 1200);
            }
        });
    });

    let reinstallTarget = null;
    const modalReinstall = new bootstrap.Modal(document.getElementById('modalReinstall'));

    document.querySelectorAll('.btn-reinstall').forEach((btn) => {
        btn.addEventListener('click', () => {
            reinstallTarget = btn.dataset.sysName;
            document.getElementById('reinstallText').textContent =
                `Переустановить модуль «${btn.dataset.name}» v${btn.dataset.version}? Данные в таблицах модуля сохранятся.`;
            modalReinstall.show();
        });
    });

    document.getElementById('btnReinstallConfirm').addEventListener('click', async () => {
        if (!reinstallTarget) {return;}
        const btn = document.getElementById('btnReinstallConfirm');
        setSpinner(btn, true, 'Переустановка...');

        const { ok, data } = await apiPost(routes.reinstall, { sys_name: reinstallTarget });
        modalReinstall.hide();
        showToast(data.message, ok && data.ok ? 'success' : 'error');
        setSpinner(btn, false);
        if (ok && data.ok) {setTimeout(() => location.reload(), 1200);}
    });

    let deleteTarget = null;
    const modalDelete = new bootstrap.Modal(document.getElementById('modalDelete'));

    document.querySelectorAll('.btn-delete').forEach((btn) => {
        btn.addEventListener('click', () => {
            deleteTarget = btn.dataset.sysName;
            document.getElementById('deleteText').innerHTML =
                `Удалить модуль <strong>«${btn.dataset.name}»</strong>?<br>
                 <span class="text-danger" style="font-size:.8125rem">Все таблицы модуля будут удалены. Файлы на диске останутся.</span>`;
            modalDelete.show();
        });
    });

    document.getElementById('btnDeleteConfirm').addEventListener('click', async () => {
        if (!deleteTarget) {return;}
        const btn = document.getElementById('btnDeleteConfirm');
        setSpinner(btn, true, 'Удаление...');

        const { ok, data } = await apiPost(routes.uninstall, { sys_name: deleteTarget });
        modalDelete.hide();
        showToast(data.message, ok && data.ok ? 'success' : 'error');
        setSpinner(btn, false);
        if (ok && data.ok) {
            setTimeout(() => location.reload(), 1200);
        }
    });

    const modalUpload = new bootstrap.Modal(document.getElementById('modalUpload'));
    const modalInstallUpload = new bootstrap.Modal(document.getElementById('modalInstallAfterUpload'));

    document.getElementById('modalInstallAfterUpload').addEventListener('hidden.bs.modal', () => {
        if (uploadedModuleInfo) {
            uploadedModuleInfo = null;
            location.reload();
        }
    });
    const uploadDropZone = document.getElementById('uploadDropZone');
    const uploadFileInput = document.getElementById('uploadFileInput');
    const uploadFileName = document.getElementById('uploadFileName');
    const btnUploadConfirm = document.getElementById('btnUploadConfirm');
    let selectedUploadFile = null;
    let uploadedModuleInfo = null;

    document.getElementById('btnUploadModule').addEventListener('click', () => modalUpload.show());

    uploadDropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadDropZone.style.background = 'rgba(100,80,200,.1)';
    });
    uploadDropZone.addEventListener('dragleave', () => {
        uploadDropZone.style.background = '';
    });
    uploadDropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadDropZone.style.background = '';
        const file = e.dataTransfer.files[0];
        if (file) {setUploadFile(file);}
    });
    uploadFileInput.addEventListener('change', () => {
        if (uploadFileInput.files[0]) {setUploadFile(uploadFileInput.files[0]);}
    });
    uploadDropZone.addEventListener('click', (e) => {
        if (e.target !== uploadFileInput.labels?.[0]) {uploadFileInput.click();}
    });

    function setUploadFile(file) {
        selectedUploadFile = file;
        uploadFileName.textContent = file.name + ' (' + (file.size / 1048576).toFixed(1) + ' МБ)';
        btnUploadConfirm.disabled = false;
    }

    btnUploadConfirm.addEventListener('click', async () => {
        if (!selectedUploadFile) {return;}
        setSpinner(btnUploadConfirm, true, 'Загрузка...');
        document.getElementById('uploadProgress').classList.remove('d-none');

        const fd = new FormData();
        fd.append('_token', CSRF);
        fd.append('archive', selectedUploadFile);

        try {
            const res = await fetch(routes.upload, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                body: fd,
            });
            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch {
                data = { ok: false, message: text };
            }

            document.getElementById('uploadProgress').classList.add('d-none');
            setSpinner(btnUploadConfirm, false);

            if (!res.ok || !data.ok) {
                showToast(data.message || 'Ошибка загрузки', 'error');
                return;
            }

            modalUpload.hide();
            uploadedModuleInfo = data;

            if (!data.existing) {
                document.getElementById('installAfterUploadTitle').textContent = 'Установить модуль';
                document.getElementById('installAfterUploadText').innerHTML =
                    `Установить <strong>«${data.name}»</strong> v${data.version}?`;
                document.getElementById('btnInstallAfterUpload').textContent = 'Установить';
                document.getElementById('btnInstallAfterUpload').className = 'btn btn-primary btn-sm';
            } else if (data.existing.is_newer) {
                document.getElementById('installAfterUploadTitle').textContent = 'Обновить модуль';
                document.getElementById('installAfterUploadText').innerHTML =
                    `Обновить <strong>«${data.name}»</strong> с v${data.existing.version} до v${data.version}?`;
                document.getElementById('btnInstallAfterUpload').textContent = 'Обновить';
                document.getElementById('btnInstallAfterUpload').className = 'btn btn-warning btn-sm';
            } else {
                document.getElementById('installAfterUploadTitle').textContent = 'Переустановить модуль';
                document.getElementById('installAfterUploadText').innerHTML =
                    `Модуль <strong>«${data.name}»</strong> уже установлен (v${data.existing.version}). Переустановить?`;
                document.getElementById('btnInstallAfterUpload').textContent = 'Переустановить';
                document.getElementById('btnInstallAfterUpload').className = 'btn btn-secondary btn-sm';
            }

            modalInstallUpload.show();
        } catch (e) {
            document.getElementById('uploadProgress').classList.add('d-none');
            setSpinner(btnUploadConfirm, false);
            showToast('Ошибка загрузки: ' + e.message, 'error');
        }
    });

    document.getElementById('btnInstallAfterUpload').addEventListener('click', async () => {
        if (!uploadedModuleInfo) {return;}
        const btn = document.getElementById('btnInstallAfterUpload');
        const action = uploadedModuleInfo.existing ? 'reinstall' : 'install';
        const url = action === 'install' ? routes.install : routes.reinstall;

        setSpinner(btn, true, 'Выполняется...');
        const { ok, data } = await apiPost(url, { sys_name: uploadedModuleInfo.sys_name });
        modalInstallUpload.hide();
        showToast(data.message, ok && data.ok ? 'success' : 'error');
        setSpinner(btn, false);
        if (ok && data.ok) {setTimeout(() => location.reload(), 1200);}
    });

    const modalUpdate = new bootstrap.Modal(document.getElementById('modalUpdate'));
    let updateTarget = null;

    async function checkModuleUpdates() {
        const installedWithGithub = document.querySelectorAll('.update-badge');
        if (!installedWithGithub.length) {return;}

        try {
            const res = await fetch(routes.checkUpdates, {
                headers: { Accept: 'application/json' },
            });
            const updates = await res.json();

            Object.entries(updates).forEach(([sysName, info]) => {
                const badge = document.getElementById('upd_' + sysName);
                if (badge) {
                    badge.textContent = '\u2191 v' + info.version;
                    badge.classList.remove('d-none');
                    badge.dataset.newVersion = info.version;
                    badge.dataset.tag = info.tag;
                    badge.dataset.changelog = info.changelog || '';
                    badge.dataset.publishedAt = info.published_at || '';
                }
            });
        } catch {}
    }

    document.addEventListener('click', (e) => {
        const badge = e.target.closest('.update-badge');
        if (!badge) {return;}

        updateTarget = {
            sysName: badge.dataset.sysName,
            name: badge.dataset.name,
            repo: badge.dataset.repo,
            tag: badge.dataset.tag,
            current: badge.dataset.current,
            newVersion: badge.dataset.newVersion,
            changelog: badge.dataset.changelog,
        };

        document.getElementById('updateText').innerHTML =
            `Обновить <strong>«${updateTarget.name}»</strong> с v${updateTarget.current} до v${updateTarget.newVersion}?`;

        const changelogBlock = document.getElementById('updateChangelog');
        const changelogText = document.getElementById('updateChangelogText');
        if (updateTarget.changelog) {
            changelogText.textContent = updateTarget.changelog;
            changelogBlock.classList.remove('d-none');
        } else {
            changelogBlock.classList.add('d-none');
        }

        modalUpdate.show();
    });

    document.getElementById('btnUpdateConfirm').addEventListener('click', async () => {
        if (!updateTarget) {return;}
        const btn = document.getElementById('btnUpdateConfirm');
        setSpinner(btn, true, 'Скачивание...');

        const { ok, data } = await apiPost(routes.update, {
            sys_name: updateTarget.sysName,
            repo: updateTarget.repo,
            tag: updateTarget.tag,
        });

        modalUpdate.hide();
        showToast(data.message, ok && data.ok ? 'success' : 'error');
        setSpinner(btn, false);
        if (ok && data.ok) {setTimeout(() => location.reload(), 1500);}
    });

    document.addEventListener('DOMContentLoaded', () => {
        checkModuleUpdates();
    });
})();
