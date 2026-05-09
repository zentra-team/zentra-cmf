(function () {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const deleteGroupModal = new bootstrap.Modal(document.getElementById('deleteGroupModal'));
    let deleteGroupId = null;

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-group-delete');
        if (!btn) {return;}
        deleteGroupId = btn.dataset.groupId;
        document.getElementById('deleteGroupName').textContent = btn.dataset.groupName;
        deleteGroupModal.show();
    });

    document.getElementById('btnDeleteGroupConfirm').addEventListener('click', async () => {
        const res = await fetch(`/admin/users/groups/${deleteGroupId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message, 'success');
            deleteGroupModal.hide();
            document.querySelector(`[data-group-id="${deleteGroupId}"]`)?.remove();
        } else {
            showToast(json.message, 'error');
        }
    });

    document.addEventListener('click', async function (e) {
        const btn = e.target.closest('.btn-group-duplicate');
        if (!btn) {return;}
        btn.disabled = true;
        try {
            const res = await fetch(`/admin/users/groups/${btn.dataset.groupId}/duplicate`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            const json = await res.json();
            if (json.ok && json.redirect) {
                showToast(json.message ?? 'Группа скопирована', 'success');
                setTimeout(() => {
                    window.location.href = json.redirect;
                }, 400);
            } else {
                showToast(json.message ?? 'Не удалось скопировать', 'error');
                btn.disabled = false;
            }
        } catch {
            showToast('Ошибка соединения', 'error');
            btn.disabled = false;
        }
    });

    if (cfg.hasErrors) {
        new bootstrap.Tab(document.querySelector('[href="#tabCreate"]')).show();
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        bootstrap.Tooltip.getOrCreateInstance(el, { trigger: 'hover' });
    });
})();
