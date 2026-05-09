(function () {
    'use strict';

    const cfg = window.ZentraConfig || {};

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        new bootstrap.Tooltip(el);
    });

    document.querySelectorAll('.ztr-api-search-toggle').forEach((el) => {
        el.addEventListener('click', () => {
            const target = document.querySelector(el.dataset.target);
            if (!target) {return;}
            const collapse = bootstrap.Collapse.getOrCreateInstance(target);
            collapse.toggle();
            el.querySelector('.bi-chevron-down')?.classList.toggle('bi-chevron-up');
        });
    });

    const checkAll = document.getElementById('checkAll');
    const bulkBar = document.getElementById('bulkBar');
    const bulkCount = document.getElementById('bulkCount');

    function getChecked() {
        return Array.from(document.querySelectorAll('.row-check:checked')).map((c) => parseInt(c.value, 10));
    }

    function refreshBulk() {
        const ids = getChecked();
        if (ids.length === 0) {
            bulkBar?.classList.add('d-none');
        } else {
            bulkBar?.classList.remove('d-none');
            if (bulkCount) {bulkCount.textContent = String(ids.length);}
        }
    }

    checkAll?.addEventListener('change', () => {
        document.querySelectorAll('.row-check').forEach((c) => {
            c.checked = checkAll.checked;
        });
        refreshBulk();
    });
    document.querySelectorAll('.row-check').forEach((c) => {
        c.addEventListener('change', refreshBulk);
    });

    document.getElementById('btnBulkClear')?.addEventListener('click', () => {
        document.querySelectorAll('.row-check').forEach((c) => {
            c.checked = false;
        });
        if (checkAll) {checkAll.checked = false;}
        refreshBulk();
    });

    let pendingBulkAction = null;
    document.querySelectorAll('[data-bulk-action]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const ids = getChecked();
            if (ids.length === 0) {return;}

            pendingBulkAction = btn.dataset.bulkAction;

            const labels = {
                activate: 'Активировать',
                deactivate: 'Деактивировать',
                delete: 'Удалить',
            };
            document.getElementById('bulkConfirmText').textContent =
                `${labels[pendingBulkAction]} токенов: ${ids.length}?`;
            const warn = document.getElementById('bulkConfirmWarn');
            warn.classList.toggle('d-none', pendingBulkAction !== 'delete');

            const confirmBtn = document.getElementById('btnBulkConfirm');
            confirmBtn.className = pendingBulkAction === 'delete' ? 'btn btn-danger btn-sm' : 'btn btn-primary btn-sm';
            confirmBtn.textContent = labels[pendingBulkAction];

            const modal = new bootstrap.Modal(document.getElementById('bulkConfirmModal'));
            modal.show();
        });
    });

    document.getElementById('btnBulkConfirm')?.addEventListener('click', async () => {
        if (!pendingBulkAction) {return;}
        const ids = getChecked();
        if (ids.length === 0) {return;}

        const btn = document.getElementById('btnBulkConfirm');
        btn.disabled = true;

        try {
            const res = await fetch(cfg.bulkUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': cfg.csrf,
                    Accept: 'application/json',
                },
                body: JSON.stringify({ action: pendingBulkAction, ids }),
            });
            const data = await res.json();
            if (!res.ok || !data.ok) {throw new Error(data?.message || 'Ошибка');}

            window.showToast?.(data.message || 'Готово', 'success');
            setTimeout(() => location.reload(), 500);
        } catch (e) {
            window.showToast?.(e.message, 'danger');
            btn.disabled = false;
        }
    });

    let pendingDelId = null;
    document.querySelectorAll('.btn-row-delete').forEach((btn) => {
        btn.addEventListener('click', () => {
            pendingDelId = parseInt(btn.dataset.id, 10);
            document.getElementById('delTokenName').textContent = btn.dataset.name || '';
            const modal = new bootstrap.Modal(document.getElementById('deleteTokenModal'));
            modal.show();
        });
    });

    document.getElementById('btnDelConfirm')?.addEventListener('click', async () => {
        if (!pendingDelId) {return;}
        const url = cfg.destroyTpl.replace('/0', '/' + pendingDelId);
        const btn = document.getElementById('btnDelConfirm');
        btn.disabled = true;

        try {
            const res = await fetch(url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': cfg.csrf, Accept: 'application/json' },
            });
            const data = await res.json();
            if (!res.ok || !data.ok) {throw new Error(data?.message || 'Ошибка');}

            window.showToast?.(data.message || 'Удалено', 'success');
            const row = document.querySelector(`tr[data-token-id="${pendingDelId}"]`);
            row?.remove();
            bootstrap.Modal.getInstance(document.getElementById('deleteTokenModal'))?.hide();

            if (!document.querySelector('tbody tr')) {
                setTimeout(() => location.reload(), 400);
            }
        } catch (e) {
            window.showToast?.(e.message, 'danger');
        } finally {
            btn.disabled = false;
            pendingDelId = null;
        }
    });
})();
