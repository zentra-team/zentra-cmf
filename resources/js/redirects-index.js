(function () {
    const cfg = window.ZentraConfig || {};
    const csrf = cfg.csrf;

    document.querySelectorAll('.ztr-redirects-search-toggle').forEach((el) => {
        const targetSel = el.getAttribute('data-target');
        if (!targetSel) {return;}
        const target = document.querySelector(targetSel);
        if (!target) {return;}

        const collapse = bootstrap.Collapse.getOrCreateInstance(target, { toggle: false });
        target.addEventListener('shown.bs.collapse', () => el.setAttribute('aria-expanded', 'true'));
        target.addEventListener('hidden.bs.collapse', () => el.setAttribute('aria-expanded', 'false'));
        el.setAttribute('aria-expanded', target.classList.contains('show') ? 'true' : 'false');
        el.addEventListener('click', () => collapse.toggle());
    });

    const bulkBar = document.getElementById('bulkBar');
    const bulkCount = document.getElementById('bulkCount');
    const checkAll = document.getElementById('checkAll');
    const rowChecks = () => Array.from(document.querySelectorAll('.row-check'));

    function syncBulkBar() {
        const selected = rowChecks().filter((c) => c.checked);
        if (bulkCount) {bulkCount.textContent = String(selected.length);}
        if (bulkBar) {bulkBar.classList.toggle('d-none', selected.length === 0);}

        const all = rowChecks();
        if (checkAll) {
            checkAll.checked = all.length > 0 && selected.length === all.length;
            checkAll.indeterminate = selected.length > 0 && selected.length < all.length;
        }
    }

    if (checkAll) {
        checkAll.addEventListener('change', () => {
            rowChecks().forEach((c) => {
                c.checked = checkAll.checked;
            });
            syncBulkBar();
        });
    }
    rowChecks().forEach((c) => c.addEventListener('change', syncBulkBar));

    document.getElementById('btnBulkClear')?.addEventListener('click', () => {
        rowChecks().forEach((c) => {
            c.checked = false;
        });
        if (checkAll) {checkAll.checked = false;}
        syncBulkBar();
    });

    const bulkConfirmModalEl = document.getElementById('bulkConfirmModal');
    const bulkConfirmModal = bulkConfirmModalEl ? new bootstrap.Modal(bulkConfirmModalEl) : null;
    const bulkConfirmText = document.getElementById('bulkConfirmText');
    const bulkConfirmWarn = document.getElementById('bulkConfirmWarn');
    const btnBulkConfirm = document.getElementById('btnBulkConfirm');
    let pendingBulkAction = null;

    document.querySelectorAll('[data-bulk-action]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const ids = rowChecks()
                .filter((c) => c.checked)
                .map((c) => c.value);
            if (ids.length === 0) {return;}

            pendingBulkAction = { action: btn.getAttribute('data-bulk-action'), ids };
            const labels = {
                activate: `Активировать ${ids.length} редирект(ов)?`,
                deactivate: `Деактивировать ${ids.length} редирект(ов)?`,
                delete: `Удалить ${ids.length} редирект(ов)?`,
            };
            bulkConfirmText.textContent = labels[pendingBulkAction.action] || '';
            bulkConfirmWarn.classList.toggle('d-none', pendingBulkAction.action !== 'delete');
            btnBulkConfirm.className =
                'btn btn-sm ' + (pendingBulkAction.action === 'delete' ? 'btn-danger' : 'btn-primary');
            bulkConfirmModal.show();
        });
    });

    btnBulkConfirm?.addEventListener('click', async () => {
        if (!pendingBulkAction) {return;}
        btnBulkConfirm.disabled = true;
        try {
            const res = await fetch(cfg.bulkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(pendingBulkAction),
            });
            const data = await res.json();
            if (!res.ok) {
                showToast(data.message || 'Ошибка', 'error');
                return;
            }
            bulkConfirmModal.hide();
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 600);
        } catch (e) {
            showToast('Ошибка соединения', 'error');
        } finally {
            btnBulkConfirm.disabled = false;
        }
    });

    const deleteModalEl = document.getElementById('deleteRedirectModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;
    const delFrom = document.getElementById('delFrom');
    const delTo = document.getElementById('delTo');
    let pendingDeleteId = null;

    document.querySelectorAll('.btn-row-delete').forEach((btn) => {
        btn.addEventListener('click', () => {
            pendingDeleteId = btn.getAttribute('data-id');
            if (delFrom) {delFrom.textContent = btn.getAttribute('data-from') || '';}
            if (delTo) {delTo.textContent = btn.getAttribute('data-to') || '';}
            deleteModal?.show();
        });
    });

    document.getElementById('btnDelConfirm')?.addEventListener('click', async () => {
        if (!pendingDeleteId) {return;}
        const btn = document.getElementById('btnDelConfirm');
        btn.disabled = true;
        try {
            const url = cfg.destroyTpl.replace('/0', '/' + pendingDeleteId);
            const res = await fetch(url, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json();
            if (!res.ok) {
                showToast(data.message || 'Ошибка', 'error');
                return;
            }
            deleteModal.hide();
            showToast(data.message, 'success');
            const row = document.querySelector(`tr[data-redirect-id="${pendingDeleteId}"]`);
            if (row) {row.remove();}
        } catch (e) {
            showToast('Ошибка соединения', 'error');
        } finally {
            btn.disabled = false;
        }
    });

    const btnInspect = document.getElementById('btnInspect');
    const inspectorUrl = document.getElementById('inspectorUrl');
    const inspectorResult = document.getElementById('inspectorResult');

    btnInspect?.addEventListener('click', async () => {
        const url = (inspectorUrl?.value || '').trim();
        if (!url) {
            inspectorResult.className = 'ztr-redirects-inspect-result ztr-error';
            inspectorResult.textContent = 'Введите URL для проверки';
            inspectorResult.classList.remove('d-none');
            return;
        }
        btnInspect.disabled = true;
        try {
            const res = await fetch(btnInspect.getAttribute('data-url'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ url }),
            });
            const data = await res.json();
            if (!res.ok) {
                inspectorResult.className = 'ztr-redirects-inspect-result ztr-error';
                inspectorResult.textContent = data.message || 'Ошибка';
            } else if (!data.matched) {
                inspectorResult.className = 'ztr-redirects-inspect-result ztr-empty';
                inspectorResult.textContent = data.message || 'Совпадений не найдено';
            } else {
                inspectorResult.className = 'ztr-redirects-inspect-result ztr-success';
                inspectorResult.innerHTML =
                    `<div><strong>Сработает:</strong> ${data.type} → <code>${escapeHtml(data.to)}</code></div>` +
                    `<div class="text-muted small mt-1">Цепочка из ${data.hops} редирект(ов).</div>`;
            }
            inspectorResult.classList.remove('d-none');
        } catch (e) {
            inspectorResult.className = 'ztr-redirects-inspect-result ztr-error';
            inspectorResult.textContent = 'Ошибка соединения';
            inspectorResult.classList.remove('d-none');
        } finally {
            btnInspect.disabled = false;
        }
    });

    function escapeHtml(s) {
        return String(s).replace(
            /[&<>"']/g,
            (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c],
        );
    }
document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        bootstrap.Tooltip.getOrCreateInstance(el, { trigger: 'hover' });
    });
})();
