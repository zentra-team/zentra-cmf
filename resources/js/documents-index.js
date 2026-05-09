(function () {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const titleInput = document.getElementById('createTitle');
    const aliasInput = document.getElementById('createAlias');
    let aliasManual = cfg.aliasManualInit || false;

    const translit = (s) => {
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
            х: 'h',
            ц: 'ts',
            ч: 'ch',
            ш: 'sh',
            щ: 'sch',
            ъ: '',
            ы: 'y',
            ь: '',
            э: 'e',
            ю: 'yu',
            я: 'ya',
        };
        return s
            .toLowerCase()
            .split('')
            .map((c) => map[c] ?? c)
            .join('')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    };

    titleInput?.addEventListener('input', () => {
        if (aliasManual) {return;}
        aliasInput.value = translit(titleInput.value);
    });
    aliasInput?.addEventListener('input', () => {
        aliasManual = true;
    });

    document.getElementById('btnAddDoc')?.addEventListener('click', () => {
        const rubricId = document.getElementById('addRubricSelect').value;
        if (rubricId) {
            const sel = document.getElementById('createRubric');
            if (sel) {
                sel.value = rubricId;
                sel.dispatchEvent(new Event('change'));
            }
        }
        new bootstrap.Tab(document.querySelector('[href="#tabCreate"]')).show();
        document.getElementById('createTitle')?.focus();
    });

    const createRubric = document.getElementById('createRubric');
    const createStatus = document.getElementById('createStatus');
    const publishMap = cfg.rubricsPublishMap || {};
    const publishOption = createStatus?.querySelector('option[data-publish-option]');

    function syncCreateStatusOptions() {
        if (!createRubric || !createStatus || !publishOption) {return;}
        const rubricId = createRubric.value;
        const canPub = rubricId ? !!publishMap[rubricId] : true;
        if (canPub) {
            if (!publishOption.parentNode) {
                const modOption = createStatus.querySelector('option[value="2"]');
                createStatus.insertBefore(publishOption, modOption);
            }
        } else {
            if (publishOption.parentNode) {publishOption.remove();}

            if (createStatus.value === '1') {createStatus.value = '0';}
        }
    }
    createRubric?.addEventListener('change', syncCreateStatusOptions);
    syncCreateStatusOptions();

    const checkAll = document.getElementById('checkAll');
    checkAll?.addEventListener('change', () => {
        document.querySelectorAll('.doc-check').forEach((c) => (c.checked = checkAll.checked));
        updateBulkCount();
    });
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('doc-check')) {updateBulkCount();}
    });
    function updateBulkCount() {
        const checked = document.querySelectorAll('.doc-check:checked').length;
        const el = document.getElementById('bulkCount');
        if (el) {el.textContent = checked ? `Выбрано: ${checked}` : '';}
    }

    document.addEventListener('click', async function (e) {
        const copyBtn = e.target.closest('.btn-doc-copy');
        if (copyBtn) {
            copyDocument(copyBtn);
            return;
        }

        const delBtn = e.target.closest('.btn-doc-delete');
        if (delBtn) {
            deleteId = delBtn.dataset.id;
            document.getElementById('deleteTitle').textContent = delBtn.dataset.title;
            deleteModal.show();
            return;
        }
    });

    async function copyDocument(btn) {
        if (btn.disabled) {return;}
        const id = btn.dataset.id;
        btn.disabled = true;
        try {
            const res = await fetch(`/admin/documents/${id}/copy`, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            const json = await res.json().catch(() => ({}));
            if (json.ok) {
                showToast(json.message, 'success');
                setTimeout(() => location.reload(), 600);
            } else {
                showToast(json.message ?? 'Ошибка', 'error');
                btn.disabled = false;
            }
        } catch {
            showToast('Ошибка соединения', 'error');
            btn.disabled = false;
        }
    }

    let deleteId = null;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const btnDeleteConfirm = document.getElementById('btnDeleteConfirm');

    btnDeleteConfirm.addEventListener('click', async () => {
        if (btnDeleteConfirm.disabled) {return;}
        btnDeleteConfirm.disabled = true;
        try {
            const res = await fetch(`/admin/documents/${deleteId}`, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            const json = await res.json().catch(() => ({}));
            if (json.ok) {
                showToast(json.message, 'success');
                deleteModal.hide();
                document.querySelector(`tr[data-id="${deleteId}"]`)?.remove();
            } else {
                showToast(json.message ?? 'Ошибка', 'error');
            }
        } catch {
            showToast('Ошибка соединения', 'error');
        } finally {
            btnDeleteConfirm.disabled = false;
        }
    });

    let bulkAction = '';
    let bulkChecked = [];
    const bulkDeleteModal = new bootstrap.Modal(document.getElementById('bulkDeleteModal'));

    const btnBulkApply = document.getElementById('btnBulkApply');
    const btnBulkDeleteConfirm = document.getElementById('btnBulkDeleteConfirm');

    btnBulkApply?.addEventListener('click', () => {
        if (btnBulkApply.disabled) {return;}
        bulkAction = document.getElementById('bulkAction').value;
        bulkChecked = [...document.querySelectorAll('.doc-check:checked')].map((c) => c.value);

        if (!bulkAction) {
            showToast('Выберите действие', 'error');
            return;
        }
        if (!bulkChecked.length) {
            showToast('Не выбраны документы', 'error');
            return;
        }

        if (bulkAction === 'delete') {
            document.getElementById('bulkDeleteCount').textContent = bulkChecked.length;
            bulkDeleteModal.show();
            return;
        }

        executeBulk(btnBulkApply);
    });

    btnBulkDeleteConfirm?.addEventListener('click', () => {
        bulkDeleteModal.hide();
        executeBulk(btnBulkDeleteConfirm);
    });

    async function executeBulk(btn) {
        if (btn.disabled) {return;}
        btn.disabled = true;
        try {
            const res = await fetch('/admin/documents/bulk', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ action: bulkAction, ids: bulkChecked }),
            });
            const json = await res.json().catch(() => ({}));
            if (json.ok) {
                showToast(json.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showToast(json.message ?? 'Ошибка', 'error');
                btn.disabled = false;
            }
        } catch {
            showToast('Ошибка соединения', 'error');
            btn.disabled = false;
        }
    }

    const rubricFieldsUrl = cfg.rubricFieldsUrl;
    const filterFieldAlias = document.getElementById('filterFieldAlias');

    document
        .getElementById('searchPanel')
        ?.querySelector('select[name="rubric_id"]')
        ?.addEventListener('change', function () {
            loadRubricFields(this.value, null);
        });

    async function loadRubricFields(rubricId, selectedAlias) {
        if (!filterFieldAlias) {return;}
        if (!rubricId) {
            filterFieldAlias.innerHTML = '<option value="">— Поле —</option>';
            return;
        }
        try {
            const res = await fetch(rubricFieldsUrl + '?rubric_id=' + rubricId, {
                headers: { Accept: 'application/json' },
            });
            const fields = await res.json();
            let html = '<option value="">— Поле —</option>';
            fields.forEach((f) => {
                const sel = selectedAlias === f.alias ? ' selected' : '';
                html += `<option value="${f.alias}"${sel}>${f.title} (${f.alias})</option>`;
            });
            filterFieldAlias.innerHTML = html;
        } catch (e) {
            console.warn('Ошибка загрузки полей:', e);
        }
    }

    if (cfg.hasErrors) {
        new bootstrap.Tab(document.querySelector('[href="#tabCreate"]')).show();
        showToast(cfg.errorsText, 'error');
    }

    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('ztr-doc-pos-input')) {
            savePosition(e.target);
        }
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' && e.target.classList.contains('ztr-doc-pos-input')) {
            e.target.blur();
        }
    });

    async function savePosition(input) {
        const id = input.dataset.id;
        const position = parseInt(input.value, 10);
        if (isNaN(position) || position < 0) {
            showToast('Позиция должна быть числом ≥ 0', 'error');
            input.value = input.defaultValue;
            return;
        }
        const prev = input.defaultValue;
        input.disabled = true;
        try {
            const res = await fetch(`${cfg.docPositionUrlBase}/${id}/position`, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ position }),
            });
            const json = await res.json().catch(() => ({}));
            if (json.ok) {
                input.defaultValue = String(json.position);
                showToast('Позиция обновлена', 'success');
            } else {
                showToast(json.message ?? 'Ошибка', 'error');
                input.value = prev;
            }
        } catch {
            showToast('Ошибка соединения', 'error');
            input.value = prev;
        } finally {
            input.disabled = false;
        }
    }

    const normalizeModalEl = document.getElementById('normalizeModal');
    const normalizeModal = normalizeModalEl ? new bootstrap.Modal(normalizeModalEl) : null;
    let normalizeRubricId = null;

    document.getElementById('btnNormalizePositions')?.addEventListener('click', function () {
        normalizeRubricId = this.dataset.rubric;
        normalizeModal?.show();
    });

    document.getElementById('btnNormalizeConfirm')?.addEventListener('click', async function () {
        if (!normalizeRubricId) { return; }
        const btn = this;
        btn.disabled = true;
        const orig = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Нормализация…';
        normalizeModal?.hide();
        try {
            const res = await fetch(cfg.normalizePositionsUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ rubric_id: normalizeRubricId }),
            });
            const json = await res.json().catch(() => ({}));
            if (json.ok) {
                showToast(json.message, 'success');
                setTimeout(() => location.reload(), 700);
            } else {
                showToast(json.message ?? 'Ошибка', 'error');
                btn.disabled = false;
                btn.innerHTML = orig;
            }
        } catch {
            showToast('Ошибка соединения', 'error');
            btn.disabled = false;
            btn.innerHTML = orig;
        }
    });
})();
