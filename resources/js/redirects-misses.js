(function () {
    const cfg = window.ZentraConfig || {};
    const csrf = cfg.csrf;

    document.querySelectorAll('.btn-miss-delete').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (btn.disabled) {return;}
            btn.disabled = true;
            try {
                const res = await fetch(btn.dataset.url, {
                    method: 'DELETE',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                });
                const data = await res.json();
                if (!res.ok) {
                    showToast(data.message || 'Ошибка', 'error');
                    return;
                }
                showToast(data.message, 'success');
                btn.closest('tr')?.remove();
            } catch (e) {
                showToast('Ошибка соединения', 'error');
            } finally {
                btn.disabled = false;
            }
        });
    });

    const btnClearAll = document.getElementById('btnClearAllMisses');
    const modalEl = document.getElementById('clearMissesModal');
    const modal = modalEl ? new bootstrap.Modal(modalEl) : null;

    btnClearAll?.addEventListener('click', () => modal?.show());

    document.getElementById('btnClearAllMissesConfirm')?.addEventListener('click', async () => {
        const btn = document.getElementById('btnClearAllMissesConfirm');
        btn.disabled = true;
        try {
            const res = await fetch(btnClearAll.dataset.url, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json();
            if (!res.ok) {
                showToast(data.message || 'Ошибка', 'error');
                return;
            }
            modal.hide();
            showToast(data.message, 'success');
            setTimeout(() => location.reload(), 400);
        } catch (e) {
            showToast('Ошибка соединения', 'error');
        } finally {
            btn.disabled = false;
        }
    });
})();
