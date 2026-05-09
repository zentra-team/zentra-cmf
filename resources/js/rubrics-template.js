document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const updateUrl = cfg.updateUrl;

    const editor = ace.edit('aceEditor');
    editor.setTheme('ace/theme/monokai');
    editor.session.setMode('ace/mode/html');
    editor.setOptions({
        fontSize: '13px',
        showPrintMargin: false,
        wrap: true,
        tabSize: 4,
        useSoftTabs: true,
    });
    editor.setValue(cfg.template, -1);
    if (!cfg.canEdit) {
        editor.setReadOnly(true);
    }
    editor.focus();

    const insertTag = (tag) => {
        if (!tag) {return;}
        editor.session.insert(editor.getCursorPosition(), tag);
        editor.focus();
    };

    document.querySelectorAll('.rubric-tag-item').forEach((el) => {
        el.addEventListener('click', () => insertTag(el.dataset.tag));
    });

    document.querySelectorAll('.rubric-tag-by-id').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            insertTag(btn.dataset.tag);
        });
    });

    document.querySelectorAll('.rubric-tag-template').forEach((btn) => {
        btn.addEventListener('click', (e) => {
            e.stopPropagation();
            insertTag(btn.dataset.tag);
        });
    });

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => {
        new bootstrap.Tooltip(el);
    });

    async function saveTemplate() {
        const btn = document.getElementById('btnSave');
        const status = document.getElementById('saveStatus');

        if (btn.disabled) {return;}

        const originalHtml = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
        status.textContent = '';

        try {
            const res = await fetch(updateUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ template: editor.getValue() }),
            });
            const data = await res.json().catch(() => ({}));

            if (data.ok) {
                showToast(data.message ?? 'Сохранено', 'success');
                status.textContent = 'Сохранено ' + new Date().toLocaleTimeString('ru');
            } else {
                const msg = data.errors ? Object.values(data.errors).flat().join(' ') : (data.message ?? 'Ошибка');
                showToast(msg, 'error');
            }
        } catch (err) {
            showToast('Сетевая ошибка: ' + (err.message ?? ''), 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = originalHtml;
        }
    }

    document.getElementById('btnSave')?.addEventListener('click', saveTemplate);

    if (cfg.canEdit) {
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                saveTemplate();
            }
        });
    }

    document.querySelectorAll('.rubric-tags-group-header').forEach((header) => {
        const body = document.querySelector(header.dataset.bsTarget);
        if (!body) {return;}
        body.addEventListener('hide.bs.collapse', () => header.classList.add('collapsed'));
        body.addEventListener('show.bs.collapse', () => header.classList.remove('collapsed'));
    });
});
