const cfg = window.ZentraConfig || {};
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const layoutId = cfg.layoutId;
const updateUrl = '/admin/layouts/' + layoutId;
const tagsUrl = '/admin/layouts/tags';

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
editor.setValue(cfg.content, -1);
editor.focus();

if (cfg.canEdit) {
    async function saveLayout() {
        const btn = document.getElementById('btnSave');
        const status = document.getElementById('saveStatus');

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
        if (status) {status.textContent = '';}

        try {
            const res = await fetch(updateUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({
                    title: document.getElementById('layoutTitle').value.trim(),
                    content: editor.getValue(),
                }),
            });
            const text = await res.text();
            const data = JSON.parse(text);

            if (data.ok) {
                showToast(data.message ?? 'Сохранено', 'success');
                if (status) {status.textContent = 'Сохранено ' + new Date().toLocaleTimeString('ru');}
            } else {
                showToast(data.message ?? 'Ошибка сохранения', 'error');
            }
        } catch (e) {
            showToast('Ошибка сохранения', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-floppy me-1"></i>Сохранить';
        }
    }

    document.getElementById('btnSave')?.addEventListener('click', saveLayout);

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveLayout();
        }
    });
} else {
    editor.setReadOnly(true);
}

const singleTags = ['IMG', 'BR'];

document.querySelectorAll('.html-tag-btn').forEach((btn) => {
    btn.addEventListener('click', () => {
        const tag = btn.dataset.tag;

        if (tag === 'TAB') {
            editor.session.insert(editor.getCursorPosition(), '\t');
            editor.focus();
            return;
        }

        if (singleTags.includes(tag)) {
            editor.session.insert(editor.getCursorPosition(), `<${tag.toLowerCase()}>`);
        } else {
            const open = `<${tag.toLowerCase()}>`;
            const close = `</${tag.toLowerCase()}>`;
            const pos = editor.getCursorPosition();
            editor.session.insert(pos, open + close);

            editor.moveCursorTo(pos.row, pos.column + open.length);
        }
        editor.focus();
    });
});

async function loadTags() {
    const panel = document.getElementById('tagsPanel');

    const res = await fetch(tagsUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
    const data = await res.json();

    panel.innerHTML = '';
    renderTagGroup(panel, 'Системные теги', data.system);

    if (data.navigation?.length) {
        renderTagGroup(panel, 'Навигация', data.navigation);
    }
    if (data.blocks?.length) {
        renderTagGroup(panel, 'Блоки', data.blocks);
    }
    if (data.modules?.length) {
        renderTagGroup(panel, 'Модули', data.modules);
    }
}

function renderTagGroup(container, title, items) {
    const groupId = 'tg_' + title.replace(/\s/g, '_');

    const header = document.createElement('div');
    header.className = 'layout-tags-group-header';
    header.innerHTML = title + ' <i class="bi bi-chevron-down" style="font-size:.65rem"></i>';
    header.setAttribute('data-bs-toggle', 'collapse');
    header.setAttribute('data-bs-target', '#' + groupId);

    const list = document.createElement('div');
    list.className = 'layout-tags-list collapse show';
    list.id = groupId;

    items.forEach((item) => {
        const el = document.createElement('span');
        el.className = 'layout-tag-item';

        const tagText = document.createElement('span');
        tagText.className = 'layout-tag-text text-warning';
        tagText.textContent = item.tag;
        el.appendChild(tagText);

        const popoverContent =
            item.title && item.hint ? '<b>' + item.title + '</b><br><br>' + item.hint : item.hint || item.title || null;

        if (popoverContent) {
            const info = document.createElement('i');
            info.className = 'bi bi-info-circle layout-tag-info';
            info.setAttribute('data-bs-toggle', 'popover');
            info.setAttribute('data-bs-trigger', 'hover focus');
            info.setAttribute('data-bs-html', 'true');
            info.setAttribute('data-bs-content', popoverContent);
            info.setAttribute('data-bs-placement', 'right');
            info.setAttribute('data-bs-custom-class', 'tag-hint-popover');
            info.addEventListener('click', (e) => e.stopPropagation());
            el.appendChild(info);
            new bootstrap.Popover(info, { sanitize: false, html: true, trigger: 'hover focus' });
        }

        el.addEventListener('click', () => {
            editor.session.insert(editor.getCursorPosition(), item.tag);
            editor.focus();
        });
        list.appendChild(el);
    });

    container.appendChild(header);
    container.appendChild(list);

    list.addEventListener('hide.bs.collapse', () => header.classList.add('collapsed'));
    list.addEventListener('show.bs.collapse', () => header.classList.remove('collapsed'));
}

loadTags();
