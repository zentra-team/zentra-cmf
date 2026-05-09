(function () {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    /* ── Ace editors ── */
    const aceEditors = {};
    const aceIds = ['mainTpl', 'itemTpl'];

    aceIds.forEach((id) => {
        const el = document.getElementById(id);
        if (!el) { return; }
        const editor = ace.edit(el);
        editor.setTheme('ace/theme/monokai');
        editor.session.setMode('ace/mode/html');
        editor.setOptions({
            fontSize: '12px',
            showPrintMargin: false,
            wrap: true,
            tabSize: 4,
            useSoftTabs: true,
        });
        editor.setValue(cfg.templates?.[id] ?? '', -1);
        if (!cfg.canEdit) { editor.setReadOnly(true); }
        aceEditors[id] = editor;
    });

    /* resize Ace when Bootstrap tab becomes visible */
    document.querySelectorAll('[data-bs-toggle="tab"]').forEach((tab) => {
        tab.addEventListener('shown.bs.tab', (e) => {
            const target = e.target.getAttribute('href')?.replace('#', '');
            if (target === 'tabMainTpl') { aceEditors.mainTpl?.resize(); }
            if (target === 'tabItemTpl') { aceEditors.itemTpl?.resize(); }
        });
    });

    function insertTag(el) {
        const tooltip = bootstrap.Tooltip.getInstance(el);
        if (tooltip) { tooltip.hide(); }
        const targetId = el.dataset.target;
        const editor = aceEditors[targetId];
        if (editor) {
            editor.session.insert(editor.getCursorPosition(), el.dataset.val);
            editor.focus();
            return;
        }
    }
    document.querySelectorAll('.tpl-tag, .tpl-tag-field').forEach((tag) => {
        tag.addEventListener('click', () => insertTag(tag));
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        bootstrap.Tooltip.getOrCreateInstance(el, { trigger: 'hover' });
    });

    function getSelectedRubricIds() {
        return [...document.querySelectorAll('.rubric-check:checked')].map((c) => parseInt(c.value));
    }

    async function loadFields() {
        const ids = getSelectedRubricIds();
        if (!ids.length) {
            updateFieldSelects([]);
            return;
        }
        try {
            const params = ids.map((id) => 'rubric_ids[]=' + id).join('&');
            const res = await fetch(cfg.fieldsUrl + '?' + params, { headers: { Accept: 'application/json' } });
            const fields = await res.json();
            updateFieldSelects(fields);
        } catch {
            updateFieldSelects([]);
        }
    }

    function dedupeFields(fields) {
        const seen = {};
        return fields.filter((f) => {
            if (seen[f.alias]) {return false;}
            seen[f.alias] = true;
            return true;
        });
    }

    function buildOptgroups(fields, defaultLabel, savedValue) {
        let html = `<option value="">${defaultLabel}</option>`;
        const unique = dedupeFields(fields);
        unique.forEach((f) => {
            const sel = f.alias === savedValue ? ' selected' : '';
            html += `<option value="${f.alias}"${sel}>${f.title}</option>`;
        });
        return html;
    }

    function updateFieldSelects(fields) {
        const sortField = document.getElementById('sortFieldSelect');
        const condField = document.getElementById('condField');
        const fieldsTable = document.getElementById('fieldsTableBody');

        const savedSort = sortField?.dataset.current || sortField?.value || '';

        if (sortField) {sortField.innerHTML = buildOptgroups(fields, '— Не задано —', savedSort);}
        if (condField) {condField.innerHTML = buildOptgroups(fields, '— Выберите поле —', '');}

        if (fieldsTable) {
            if (!fields.length) {
                fieldsTable.innerHTML =
                    '<tr><td colspan="2" class="text-muted">Выберите рубрику на вкладке «Параметры»</td></tr>';
            } else {
                const unique = dedupeFields(fields);
                let html = '';
                unique.forEach((f) => {
                    html += `<tr><td>${f.title}</td><td><code class="text-warning tpl-tag-field" data-target="itemTpl" data-val="[field:${f.alias}]" style="font-size:.78rem;cursor:pointer">[field:${f.alias}]</code></td></tr>`;
                });
                fieldsTable.innerHTML = html;
                fieldsTable.querySelectorAll('.tpl-tag-field').forEach((tag) => {
                    tag.addEventListener('click', () => insertTag(tag));
                });
            }
        }
    }

    document.querySelectorAll('.rubric-check').forEach((cb) => {
        cb.addEventListener('change', loadFields);
    });

    const conditions = cfg.conditions || [];

    const condModal = new bootstrap.Modal(document.getElementById('conditionsModal'));

    document.getElementById('btnConditions').addEventListener('click', () => {
        renderCondList();
        condModal.show();
    });

    function renderCondList() {
        const list = document.getElementById('condList');
        const empty = document.getElementById('condEmpty');
        list.querySelectorAll('.cond-row').forEach((r) => r.remove());

        if (conditions.length === 0) {
            empty.style.display = '';
            return;
        }
        empty.style.display = 'none';

        conditions.forEach((cond, idx) => {
            const row = document.createElement('div');
            row.className = 'cond-row' + (cond.active ? '' : ' cond-inactive');
            row.dataset.idx = idx;
            row.innerHTML = `
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-grip-vertical cond-drag"></i>
                    <code style="font-size:.78rem;color:var(--ztr-purple-400)">${cond.field}</code>
                    <span style="font-size:.78rem">${cond.operator}</span>
                    <span style="font-size:.78rem;font-weight:600">${cond.value}</span>
                </div>
                <div style="font-size:.75rem;color:var(--ztr-text-muted);text-align:center">${cond.logic}</div>
                <div class="text-center">
                    <input type="checkbox" class="form-check-input cond-active-check" ${cond.active ? 'checked' : ''} title="Активно">
                </div>
                <div></div>
                <button type="button" class="btn btn-sm btn-danger cond-del-btn" style="padding:.2rem .4rem">
                    <i class="bi bi-trash"></i>
                </button>`;

            row.querySelector('.cond-active-check').addEventListener('change', function () {
                conditions[idx].active = this.checked;
                row.classList.toggle('cond-inactive', !this.checked);
            });
            row.querySelector('.cond-del-btn').addEventListener('click', () => {
                conditions.splice(idx, 1);
                renderCondList();
            });

            list.appendChild(row);
        });

        Sortable.create(list, {
            handle: '.cond-drag',
            animation: 150,
            onEnd: ({ oldIndex, newIndex }) => {
                const [moved] = conditions.splice(oldIndex, 1);
                conditions.splice(newIndex, 0, moved);
            },
        });
    }

    document.getElementById('btnAddCond').addEventListener('click', () => {
        const field = document.getElementById('condField').value;
        const operator = document.getElementById('condOperator').value;
        const value = document.getElementById('condValue').value.trim();
        const logic = document.getElementById('condLogic').value;

        if (!field) {
            showToast('Выберите поле', 'error');
            return;
        }
        if (!value) {
            showToast('Введите значение', 'error');
            return;
        }

        conditions.push({ field, operator, value, logic, active: true });
        document.getElementById('condValue').value = '';
        renderCondList();
    });

    document.getElementById('btnSaveConds').addEventListener('click', () => {
        updateCondSummary();
        condModal.hide();
        save();
    });

    function updateCondSummary() {
        const summary = document.getElementById('condSummary');
        const count = document.getElementById('condCount');
        count.textContent = conditions.length;

        if (conditions.length === 0) {
            summary.innerHTML = '<span>Условия не заданы - выбираются все документы рубрики</span>';
            return;
        }
        summary.innerHTML = conditions
            .filter((c) => c.active)
            .map(
                (c) =>
                    `<span class="badge bg-secondary me-1" style="font-size:.72rem">${c.field} ${c.operator} ${c.value}</span>`,
            )
            .join('');
    }

    async function save() {
        const btn = document.getElementById('btnSave');
        const status = document.getElementById('saveStatus');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
        status.textContent = '';

        const form = document.getElementById('editForm');
        const fd = new FormData(form);

        const data = {
            title: fd.get('title'),
            alias: fd.get('alias'),
            rubric_ids: getSelectedRubricIds(),
            description: fd.get('description') || null,
            sort_field: fd.get('sort_field') || null,
            sort_system: fd.get('sort_system') || null,
            sort_order: fd.get('sort_order'),
            fetch_mode: fd.get('fetch_mode') || 'global',
            limit: fd.get('limit') ? parseInt(fd.get('limit')) : null,
            show_pagination: form.querySelector('[name="show_pagination"]').checked ? 1 : 0,
            per_page: fd.get('per_page') ? parseInt(fd.get('per_page')) : null,
            exclude_current: form.querySelector('[name="exclude_current"]').checked ? 1 : 0,
            cache_time: fd.get('cache_time') ? parseInt(fd.get('cache_time')) : null,
            conditions: conditions,
            template_main: aceEditors.mainTpl?.getValue() || null,
            template_item: aceEditors.itemTpl?.getValue() || null,
        };

        try {
            const res = await fetch(cfg.saveUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data),
            });
            const json = await res.json();

            if (json.ok) {
                showToast(json.message ?? 'Сохранено', 'success');
                status.textContent = 'Сохранено ' + new Date().toLocaleTimeString('ru');
            } else {
                const msg = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Ошибка');
                showToast(msg, 'error');
            }
        } catch {
            showToast('Ошибка соединения', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-floppy me-1"></i>Сохранить';
        }
    }

    document.getElementById('btnSave').addEventListener('click', save);
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            save();
        }
    });

    document.querySelectorAll('[name="fetch_mode"]').forEach((radio) => {
        radio.addEventListener('change', () => {
            const label = document.getElementById('limitLabel');
            if (label) {
                label.textContent = radio.value === 'distributed' ? 'Лимит на рубрику' : 'Лимит документов';
            }
        });
    });

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-copy-tag');
        if (!btn) {return;}
        navigator.clipboard.writeText(btn.dataset.tag).then(() => showToast('Тег скопирован', 'success'));
    });
})();
