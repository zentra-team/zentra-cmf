const csrf = document.querySelector('meta[name="csrf-token"]').content;

let copyLayoutId = null;
const modalCopy = new bootstrap.Modal(document.getElementById('modalCopy'));

document.querySelectorAll('.btn-copy').forEach((btn) => {
    btn.addEventListener('click', () => {
        copyLayoutId = btn.dataset.id;
        document.getElementById('copySourceTitle').textContent = btn.dataset.title;
        document.getElementById('copyTitle').value = btn.dataset.title + ' (копия)';
        document.getElementById('copyError').style.display = 'none';
        modalCopy.show();
        setTimeout(() => document.getElementById('copyTitle').select(), 300);
    });
});

document.getElementById('btnCopyConfirm').addEventListener('click', async () => {
    const title = document.getElementById('copyTitle').value.trim();
    const errEl = document.getElementById('copyError');
    const btn = document.getElementById('btnCopyConfirm');
    errEl.style.display = 'none';

    if (!title) {
        errEl.textContent = 'Введите название';
        errEl.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

    try {
        const res = await fetch(`/admin/layouts/${copyLayoutId}/copy`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': csrf,
            },
            body: JSON.stringify({ title }),
        });
        const data = await res.json();

        if (res.ok && data.ok) {
            window.location = data.redirect;
            return;
        }

        const msg = data.errors?.title?.[0] ?? data.message ?? 'Ошибка';
        errEl.textContent = msg;
        errEl.style.display = 'block';
    } catch (e) {
        errEl.textContent = 'Ошибка запроса. Попробуйте ещё раз.';
        errEl.style.display = 'block';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-copy me-1"></i>Копировать';
});

let deleteLayoutId = null;
const modalDelete = new bootstrap.Modal(document.getElementById('modalDelete'));

document.querySelectorAll('.btn-delete').forEach((btn) => {
    btn.addEventListener('click', () => {
        deleteLayoutId = btn.dataset.id;
        document.getElementById('deleteTitle').textContent = '«' + btn.dataset.title + '»';
        modalDelete.show();
    });
});

document.getElementById('btnDeleteConfirm').addEventListener('click', async () => {
    const btn = document.getElementById('btnDeleteConfirm');
    btn.disabled = true;

    const res = await fetch(`/admin/layouts/${deleteLayoutId}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': csrf },
    });
    const data = await res.json();

    btn.disabled = false;

    if (data.ok) {
        modalDelete.hide();
        document.getElementById('row-' + deleteLayoutId)?.remove();
        showToast('Макет удалён', 'success');
    } else {
        modalDelete.hide();
        showToast(data.message, 'error');
    }
});
