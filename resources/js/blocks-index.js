const csrf = document.querySelector('meta[name="csrf-token"]').content;

function esc(str) {
    return String(str ?? '').replace(
        /[&<>"']/g,
        (m) =>
            ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;',
            })[m],
    );
}

// ── Create form: alias auto-generate ─────────────────────────────────────
const createTitle = document.getElementById('createTitle');
const createAlias = document.getElementById('createAlias');
const createTagPreview = document.getElementById('createTagPreview');

function toAlias(str) {
    const map = {
        а: 'a',
        б: 'b',
        в: 'v',
        г: 'g',
        д: 'd',
        е: 'e',
        ё: 'yo',
        ж: 'zh',
        з: 'z',
        и: 'i',
        й: 'y',
        к: 'k',
        л: 'l',
        м: 'm',
        н: 'n',
        о: 'o',
        п: 'p',
        р: 'r',
        с: 's',
        т: 't',
        у: 'u',
        ф: 'f',
        х: 'kh',
        ц: 'ts',
        ч: 'ch',
        ш: 'sh',
        щ: 'shch',
        ъ: '',
        ы: 'y',
        ь: '',
        э: 'e',
        ю: 'yu',
        я: 'ya',
    };
    return str
        .toLowerCase()
        .split('')
        .map((c) => map[c] ?? c)
        .join('')
        .replace(/[^a-z0-9]+/g, '_')
        .replace(/^_+|_+$/g, '');
}

// Sanitize alias input: lowercase + strip anything other than [a-z0-9_], show throttled toast
let aliasToastTimer = 0;
function sanitizeAliasInput(input) {
    const raw = input.value;
    const cleaned = raw.toLowerCase().replace(/[^a-z0-9_]/g, '');
    if (raw !== cleaned) {
        input.value = cleaned;
        clearTimeout(aliasToastTimer);
        aliasToastTimer = setTimeout(() => {
            showToast('Алиас может содержать только латинские буквы, цифры и подчёркивания', 'error');
        }, 250);
    }
    return cleaned;
}

let aliasManual = false;
if (createAlias) {
    createAlias.addEventListener('input', () => {
        aliasManual = true;
        sanitizeAliasInput(createAlias);
        createTagPreview.textContent = '[block:' + createAlias.value + ']';
    });
    createTitle.addEventListener('input', () => {
        if (aliasManual) {return;}
        const a = toAlias(createTitle.value);
        createAlias.value = a;
        createTagPreview.textContent = '[block:' + a + ']';
    });
}

// ── Create form: WYSIWYG toggle label ────────────────────────────────────
const createWysiwyg = document.getElementById('createWysiwyg');
const createWysiwygLabel = document.getElementById('createWysiwygLabel');
const createWysiwygHint = document.getElementById('createWysiwygHint');
if (createWysiwyg && createWysiwygLabel && createWysiwygHint) {
    const syncWysiwygLabel = () => {
        if (createWysiwyg.checked) {
            createWysiwygLabel.textContent = 'WYSIWYG-редактор';
            createWysiwygHint.textContent =
                'Визуальный редактор с панелью форматирования, картинками, таблицами. Подходит для текстового контента.';
        } else {
            createWysiwygLabel.textContent = 'Обычное поле для HTML/CSS/JS-кода';
            createWysiwygHint.textContent = 'Ace-редактор с подсветкой синтаксиса. Подходит для вёрстки и скриптов.';
        }
    };
    createWysiwyg.addEventListener('change', syncWysiwygLabel);
    syncWysiwygLabel();
}

// ── Create block in group (switch to Create tab with preselected group) ──
function openCreateInGroup(groupId) {
    const tab = document.querySelector('[data-bs-target="#tabCreate"]');
    if (tab) {new bootstrap.Tab(tab).show();}
    const select = document.getElementById('createGroupId');
    if (select) {select.value = groupId;}
    const titleInput = document.getElementById('createTitle');
    if (titleInput) {titleInput.focus();}
}

document.querySelectorAll('.btn-create-in-group').forEach((btn) => {
    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        openCreateInGroup(btn.dataset.groupId);
    });
});

// ── Copy modal ────────────────────────────────────────────────────────────
let copyBlockId = null;
const copyModalEl = document.getElementById('copyModal');
const copyModal = copyModalEl ? new bootstrap.Modal(copyModalEl) : null;
const copyAliasInput = document.getElementById('copyAlias');
const btnCopyConfirm = document.getElementById('btnCopyConfirm');

const updateCopyConfirmState = () => {
    if (!btnCopyConfirm || !copyAliasInput) {return;}
    btnCopyConfirm.disabled = !copyAliasInput.value.trim();
};
if (copyAliasInput)
    {copyAliasInput.addEventListener('input', () => {
        sanitizeAliasInput(copyAliasInput);
        updateCopyConfirmState();
    });}

function openCopyModal(blockId, groupId) {
    if (!copyModal) {return;}
    copyBlockId = blockId;
    copyAliasInput.value = '';
    document.getElementById('copyGroupId').value = groupId ?? '';
    updateCopyConfirmState();
    copyModal.show();
}

// Event delegation for dynamically rendered block buttons
document.addEventListener('click', function (e) {
    const copyBtn = e.target.closest('.btn-block-copy');
    if (copyBtn) {
        openCopyModal(copyBtn.dataset.blockId, null);
        return;
    }

    const deleteBtn = e.target.closest('.btn-block-delete');
    if (deleteBtn && deleteBlockModal) {
        deleteBlockId = deleteBtn.dataset.blockId;
        document.getElementById('deleteBlockName').textContent = deleteBtn.dataset.blockTitle;
        deleteBlockModal.show();
        return;
    }

    const copyTagBtn = e.target.closest('.btn-copy-tag');
    if (copyTagBtn) {
        navigator.clipboard.writeText(copyTagBtn.dataset.tag).then(() => showToast('Тег скопирован', 'success'));
        return;
    }
});

if (btnCopyConfirm) {
    btnCopyConfirm.addEventListener('click', async () => {
        if (!copyBlockId) {return;}
        const alias = copyAliasInput.value.trim();
        const groupId = document.getElementById('copyGroupId').value;
        if (!alias) {return;}

        btnCopyConfirm.disabled = true;

        try {
            const res = await fetch(`/admin/blocks/${copyBlockId}/copy`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ alias, group_id: groupId || null }),
            });
            const json = await res.json().catch(() => ({}));
            if (json.ok) {
                showToast(json.message, 'success');
                copyModal.hide();
                setTimeout(() => location.reload(), 800);
                return;
            }
            const msg = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Ошибка');
            showToast(msg, 'error');
        } catch (e) {
            showToast('Сетевая ошибка: ' + (e.message ?? 'не удалось отправить запрос'), 'error');
        } finally {
            updateCopyConfirmState();
        }
    });
}

// ── Delete block modal ────────────────────────────────────────────────────
let deleteBlockId = null;
const deleteBlockModalEl = document.getElementById('deleteBlockModal');
const deleteBlockModal = deleteBlockModalEl ? new bootstrap.Modal(deleteBlockModalEl) : null;

const btnDeleteBlockConfirm = document.getElementById('btnDeleteBlockConfirm');
if (btnDeleteBlockConfirm) {
    btnDeleteBlockConfirm.addEventListener('click', async () => {
        if (!deleteBlockId) {return;}
        btnDeleteBlockConfirm.disabled = true;
        try {
            const res = await fetch(`/admin/blocks/${deleteBlockId}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const json = await res.json().catch(() => ({}));
            if (json.ok) {
                showToast(json.message, 'success');
                deleteBlockModal.hide();
                setTimeout(() => location.reload(), 800);
                return;
            }
            showToast(json.message ?? 'Ошибка', 'error');
        } catch (e) {
            showToast('Сетевая ошибка: ' + (e.message ?? 'не удалось отправить запрос'), 'error');
        } finally {
            btnDeleteBlockConfirm.disabled = false;
        }
    });
}

// ── Add group ─────────────────────────────────────────────────────────────
const btnAddGroup = document.getElementById('btnAddGroup');
if (btnAddGroup) {
    btnAddGroup.addEventListener('click', async () => {
        const title = document.getElementById('newGroupTitle').value.trim();
        const desc = document.getElementById('newGroupDesc').value.trim();
        if (!title) {
            showToast('Введите название группы', 'error');
            return;
        }

        btnAddGroup.disabled = true;

        let json;
        try {
            const res = await fetch('/admin/block-groups', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ title, description: desc }),
            });
            json = await res.json().catch(() => ({}));
        } catch (e) {
            showToast('Сетевая ошибка: ' + (e.message ?? 'не удалось отправить запрос'), 'error');
            btnAddGroup.disabled = false;
            return;
        }
        btnAddGroup.disabled = false;

        if (json.ok) {
            showToast(json.message, 'success');
            document.getElementById('newGroupTitle').value = '';
            document.getElementById('newGroupDesc').value = '';

            // Add option to group selects
            const g = json.group;
            const opt = `<option value="${esc(g.id)}">${esc(g.title)}</option>`;
            document.getElementById('createGroupId')?.insertAdjacentHTML('beforeend', opt);
            document.getElementById('copyGroupId')?.insertAdjacentHTML('beforeend', opt);

            // Add row to table
            const tbody = document.getElementById('groupsTableBody');
            const noRow = document.getElementById('noGroupsRow');
            if (noRow) {noRow.remove();}
            tbody.insertAdjacentHTML(
                'beforeend',
                `
                <tr data-group-id="${esc(g.id)}">
                    <td><i class="bi bi-grip-vertical group-drag-handle text-muted"></i></td>
                    <td class="text-muted">${esc(g.id)}</td>
                    <td class="ztr-nowrap">
                        <span class="group-title-text">${esc(g.title)}</span>
                        <input type="text" class="form-control form-control-sm group-title-input d-none ztr-blocks-group-title-input" value="${esc(g.title)}">
                    </td>
                    <td class="ztr-blocks-col-desc">
                        <span class="group-desc-text text-muted ztr-blocks-desc">${esc(g.description ?? '')}</span>
                        <input type="text" class="form-control form-control-sm group-desc-input d-none ztr-blocks-group-desc-input" value="${esc(g.description ?? '')}">
                    </td>
                    <td class="text-muted ztr-blocks-desc">0</td>
                    <td class="text-end ztr-nowrap">
                        <button class="btn btn-sm btn-outline-success group-edit-btn" title="Редактировать"><i class="bi bi-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger group-delete-btn" title="Удалить"
                            data-group-id="${esc(g.id)}" data-group-title="${esc(g.title)}" data-block-count="0"><i class="bi bi-trash"></i></button>
                    </td>
                </tr>
            `,
            );
            bindGroupButtons();
        } else {
            const msg = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Ошибка');
            showToast(msg, 'error');
        }
    });
}

// ── Group edit/delete buttons ─────────────────────────────────────────────
let deleteGroupId = null;
const deleteGroupModalEl = document.getElementById('deleteGroupModal');
const deleteGroupModal = deleteGroupModalEl ? new bootstrap.Modal(deleteGroupModalEl) : null;

function bindGroupButtons() {
    document.querySelectorAll('.group-edit-btn').forEach((btn) => {
        btn.onclick = function () {
            const row = this.closest('tr');
            const titleText = row.querySelector('.group-title-text');
            const titleInput = row.querySelector('.group-title-input');
            const descText = row.querySelector('.group-desc-text');
            const descInput = row.querySelector('.group-desc-input');

            if (this.classList.contains('active')) {
                // Save
                const newTitle = titleInput.value.trim();
                const newDesc = descInput.value.trim();
                if (!newTitle) {
                    showToast('Введите название', 'error');
                    return;
                }

                fetch(`/admin/block-groups/${row.dataset.groupId}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ title: newTitle, description: newDesc }),
                })
                    .then((r) => r.json().catch(() => ({})))
                    .then((json) => {
                        if (json.ok) {
                            titleText.textContent = newTitle;
                            descText.textContent = newDesc;
                            showToast(json.message, 'success');
                        } else {
                            const msg = json.errors
                                ? Object.values(json.errors).flat().join(' ')
                                : (json.message ?? 'Ошибка');
                            showToast(msg, 'error');
                        }
                    })
                    .catch((e) => showToast('Сетевая ошибка: ' + (e.message ?? ''), 'error'));

                titleText.classList.remove('d-none');
                descText.classList.remove('d-none');
                titleInput.classList.add('d-none');
                descInput.classList.add('d-none');
                this.innerHTML = '<i class="bi bi-pencil"></i>';
                this.classList.remove('active', 'btn-success');
                this.classList.add('btn-outline-success');
            } else {
                // Edit mode
                titleText.classList.add('d-none');
                descText.classList.add('d-none');
                titleInput.classList.remove('d-none');
                descInput.classList.remove('d-none');
                titleInput.focus();
                this.innerHTML = '<i class="bi bi-check"></i>';
                this.classList.add('active', 'btn-success');
                this.classList.remove('btn-outline-success');
            }
        };
    });

    document.querySelectorAll('.group-delete-btn').forEach((btn) => {
        btn.onclick = function () {
            if (!deleteGroupModal) {return;}
            deleteGroupId = this.dataset.groupId;
            document.getElementById('deleteGroupName').textContent = this.dataset.groupTitle;
            const cnt = parseInt(this.dataset.blockCount);
            document.getElementById('deleteGroupNote').textContent =
                cnt > 0 ? `В группе ${cnt} блок(ов). Они будут перенесены в «Без группы».` : 'Группа пустая.';
            deleteGroupModal.show();
        };
    });
}

const btnDeleteGroupConfirm = document.getElementById('btnDeleteGroupConfirm');
if (btnDeleteGroupConfirm) {
    btnDeleteGroupConfirm.addEventListener('click', async () => {
        if (!deleteGroupId) {return;}
        btnDeleteGroupConfirm.disabled = true;
        try {
            const res = await fetch(`/admin/block-groups/${deleteGroupId}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const json = await res.json().catch(() => ({}));
            if (json.ok) {
                showToast(json.message, 'success');
                deleteGroupModal.hide();
                setTimeout(() => {
                    location.href = location.pathname + '?tab=groups';
                }, 800);
                return;
            }
            showToast(json.message ?? 'Ошибка', 'error');
        } catch (e) {
            showToast('Сетевая ошибка: ' + (e.message ?? 'не удалось отправить запрос'), 'error');
        } finally {
            btnDeleteGroupConfirm.disabled = false;
        }
    });
}

bindGroupButtons();

// ── SortableJS: groups reorder ────────────────────────────────────────────
const groupsBody = document.getElementById('groupsTableBody');
if (groupsBody) {
    Sortable.create(groupsBody, {
        handle: '.group-drag-handle',
        animation: 150,
        onEnd() {
            const ids = [...groupsBody.querySelectorAll('tr[data-group-id]')].map((r) => r.dataset.groupId);
            fetch('/admin/block-groups/reorder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ ids }),
            }).catch((e) => showToast('Не удалось сохранить порядок: ' + (e.message ?? ''), 'error'));
        },
    });
}

// Поворот стрелки при сворачивании
document.querySelectorAll('.block-group-header').forEach((header) => {
    const body = document.querySelector(header.dataset.bsTarget);
    if (!body) {return;}
    body.addEventListener('hide.bs.collapse', () => header.classList.add('collapsed'));
    body.addEventListener('show.bs.collapse', () => header.classList.remove('collapsed'));
});
