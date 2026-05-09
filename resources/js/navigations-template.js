(function () {
    'use strict';

    const { csrf, saveUrl, templates, canEdit } = window.ZentraConfig;

    const editors = {};
    const ids = ['tpl1', 'link1', 'tpl2', 'link2', 'tpl3', 'link3'];

    ids.forEach((id) => {
        const el = document.getElementById('ace-' + id);
        if (!el) {return;}

        const editor = ace.edit(el);
        editor.setTheme('ace/theme/monokai');
        editor.session.setMode('ace/mode/html');
        editor.setOptions({
            fontSize: '12px',
            showPrintMargin: false,
            wrap: true,
            tabSize: 4,
            useSoftTabs: true,
            minLines: 5,
            maxLines: Infinity,
        });
        editor.setValue(templates[id] ?? '', -1);
        if (!canEdit) {editor.setReadOnly(true);}
        editors[id] = editor;
    });

    document.querySelectorAll('.tpl-tag').forEach((tag) => {
        tag.addEventListener('click', () => {
            const editor = editors[tag.dataset.target];
            if (!editor) {return;}

            const val = tag.dataset.val;
            const parts = val.split('...');

            if (parts.length === 2) {
                const pos = editor.getCursorPosition();
                editor.session.insert(pos, parts[0] + '\n\n' + parts[1]);
                editor.moveCursorTo(pos.row + 1, 0);
            } else {
                editor.session.insert(editor.getCursorPosition(), val);
            }

            editor.focus();
        });
    });

    async function saveTemplate() {
        const btn = document.getElementById('btnSave');
        const status = document.getElementById('saveStatus');
        const form = document.getElementById('tplForm');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
        status.textContent = '';

        const groupSelect = form.querySelector('[name="allowed_groups[]"]');
        const data = {
            title: form.querySelector('[name="title"]').value.trim(),
            alias: form.querySelector('[name="alias"]').value.trim(),
            allowed_groups: groupSelect ? Array.from(groupSelect.selectedOptions).map((o) => parseInt(o.value)) : [],
            template_l1: editors.tpl1?.getValue() ?? '',
            link_tpl_l1: editors.link1?.getValue() ?? '',
            template_l2: editors.tpl2?.getValue() ?? '',
            link_tpl_l2: editors.link2?.getValue() ?? '',
            template_l3: editors.tpl3?.getValue() ?? '',
            link_tpl_l3: editors.link3?.getValue() ?? '',
        };

        const res = await fetch(saveUrl, {
            method: 'PUT',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(data),
        });
        const json = await res.json();

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-floppy me-1"></i>\u0421\u043e\u0445\u0440\u0430\u043d\u0438\u0442\u044c';

        if (json.ok) {
            showToast(json.message ?? '\u0421\u043e\u0445\u0440\u0430\u043d\u0435\u043d\u043e', 'success');
            status.textContent =
                '\u0421\u043e\u0445\u0440\u0430\u043d\u0435\u043d\u043e ' + new Date().toLocaleTimeString('ru');
        } else {
            const msg = json.errors
                ? Object.values(json.errors).flat().join(' ')
                : (json.message ?? '\u041e\u0448\u0438\u0431\u043a\u0430');
            showToast(msg, 'error');
        }
    }

    document.getElementById('btnSave').addEventListener('click', saveTemplate);
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveTemplate();
        }
    });

    document.querySelectorAll('.btn-copy-tag').forEach((btn) => {
        btn.addEventListener('click', () => {
            navigator.clipboard
                .writeText(btn.dataset.tag)
                .then(() =>
                    showToast(
                        '\u0422\u0435\u0433 \u0441\u043a\u043e\u043f\u0438\u0440\u043e\u0432\u0430\u043d',
                        'success',
                    ),
                );
        });
    });

    const groupsSelect = document.getElementById('groupsSelect');
    if (groupsSelect) {
        document.getElementById('btnSelectAllGroups')?.addEventListener('click', () => {
            Array.from(groupsSelect.options).forEach((o) => (o.selected = true));
        });
        document.getElementById('btnDeselectAllGroups')?.addEventListener('click', () => {
            Array.from(groupsSelect.options).forEach((o) => (o.selected = false));
        });
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        new bootstrap.Tooltip(el, {
            trigger: 'hover',
            html: true,
            sanitize: false,
            delay: { show: 600, hide: 100 },
        });
    });

    document.querySelectorAll('.nav-tpl-section-header').forEach((header) => {
        const body = document.querySelector(header.dataset.bsTarget);
        if (!body) {return;}
        body.addEventListener('hide.bs.collapse', () => header.classList.add('collapsed'));
        body.addEventListener('show.bs.collapse', () => header.classList.remove('collapsed'));
    });
})();
