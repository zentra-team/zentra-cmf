document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const baseUrl = cfg.baseUrl;

    const sortableEl = document.getElementById('subfieldsSortable');
    if (sortableEl && cfg.canEdit) {
        Sortable.create(sortableEl, {
            handle: '.field-drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: async () => {
                const order = [...sortableEl.querySelectorAll('tr[data-idx]')].map((tr) => parseInt(tr.dataset.idx));
                try {
                    const res = await fetch(`${baseUrl}/reorder`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ order }),
                    });
                    if (!res.ok) {
                        showToast('Не удалось сохранить порядок', 'error');
                        return;
                    }

                    [...sortableEl.querySelectorAll('tr[data-idx]')].forEach((tr, newIdx) => {
                        updateRowIdx(tr, newIdx);
                    });
                    showToast('Порядок сохранён', 'success');
                } catch (err) {
                    showToast('Сетевая ошибка: ' + (err.message ?? ''), 'error');
                }
            },
        });
    }

    function updateRowIdx(tr, newIdx) {
        tr.dataset.idx = newIdx;
        tr.id = `srow-${newIdx}`;
        tr.querySelectorAll('[data-idx]').forEach((el) => (el.dataset.idx = newIdx));
        const numCell = tr.querySelector('.ztr-rubrics-fields-small');
        if (numCell) {numCell.textContent = newIdx + 1;}
    }

    const btnSave = document.getElementById('btnSaveSubfields');
    if (btnSave) {
        btnSave.addEventListener('click', async () => {
            if (btnSave.disabled) {return;}
            const rows = [...document.querySelectorAll('#subfieldsSortable tr[data-idx]')];
            btnSave.disabled = true;

            const promises = rows.map((tr) => {
                const idx = tr.dataset.idx;
                return fetch(`${baseUrl}/${idx}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({
                        title: tr.querySelector('.field-title')?.value.trim(),
                    }),
                });
            });

            try {
                const results = await Promise.all(promises);
                const allOk = results.every((r) => r.ok);
                showToast(allOk ? 'Названия сохранены' : 'Часть подполей не сохранилась', allOk ? 'success' : 'error');
            } catch (err) {
                showToast('Сетевая ошибка: ' + (err.message ?? ''), 'error');
            } finally {
                btnSave.disabled = false;
            }
        });
    }

    const btnAdd = document.getElementById('btnAddSubfield');
    if (btnAdd) {
        btnAdd.addEventListener('click', async () => {
            const title = document.getElementById('newSubfieldTitle').value.trim();
            const type = document.getElementById('newSubfieldType').value;
            const errEl = document.getElementById('addSubfieldError');
            errEl.style.display = 'none';

            if (!title) {
                errEl.textContent = 'Введите название подполя';
                errEl.style.display = 'block';
                return;
            }

            btnAdd.disabled = true;

            try {
                const res = await fetch(baseUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ title, type }),
                });
                const data = await res.json();
                if (res.ok && data.ok) {
                    showToast('Подполе добавлено', 'success');
                    window.location.reload();
                    return;
                }
                const msg = data.errors?.title?.[0] ?? data.message ?? 'Ошибка';
                errEl.textContent = msg;
                errEl.style.display = 'block';
            } catch {
                errEl.textContent = 'Ошибка запроса';
                errEl.style.display = 'block';
            }

            btnAdd.disabled = false;
        });
    }

    document.querySelectorAll('.field-type-link').forEach((link) => {
        link.addEventListener('click', () => {
            const idx = link.dataset.idx;
            link.style.display = 'none';
            const sel = document.getElementById(`stype-select-${idx}`);
            const ok = document.getElementById(`stype-ok-${idx}`);
            if (sel) {sel.style.display = 'inline-block';}
            if (ok) {ok.style.display = 'inline-block';}
        });
    });

    document.querySelectorAll('.field-type-ok').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (btn.disabled) {return;}
            const idx = btn.dataset.idx;
            const sel = document.getElementById(`stype-select-${idx}`);
            const label = document.getElementById(`stype-label-${idx}`);

            btn.disabled = true;

            try {
                const res = await fetch(`${baseUrl}/${idx}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ type: sel.value }),
                });
                const data = await res.json().catch(() => ({}));
                if (data.ok) {
                    label.textContent = sel.options[sel.selectedIndex].text;
                    showToast('Тип изменён', 'success');
                } else {
                    showToast(data.message ?? 'Ошибка', 'error');
                }
            } catch (err) {
                showToast('Сетевая ошибка: ' + (err.message ?? ''), 'error');
            } finally {
                sel.style.display = 'none';
                btn.style.display = 'none';
                label.style.display = 'inline';
                btn.disabled = false;
            }
        });
    });

    let aliasIdx = null;
    const modalAlias = new bootstrap.Modal(document.getElementById('modalAlias'));

    document.querySelectorAll('.btn-change-alias').forEach((btn) => {
        btn.addEventListener('click', () => {
            aliasIdx = btn.dataset.idx;
            document.getElementById('newAliasInput').value = btn.dataset.alias;
            document.getElementById('aliasError').style.display = 'none';
            modalAlias.show();
            setTimeout(() => document.getElementById('newAliasInput').select(), 300);
        });
    });

    document.getElementById('btnAliasConfirm').addEventListener('click', async () => {
        const alias = document.getElementById('newAliasInput').value.trim();
        const errEl = document.getElementById('aliasError');
        const btn = document.getElementById('btnAliasConfirm');
        errEl.style.display = 'none';

        if (!alias) {
            errEl.textContent = 'Введите алиас';
            errEl.style.display = 'block';
            return;
        }

        btn.disabled = true;

        try {
            const res = await fetch(`${baseUrl}/${aliasIdx}/alias`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ alias }),
            });
            const data = await res.json();
            if (res.ok && data.ok) {
                document.querySelector(`.field-alias-text[data-idx="${aliasIdx}"]`).textContent = data.alias;
                document.querySelector(`.btn-change-alias[data-idx="${aliasIdx}"]`).dataset.alias = data.alias;
                modalAlias.hide();
                showToast('Алиас изменён', 'success');
            } else {
                const msg = data.errors?.alias?.[0] ?? data.message ?? 'Ошибка';
                errEl.textContent = msg;
                errEl.style.display = 'block';
            }
        } catch {
            errEl.textContent = 'Ошибка запроса';
            errEl.style.display = 'block';
        }

        btn.disabled = false;
    });

    let deleteIdx = null;
    const modalDelete = new bootstrap.Modal(document.getElementById('modalDeleteSubfield'));

    document.querySelectorAll('.btn-delete-subfield').forEach((btn) => {
        btn.addEventListener('click', () => {
            deleteIdx = btn.dataset.idx;
            document.getElementById('deleteSubfieldTitle').textContent = '«' + btn.dataset.title + '»';
            modalDelete.show();
        });
    });

    document.getElementById('btnDeleteSubfieldConfirm').addEventListener('click', async () => {
        const btn = document.getElementById('btnDeleteSubfieldConfirm');
        if (btn.disabled) {return;}
        btn.disabled = true;

        try {
            const res = await fetch(`${baseUrl}/${deleteIdx}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json().catch(() => ({}));

            modalDelete.hide();

            if (data.ok) {
                showToast('Подполе удалено', 'success');
                window.location.reload();
                return;
            }
            showToast(data.message ?? 'Ошибка', 'error');
        } catch (err) {
            showToast('Сетевая ошибка: ' + (err.message ?? ''), 'error');
        } finally {
            btn.disabled = false;
        }
    });
});
