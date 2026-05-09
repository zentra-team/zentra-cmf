const cfg = window.ZentraConfig || {};
const csrf = document.querySelector('meta[name="csrf-token"]').content;
const isEdit = cfg.isEdit;
const canSave = cfg.canSave !== false;
const saveUrl = isEdit ? cfg.updateUrl : cfg.storeUrl;
const method = isEdit ? 'PUT' : 'POST';
const isWysiwyg = !!cfg.isWysiwyg;
const initialContent = cfg.content || '';
const uploadUrl = cfg.uploadUrl || '/admin/upload/image';

let aceEditor = null;
let tinyEditor = null;

if (isWysiwyg) {
    const tinyTextarea = document.getElementById('tinymceEditor');
    tinyTextarea.value = initialContent;

    const tinyImagesUploadHandler = (blobInfo) =>
        new Promise((resolve, reject) => {
            const fd = new FormData();
            fd.append('file', blobInfo.blob(), blobInfo.filename());

            fetch(uploadUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: fd,
            })
                .then(async (r) => {
                    const json = await r.json().catch(() => ({}));
                    if (r.ok && json.ok && json.url) {
                        resolve(json.url);
                    } else {
                        const msg = json.errors
                            ? Object.values(json.errors).flat().join(' ')
                            : (json.message ?? 'Ошибка загрузки');
                        reject({ message: msg, remove: true });
                    }
                })
                .catch((e) => reject({ message: e.message ?? 'Сетевая ошибка', remove: true }));
        });

    tinymce.init({
        target: tinyTextarea,
        base_url: 'https://cdn.jsdelivr.net/npm/tinymce@7',
        suffix: '.min',
        language: 'ru',
        language_url: '/assets/vendor/tinymce/ru.js',
        skin: 'oxide-dark',
        content_css: 'dark',
        height: '100%',
        menubar: 'edit view insert format tools table help',
        plugins:
            'advlist autolink lists link image charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table help wordcount codesample emoticons directionality nonbreaking pagebreak hr',
        toolbar: [
            'undo redo | blocks fontfamily fontsize | bold italic underline strikethrough | forecolor backcolor | removeformat',
            'alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image media table | codesample blockquote hr | charmap emoticons | searchreplace code fullscreen',
        ],
        branding: false,
        promotion: false,
        contextmenu: 'link image table',
        paste_data_images: true,
        automatic_uploads: true,
        images_file_types: 'jpg,jpeg,png,gif,webp,svg',
        images_upload_handler: tinyImagesUploadHandler,
        image_advtab: true,
        image_caption: true,
        image_title: true,
        image_class_list: [
            { title: 'Без класса', value: '' },
            { title: 'Адаптивная (img-fluid)', value: 'img-fluid' },
            { title: 'Адаптивная на всю ширину', value: 'img-fluid w-100' },
            { title: 'Плавающая слева', value: 'float-start me-3 mb-2' },
            { title: 'Плавающая справа', value: 'float-end ms-3 mb-2' },
        ],
        link_default_target: '_self',
        link_target_list: [
            { title: 'В этой же вкладке', value: '_self' },
            { title: 'В новой вкладке', value: '_blank' },
        ],
        setup: (editor) => {
            tinyEditor = editor;
            editor.on('init', () => {
                if (initialContent) {editor.setContent(initialContent);}
            });
        },
    });
} else {
    aceEditor = ace.edit('aceEditor');
    aceEditor.setTheme('ace/theme/monokai');
    aceEditor.session.setMode('ace/mode/html');
    aceEditor.setOptions({ fontSize: '13px', showPrintMargin: false, wrap: true, tabSize: 4, useSoftTabs: true });
    aceEditor.setValue(initialContent, -1);
}

if (!isEdit) {
    const titleInput = document.querySelector('[name="title"]');
    const aliasInput = document.getElementById('aliasInput');
    const tagPreview = document.getElementById('tagPreview');

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

    let aliasToastTimer = 0;
    const sanitizeAlias = (input) => {
        const raw = input.value;
        const cleaned = raw.toLowerCase().replace(/[^a-z0-9_]/g, '');
        if (raw !== cleaned) {
            input.value = cleaned;
            clearTimeout(aliasToastTimer);
            aliasToastTimer = setTimeout(() => {
                showToast('Алиас может содержать только латинские буквы, цифры и подчёркивания', 'error');
            }, 250);
        }
    };

    let aliasManual = false;
    aliasInput.addEventListener('input', () => {
        aliasManual = true;
        sanitizeAlias(aliasInput);
        tagPreview.textContent = '[block:' + aliasInput.value + ']';
    });
    titleInput.addEventListener('input', () => {
        if (aliasManual) {return;}
        const a = toAlias(titleInput.value);
        aliasInput.value = a;
        tagPreview.textContent = '[block:' + a + ']';
    });
}

const tagHints = cfg.tagHints || {};
document.querySelectorAll('.block-tag-info').forEach((el) => {
    const hint = tagHints[el.dataset.tagKey];
    if (!hint) {return;}
    new bootstrap.Popover(el, {
        sanitize: false,
        html: true,
        trigger: 'hover focus',
        placement: 'right',
        customClass: 'tag-hint-popover',
        content: hint,
    });
    el.addEventListener('click', (e) => e.stopPropagation());
});

document.querySelectorAll('.block-tag-item').forEach((el) => {
    el.addEventListener('click', (e) => {
        if (e.target.closest('.block-tag-info')) {return;}
        if (isWysiwyg && tinyEditor) {
            tinyEditor.focus();
            tinyEditor.execCommand('mceInsertContent', false, el.dataset.tag);
        } else if (aceEditor) {
            aceEditor.session.insert(aceEditor.getCursorPosition(), el.dataset.tag);
            aceEditor.focus();
        }
    });
});

document.querySelectorAll('.btn-tag').forEach((btn) => {
    btn.addEventListener('click', () => {
        if (!aceEditor) {return;}
        aceEditor.session.insert(aceEditor.getCursorPosition(), btn.dataset.html);
        aceEditor.focus();
    });
});

function getContent() {
    if (isWysiwyg) {return tinyEditor ? tinyEditor.getContent() : '';}
    return aceEditor ? aceEditor.getValue() : '';
}

async function saveBlock() {
    const btn = document.getElementById('btnSave');
    const status = document.getElementById('saveStatus');

    if (btn.disabled) {return;}

    const originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
    status.textContent = '';

    const form = document.getElementById('blockForm');
    const data = {
        _token: csrf,
        title: form.querySelector('[name="title"]').value,
        alias: form.querySelector('[name="alias"]').value,
        description: form.querySelector('[name="description"]').value,
        group_id: form.querySelector('[name="group_id"]').value || null,
        content: getContent(),
    };

    if (!isEdit) {data.is_wysiwyg = isWysiwyg ? 1 : 0;}

    try {
        const res = await fetch(saveUrl, {
            method: method,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(data),
        });
        const json = await res.json().catch(() => ({}));

        if (json.ok) {
            showToast(json.message ?? 'Сохранено', 'success');
            status.textContent = 'Сохранено ' + new Date().toLocaleTimeString('ru');
            if (!isEdit && json.redirect) {
                window.location.href = json.redirect;
                return;
            }
        } else {
            const msg = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Ошибка');
            showToast(msg, 'error');
        }
    } catch (e) {
        showToast('Сетевая ошибка: ' + (e.message ?? 'не удалось отправить запрос'), 'error');
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    }
}

const btnSaveEl = document.getElementById('btnSave');
if (btnSaveEl) {btnSaveEl.addEventListener('click', saveBlock);}
if (canSave) {
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveBlock();
        }
    });
}

document.querySelectorAll('.block-tags-group-header').forEach((header) => {
    const body = document.querySelector(header.dataset.bsTarget);
    if (!body) {return;}
    body.addEventListener('hide.bs.collapse', () => header.classList.add('collapsed'));
    body.addEventListener('show.bs.collapse', () => header.classList.remove('collapsed'));
});
