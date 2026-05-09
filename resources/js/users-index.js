(function () {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    document.getElementById('searchToggle').addEventListener('click', () => {
        const panel = document.getElementById('searchPanel');
        const icon = document.querySelector('#searchToggle .toggle-icon');
        const isOpen = panel.classList.contains('show');
        bootstrap.Collapse.getOrCreateInstance(panel).toggle();
        icon.style.transform = isOpen ? '' : 'rotate(180deg)';
    });
    if (cfg.hasSearchParams) {
        document.querySelector('#searchToggle .toggle-icon').style.transform = 'rotate(180deg)';
    }

    const changedGroups = new Map();
    document.querySelectorAll('.inline-group-select').forEach((sel) => {
        sel.addEventListener('change', function () {
            const userId = this.dataset.userId;
            if (this.value !== this.dataset.original) {
                changedGroups.set(userId, this.value);
            } else {
                changedGroups.delete(userId);
            }
            document.getElementById('btnSaveGroups').style.display = changedGroups.size ? '' : 'none';
        });
    });

    document.getElementById('btnSaveGroups')?.addEventListener('click', async () => {
        const btn = document.getElementById('btnSaveGroups');
        btn.disabled = true;
        const promises = [];
        changedGroups.forEach((groupId, userId) => {
            promises.push(
                fetch(`/admin/users/${userId}/group`, {
                    method: 'PATCH',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ group_id: groupId || null }),
                }),
            );
        });
        await Promise.all(promises);
        changedGroups.clear();
        btn.style.display = 'none';
        btn.disabled = false;
        document.querySelectorAll('.inline-group-select').forEach((sel) => {
            sel.dataset.original = sel.value;
        });
        showToast('Группы сохранены', 'success');
    });

    const deleteUserModal = new bootstrap.Modal(document.getElementById('deleteUserModal'));
    let deleteUserId = null;

    document.addEventListener('click', function (e) {
        const btn = e.target.closest('.btn-user-delete');
        if (!btn) {return;}
        deleteUserId = btn.dataset.userId;
        document.getElementById('deleteUserName').textContent = btn.dataset.userName;
        deleteUserModal.show();
    });

    document.getElementById('btnDeleteUserConfirm').addEventListener('click', async () => {
        const res = await fetch(`/admin/users/${deleteUserId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message, 'success');
            deleteUserModal.hide();
            document.querySelector(`tr[data-user-id="${deleteUserId}"]`)?.remove();
        } else {
            showToast(json.message, 'error');
        }
    });

    if (cfg.hasErrors) {
        new bootstrap.Tab(document.querySelector('[href="#tabCreate"]')).show();
    }
})();
