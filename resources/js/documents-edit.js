(function () {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const canEdit = cfg.canEdit !== false;

    const mdeInstances = {};

    if (typeof EasyMDE !== 'undefined') {
        const mdeUploadUrl = cfg.uploadUrl || '/admin/upload/image';

        const mdeImageUpload = (file, onSuccess, onError) => {
            const fd = new FormData();
            fd.append('file', file);
            fetch(mdeUploadUrl, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: fd,
            })
                .then(async (r) => {
                    const json = await r.json().catch(() => ({}));
                    if (r.ok && json.ok && json.url) {
                        onSuccess(json.url);
                    } else {
                        const msg = json.errors
                            ? Object.values(json.errors).flat().join(' ')
                            : (json.message ?? 'Ошибка загрузки');
                        onError(msg);
                    }
                })
                .catch((e) => onError(e.message ?? 'Сетевая ошибка'));
        };

        document.querySelectorAll('textarea.ztr-markdown').forEach((textarea) => {
            const mde = new EasyMDE({
                element: textarea,
                spellChecker: false,
                autosave: { enabled: false },
                toolbar: [
                    'bold',
                    'italic',
                    'strikethrough',
                    '|',
                    'heading-1',
                    'heading-2',
                    'heading-3',
                    '|',
                    'code',
                    'quote',
                    '|',
                    'unordered-list',
                    'ordered-list',
                    '|',
                    'link',
                    {
                        name: 'image',
                        action: EasyMDE.drawImage,
                        className: 'bi bi-image ztr-mde-icon',
                        title: 'Картинка по URL',
                    },
                    {
                        name: 'upload-image',
                        action: function (editor) {
                            const picker = document.createElement('input');
                            picker.type = 'file';
                            picker.accept = 'image/jpeg,image/png,image/gif,image/webp';
                            picker.addEventListener('change', () => {
                                if (!picker.files.length) {return;}
                                mdeImageUpload(
                                    picker.files[0],
                                    (url) => editor.codemirror.replaceSelection(url),
                                    (err) => showToast(err || 'Ошибка загрузки', 'error'),
                                );
                            });
                            picker.click();
                        },
                        className: 'bi bi-cloud-arrow-up ztr-mde-icon',
                        title: 'Загрузить и вставить ссылку',
                    },
                    '|',
                    'preview',
                    'side-by-side',
                    'fullscreen',
                ],
                uploadImage: true,
                imageUploadFunction: mdeImageUpload,
                imageAccept: 'image/jpeg,image/png,image/gif,image/webp',
                minHeight: '320px',
                status: ['lines', 'words'],
            });
            if (!canEdit) {
                mde.codemirror.setOption('readOnly', 'nocursor');
            }
            mdeInstances[textarea.name] = mde;
        });
    }

    const tinyInstances = {};
    const uploadUrl = cfg.uploadUrl || '/admin/upload/image';

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

    if (typeof tinymce !== 'undefined') {
        document.querySelectorAll('textarea.ztr-wysiwyg').forEach((textarea) => {
            tinymce.init({
                target: textarea,
                base_url: 'https://cdn.jsdelivr.net/npm/tinymce@7',
                suffix: '.min',
                language: 'ru',
                language_url: '/assets/vendor/tinymce/ru.js',
                skin: 'oxide-dark',
                content_css: 'dark',
                min_height: 400,
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
                readonly: !canEdit,
                setup: (editor) => {
                    tinyInstances[textarea.name] = editor;
                },
            });
        });
    }

    function toSlug(str) {
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
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '');
    }

    let aliasManual = true;
    const titleInput = document.querySelector('[name="title"]');
    const aliasInput = document.getElementById('aliasInput');

    function updateAliasUI(alias) {
        const preview = document.getElementById('urlPreview');
        const prefixSpan = document.getElementById('aliasPrefix');
        const sfx = cfg.urlSuffix || '';
        if (alias === 'index') {
            if (prefixSpan) {prefixSpan.textContent = '/';}
            if (preview) {preview.textContent = '/ (главная страница)';}
        } else if (alias === '' && cfg.rubricAlias) {
            if (prefixSpan) {prefixSpan.textContent = '/';}
            if (preview) {preview.textContent = '/' + cfg.rubricAlias + ' (главная страница рубрики)';}
        } else {
            const prefix = cfg.rubricAlias ? '/' + cfg.rubricAlias + '/' : '/';
            if (prefixSpan) {prefixSpan.textContent = prefix;}
            if (preview) {preview.textContent = prefix + alias + sfx;}
        }
    }

    aliasInput?.addEventListener('input', function () {
        aliasManual = true;
        updateAliasUI(this.value);
    });

    if (aliasInput) {updateAliasUI(aliasInput.value);}

    titleInput?.addEventListener('input', function () {
        if (aliasManual || !aliasInput) {return;}
        aliasInput.value = toSlug(this.value);
        aliasInput.dispatchEvent(new Event('input'));
    });

    const STATUS_LABELS  = { 0: 'Черновик', 1: 'Опубликован', 2: 'На модерации' };
    const STATUS_CLASSES = { 0: 'secondary',  1: 'success',     2: 'warning' };

    function computePublicUrl() {
        const alias       = getVal('alias') || '';
        const rubricAlias = cfg.rubricAlias;
        const suffix      = cfg.urlSuffix;
        if (alias === 'index') { return '/'; }
        if (!alias) { return rubricAlias ? '/' + rubricAlias + suffix : '/'; }
        return '/' + (rubricAlias ? rubricAlias + '/' : '') + alias + suffix;
    }

    function updateStatusUI(newStatus) {
        const publishedAtVal = getVal('published_at');
        const isScheduled    = newStatus === 1 && publishedAtVal && new Date(publishedAtVal) > new Date();
        const label = isScheduled ? 'Запланирован' : (STATUS_LABELS[newStatus]  ?? 'Черновик');
        const cls   = isScheduled ? 'info'         : (STATUS_CLASSES[newStatus] ?? 'secondary');

        const badge = document.getElementById('docStatusBadge');
        if (badge) {
            badge.className   = 'badge bg-' + cls;
            badge.textContent = label;
        }

        const viewBtn = document.getElementById('docViewBtn');
        if (viewBtn) {
            const a = document.createElement('a');
            a.target = '_blank';
            a.rel    = 'noopener';
            if (newStatus === 1 && !isScheduled) {
                a.href      = computePublicUrl();
                a.className = 'btn btn-sm btn-success';
                a.title     = 'Просмотр на сайте';
                a.innerHTML = '<i class="bi bi-box-arrow-up-right me-1"></i>На сайте';
            } else {
                a.href      = cfg.previewUrl;
                a.className = 'btn btn-sm btn-' + cls;
                a.title     = 'Превью (документ не опубликован)';
                a.innerHTML = '<i class="bi bi-eye me-1"></i>Превью';
            }
            viewBtn.replaceChildren(a);
        }
    }

    async function save() {
        if (!canEdit) {return;}
        const btn = document.getElementById('btnSave');
        const status = document.getElementById('saveStatus');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
        status.textContent = '';

        const data = {
            title: getVal('title'),
            alias: getVal('alias') || null,
            meta_title: getVal('meta_title') || null,
            meta_description: getVal('meta_description') || null,
            meta_robots: getVal('meta_robots'),
            og_title: getVal('og_title') || null,
            og_description: getVal('og_description') || null,
            og_image: getVal('og_image') || null,
            sitemap_changefreq: getVal('sitemap_changefreq') || null,
            sitemap_priority: getVal('sitemap_priority') || null,
            published_at: getVal('published_at') || null,
            unpublished_at: getVal('unpublished_at') || null,
            status: getVal('status'),
            position: getVal('position') ? parseInt(getVal('position')) : 0,
            nav_item_id: getVal('nav_item_id') || null,
            breadcrumb_title: getVal('breadcrumb_title') || null,
            parent_doc_id: getVal('parent_doc_id') || null,
            public_cache_disabled: document.getElementById('publicCacheDisabled')?.checked ? 1 : 0,
            public_cache_ttl: getVal('public_cache_ttl') !== '' ? parseInt(getVal('public_cache_ttl')) || null : null,
            fields: {},
        };

        Object.entries(mdeInstances).forEach(([name, mde]) => {
            const ta = document.querySelector(`[name="${name}"]`);
            if (ta) {ta.value = mde.value();}
        });

        Object.entries(tinyInstances).forEach(([name, editor]) => {
            const ta = document.querySelector(`[name="${name}"]`);
            if (ta) {ta.value = editor.getContent();}
        });

        document.querySelectorAll('.ztr-gallery-field').forEach((wrapper) => {
            const m = (wrapper.dataset.fieldName || '').match(/fields\[(\d+)\]/);
            if (!m) {return;}
            const fid = m[1];
            const items = [];
            wrapper.querySelectorAll('.ztr-gallery-item').forEach((item) => {
                items.push({
                    path: item.dataset.path || '',
                    alt: item.querySelector('.ztr-gallery-alt')?.value || '',
                    description: item.querySelector('.ztr-gallery-desc')?.value || '',
                });
            });
            data.fields[fid] = items;
        });

        document.querySelectorAll('.ztr-checkbox-list').forEach((wrapper) => {
            const m = (wrapper.dataset.fieldName || '').match(/fields\[(\d+)\]/);
            if (!m) {return;}
            const fid = m[1];
            const values = [];
            wrapper.querySelectorAll('.ztr-checkbox-list-item:checked').forEach((cb) => {
                values.push(cb.value);
            });
            data.fields[fid] = values;
        });

        document.querySelectorAll('.ztr-rel-multi').forEach((wrapper) => {
            const m = (wrapper.dataset.fieldName || '').match(/fields\[(\d+)\]/);
            if (!m) {return;}
            const fid = m[1];
            const ids = [];
            wrapper.querySelectorAll('.ztr-rel-multi-chip').forEach((chip) => {
                const id = parseInt(chip.dataset.id || '0', 10);
                if (id > 0) {ids.push(id);}
            });
            data.fields[fid] = ids;
        });

        document.querySelectorAll('.ztr-keyvalue-field').forEach((wrapper) => {
            const m = (wrapper.dataset.fieldName || '').match(/fields\[(\d+)\]/);
            if (!m) {return;}
            const fid = m[1];
            const pairs = [];
            wrapper.querySelectorAll('.ztr-keyvalue-item').forEach((item) => {
                pairs.push({
                    key: item.querySelector('.ztr-keyvalue-key')?.value || '',
                    value: item.querySelector('.ztr-keyvalue-value')?.value || '',
                });
            });
            data.fields[fid] = pairs;
        });

        document.querySelectorAll('.ztr-repeater-field').forEach((wrapper) => {
            const m = (wrapper.dataset.fieldName || '').match(/fields\[(\d+)\]/);
            if (!m) {return;}
            const fid = m[1];
            const groups = [];
            wrapper.querySelectorAll('.ztr-repeater-groups > .ztr-repeater-group').forEach((group) => {
                const groupData = {};
                group.querySelectorAll('[name^="fields["]').forEach((el) => {
                    const m2 = el.name.match(/fields\[\d+\]\[\d+\]\[([^\]]+)\](?:\[([^\]]+)\])?/);
                    if (!m2) {return;}
                    const sub = m2[1];
                    const subkey = m2[2];
                    if (el.type === 'file') {return;}

                    if (subkey) {
                        if (typeof groupData[sub] !== 'object' || groupData[sub] === null) {
                            groupData[sub] = {};
                        }
                        if (el.type === 'checkbox') {
                            groupData[sub][subkey] = el.checked ? '1' : '0';
                        } else if (el.type !== 'radio' || el.checked) {
                            groupData[sub][subkey] = el.value;
                        }
                    } else {
                        if (el.type === 'checkbox') {
                            groupData[sub] = el.checked ? '1' : '0';
                        } else if (el.type === 'radio') {
                            if (el.checked) { groupData[sub] = el.value; }
                        } else {
                            groupData[sub] = el.value;
                        }
                    }
                });
                groups.push(groupData);
            });
            data.fields[fid] = groups;
        });

        document.querySelectorAll('[name^="fields["]').forEach((el) => {
            if (el.closest('.ztr-gallery-field')) {return;}
            if (el.closest('.ztr-checkbox-list')) {return;}
            if (el.closest('.ztr-rel-multi')) {return;}
            if (el.closest('.ztr-repeater-field')) {return;}
            if (el.closest('.ztr-keyvalue-field')) {return;}
            const match = el.name.match(/fields\[(\d+)\](?:\[(\w+)\])?/);
            if (!match) {return;}
            const fid = match[1];
            const sub = match[2];

            if (sub) {
                if (el.type === 'file') {return;}
                if (typeof data.fields[fid] !== 'object' || data.fields[fid] === null) {
                    data.fields[fid] = {};
                }
                if (el.type === 'checkbox') {
                    data.fields[fid][sub] = el.checked ? '1' : '0';
                } else {
                    data.fields[fid][sub] = el.value;
                }
            } else {
                if (el.type === 'checkbox') {
                    data.fields[fid] = el.checked ? '1' : '0';
                } else if (el.type === 'radio') {
                    if (el.checked) { data.fields[fid] = el.value; }
                } else {
                    data.fields[fid] = el.value;
                }
            }
        });

        try {
            const res = await fetch(cfg.saveUrl, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify(data),
            });
            const json = await res.json().catch(() => null);

            if (!json) {
                showToast('Ошибка сервера', 'error');
            } else if (json.ok) {
                showToast(json.message ?? 'Сохранено', 'success');
                status.textContent = 'Сохранено ' + new Date().toLocaleTimeString('ru');
                updateStatusUI(parseInt(getVal('status'), 10));
                loadRevisions();
            } else {
                const msg = json.errors
                    ? Object.values(json.errors).flat().join(' ')
                    : (json.message ?? 'Ошибка сохранения');
                showToast(msg, 'error');
            }
        } catch {
            showToast('Ошибка соединения', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-floppy me-1"></i>Сохранить';
        }
    }

    function getVal(name) {
        return document.querySelector(`[name="${name}"]`)?.value ?? '';
    }

    document.getElementById('btnSave')?.addEventListener('click', save);
    document.addEventListener('keydown', (e) => {
        if (!canEdit) {return;}
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            save();
        }
    });

    let currentRevisionId = null;

    async function loadRevisions() {
        try {
            const res = await fetch(cfg.revisionsUrl, { headers: { Accept: 'application/json' } });
            const json = await res.json();
            renderRevisions(json.revisions ?? []);
        } catch {
            document.getElementById('revisionsBody').innerHTML =
                '<div class="text-muted p-3">Не удалось загрузить историю.</div>';
        }
    }

    function renderRevisions(revisions) {
        const body = document.getElementById('revisionsBody');
        const btnDel = document.getElementById('btnDeleteAllRevisions');

        if (!revisions.length) {
            body.innerHTML =
                '<div class="text-muted p-3 ztr-doc-no-fields"><i class="bi bi-info-circle me-1"></i>История изменений пуста. Она появится после первого сохранения.</div>';
            if (btnDel) {btnDel.classList.add('d-none');}
            return;
        }

        if (btnDel) {btnDel.classList.remove('d-none');}
        let html = '<div class="table-responsive"><table class="table table-sm mb-0 ztr-doc-rev-table">';
        html +=
            '<thead><tr>' +
            '<th class="ztr-doc-rev-col-date">Дата и время</th>' +
            '<th>Автор</th>' +
            '<th class="ztr-doc-rev-col-act"></th>' +
            '</tr></thead><tbody>';

        revisions.forEach((r) => {
            html += `<tr>
                <td class="text-nowrap">${r.created_at}</td>
                <td>${r.author}</td>
                <td class="text-end text-nowrap">
                    <button class="btn btn-xs btn-outline-secondary me-1 btn-view-revision" data-id="${r.id}">
                        <i class="bi bi-eye"></i> Просмотр
                    </button>
                    ${canEdit ? `<button class="btn btn-xs btn-outline-danger btn-delete-revision" data-id="${r.id}"><i class="bi bi-trash"></i></button>` : ''}
                </td>
            </tr>`;
        });

        html += '</tbody></table></div>';
        body.innerHTML = html;
    }

    document.getElementById('revisionsBody')?.addEventListener('click', (e) => {
        const viewBtn = e.target.closest('.btn-view-revision');
        if (viewBtn) {
            viewRevision(parseInt(viewBtn.dataset.id));
            return;
        }

        const delBtn = e.target.closest('.btn-delete-revision');
        if (delBtn) {
            deleteRevision(parseInt(delBtn.dataset.id), delBtn);
            return;
        }
    });

    async function viewRevision(id) {
        currentRevisionId = id;
        const modalBody = document.getElementById('revisionModalBody');
        modalBody.innerHTML =
            '<div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></div>';
        new bootstrap.Modal(document.getElementById('revisionModal')).show();

        const url = cfg.revisionBaseUrl.replace('__id__', id);
        try {
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            const json = await res.json();
            modalBody.innerHTML = buildSnapshotHtml(json.snapshot ?? {});
        } catch {
            modalBody.innerHTML = '<div class="text-danger p-2">Ошибка загрузки ревизии.</div>';
        }
    }

    function buildSnapshotHtml(s) {
        const statusLabels = { 0: 'Черновик', 1: 'Опубликован', 2: 'На модерации' };
        const rows = [
            ['Название', s.title ?? '—'],
            ['Псевдоним', s.alias ?? '—'],
            ['Meta title', s.meta_title ?? '—'],
            ['Статус', statusLabels[s.status] ?? '—'],
            ['Дата публикации', s.published_at ? new Date(s.published_at).toLocaleString('ru') : '—'],
            ['Снят', s.unpublished_at ? new Date(s.unpublished_at).toLocaleString('ru') : '—'],
            ['Meta description', s.meta_description ?? '—'],
        ];

        let html = '<table class="table table-sm table-bordered mb-0 ztr-doc-rev-snapshot">';
        rows.forEach(([k, v]) => {
            html += `<tr><th>${k}</th><td>${v}</td></tr>`;
        });

        const fields = s.fields ?? {};
        if (Object.keys(fields).length) {
            html += '<tr><th colspan="2" class="text-muted ztr-doc-rev-section-hdr">Поля рубрики</th></tr>';
            Object.entries(fields).forEach(([fid, val]) => {
                const short = String(val ?? '').length > 200 ? String(val).substring(0, 200) + '...' : (val ?? '—');
                html += `<tr><th>field_id: ${fid}</th><td>${short}</td></tr>`;
            });
        }

        html += '</table>';
        return html;
    }

    async function deleteRevision(id, btn) {
        if (btn.disabled) {return;}
        const url = cfg.revisionBaseUrl.replace('__id__', id);
        btn.disabled = true;
        try {
            const res = await fetch(url, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            const json = await res.json().catch(() => ({}));
            if (json.ok) {
                showToast(json.message, 'success');
                loadRevisions();
                return;
            }
            showToast(json.message ?? 'Ошибка', 'error');
        } catch {
            showToast('Ошибка соединения', 'error');
        } finally {
            btn.disabled = false;
        }
    }

    const btnRestoreFromModal = document.getElementById('btnRestoreFromModal');
    btnRestoreFromModal?.addEventListener('click', async () => {
        if (!currentRevisionId || btnRestoreFromModal.disabled) {return;}
        btnRestoreFromModal.disabled = true;
        const url = cfg.revisionBaseUrl.replace('__id__', currentRevisionId) + '/restore';
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            const json = await res.json().catch(() => ({}));
            if (json.ok) {
                showToast(json.message, 'success');
                bootstrap.Modal.getInstance(document.getElementById('revisionModal'))?.hide();
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(json.message ?? 'Ошибка', 'error');
                btnRestoreFromModal.disabled = false;
            }
        } catch {
            showToast('Ошибка соединения', 'error');
            btnRestoreFromModal.disabled = false;
        }
    });

    document.getElementById('btnDeleteAllRevisions')?.addEventListener('click', () => {
        new bootstrap.Modal(document.getElementById('confirmDeleteAllModal')).show();
    });

    const btnConfirmDeleteAll = document.getElementById('btnConfirmDeleteAll');
    btnConfirmDeleteAll?.addEventListener('click', async () => {
        if (btnConfirmDeleteAll.disabled) {return;}
        btnConfirmDeleteAll.disabled = true;
        bootstrap.Modal.getInstance(document.getElementById('confirmDeleteAllModal'))?.hide();
        try {
            const res = await fetch(cfg.deleteAllUrl, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
            });
            const json = await res.json().catch(() => ({}));
            if (json.ok) {
                showToast(json.message, 'success');
                loadRevisions();
            } else {showToast(json.message ?? 'Ошибка', 'error');}
        } catch {
            showToast('Ошибка соединения', 'error');
        } finally {
            btnConfirmDeleteAll.disabled = false;
        }
    });

    const mediaDeleteUrl = cfg.mediaDeleteUrl || '/admin/upload/image';

    function validateFileSize(file, maxKb, fileName) {
        if (!maxKb || maxKb <= 0) {return true;}
        const sizeKb = Math.ceil(file.size / 1024);
        if (sizeKb > maxKb) {
            showToast(`Файл «${fileName || file.name}» слишком большой: ${sizeKb} КБ при лимите ${maxKb} КБ`, 'error');
            return false;
        }
        return true;
    }

    async function deleteMediaFile(url) {
        if (!url) {return false;}
        try {
            const res = await fetch(mediaDeleteUrl, {
                method: 'DELETE',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf,
                },
                body: JSON.stringify({ url }),
            });
            const json = await res.json().catch(() => ({}));
            return !!json.ok;
        } catch {
            return false;
        }
    }

    function buildImagePreview(url) {
        return (
            '<img src="' +
            url +
            '" class="ztr-doc-media-thumb">' +
            '<button type="button" class="btn btn-link btn-sm p-0 text-danger ms-2 field-media-delete"' +
            ' title="Удалить изображение"><i class="bi bi-x-circle"></i></button>'
        );
    }
    function buildFilePreview(url, fname) {
        return (
            'Текущий файл: <a href="' +
            url +
            '" target="_blank">' +
            fname +
            '</a>' +
            '<button type="button" class="btn btn-link btn-sm p-0 text-danger ms-2 field-media-delete"' +
            ' title="Удалить файл"><i class="bi bi-x-circle"></i></button>'
        );
    }

    document.addEventListener('change', async (e) => {
        const input = e.target;
        if (!input.matches('input[type="file"][name^="fields["]')) {return;}
        if (input.closest('.ztr-gallery-field')) {return;}
        if (!input.files.length) {return;}

        const uploadType = input.dataset.uploadType ?? 'image';
        const endpoint =
            uploadType === 'file' ? cfg.uploadFileUrl || '/admin/upload/file' : cfg.uploadUrl || '/admin/upload/image';

        const wrapper = input.closest('.mb-2') || input.parentElement;
        const pathInput = wrapper.querySelector('input[type="hidden"][name$="[path]"]');
        const oldUrl = pathInput?.value || '';

        const maxKb = parseInt(input.dataset.maxSizeKb || '0', 10) || 0;
        if (!validateFileSize(input.files[0], maxKb)) {
            input.value = '';
            return;
        }

        const fd = new FormData();
        fd.append('file', input.files[0]);

        try {
            const res = await fetch(endpoint, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: fd,
            });
            const json = await res.json().catch(() => ({}));

            if (!json.ok) {
                const msg = json.errors
                    ? Object.values(json.errors).flat().join(' ')
                    : (json.message ?? 'Ошибка загрузки');
                showToast(msg, 'error');
                return;
            }

            if (pathInput) {pathInput.value = json.url;}

            const imgPreview = wrapper.querySelector('.field-image-preview');
            if (imgPreview && uploadType === 'image') {
                imgPreview.innerHTML = buildImagePreview(json.url);
                imgPreview.classList.remove('d-none');
            }
            const filePreview = wrapper.querySelector('.field-file-preview');
            if (filePreview && uploadType === 'file') {
                const fname = json.original_name || json.url.split('/').pop();
                filePreview.innerHTML = buildFilePreview(json.url, fname);
                filePreview.classList.remove('d-none');
            }

            if (oldUrl && oldUrl.startsWith('/media/') && oldUrl !== json.url) {
                deleteMediaFile(oldUrl);
            }

            showToast('Файл загружен', 'success');
        } catch (e2) {
            showToast('Ошибка сети: ' + (e2.message ?? ''), 'error');
        } finally {
            input.value = '';
        }
    });

    const deleteMediaModalEl = document.getElementById('confirmDeleteMediaModal');
    const deleteMediaModal = deleteMediaModalEl ? new bootstrap.Modal(deleteMediaModalEl) : null;
    const deleteMediaConfirm = document.getElementById('btnConfirmDeleteMedia');

    function confirmDeleteMedia() {
        return new Promise((resolve) => {
            if (!deleteMediaModal || !deleteMediaConfirm) {
                resolve(window.confirm('Удалить этот файл безвозвратно?'));
                return;
            }
            let answered = false;
            const onConfirm = () => {
                answered = true;
                resolve(true);
                deleteMediaModal.hide();
            };
            const onHide = () => {
                deleteMediaConfirm.removeEventListener('click', onConfirm);
                deleteMediaModalEl.removeEventListener('hidden.bs.modal', onHide);
                if (!answered) {resolve(false);}
            };
            deleteMediaConfirm.addEventListener('click', onConfirm);
            deleteMediaModalEl.addEventListener('hidden.bs.modal', onHide);
            deleteMediaModal.show();
        });
    }

    document.addEventListener('click', async (e) => {
        const btn = e.target.closest('.field-media-delete');
        if (!btn) {return;}
        e.preventDefault();

        const wrapper = btn.closest('.mb-2');
        if (!wrapper) {return;}

        const pathInput = wrapper.querySelector('input[type="hidden"][name$="[path]"]');
        const url = pathInput?.value || '';
        if (!url) {return;}

        if (!(await confirmDeleteMedia())) {return;}

        btn.disabled = true;
        const ok = await deleteMediaFile(url);
        btn.disabled = false;

        if (!ok) {
            showToast('Не удалось удалить файл', 'error');
            return;
        }

        if (pathInput) {pathInput.value = '';}
        const fileInput = wrapper.querySelector('input[type="file"]');
        if (fileInput) {fileInput.value = '';}

        const imgPreview = wrapper.querySelector('.field-image-preview');
        if (imgPreview) {
            imgPreview.innerHTML = '';
            imgPreview.classList.add('d-none');
        }
        const filePreview = wrapper.querySelector('.field-file-preview');
        if (filePreview) {
            filePreview.innerHTML = '';
            filePreview.classList.add('d-none');
        }

        const descInput = wrapper.querySelector('input[name$="[description]"]');
        if (descInput) {descInput.value = '';}
        const altInput = wrapper.querySelector('input[name$="[alt]"]');
        if (altInput) {altInput.value = '';}

        showToast('Файл удалён', 'success');
    });

    (function initDocLinkPicker() {
        const searchUrl = cfg.docSearchUrl || '/admin/documents/search';
        const excludeId = cfg.documentId || null;

        const escapeHtml = (s) =>
            String(s ?? '').replace(
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

        document.querySelectorAll('.doc-link-field').forEach((wrapper) => {
            const input = wrapper.querySelector('.doc-link-search-input');
            const results = wrapper.querySelector('.doc-link-results');
            const hiddenId = wrapper.querySelector('.doc-link-id');
            const selected = wrapper.querySelector('.doc-link-selected');
            const search = wrapper.querySelector('.doc-link-search');
            const clearBtn = wrapper.querySelector('.doc-link-clear');
            const titleEl = wrapper.querySelector('.doc-link-selected-title');
            const rubricEl = wrapper.querySelector('.doc-link-selected-rubric');
            const rubricFilter = wrapper.dataset.rubricId || '';

            if (!input || !results || !hiddenId) {return;}

            let timer = null;

            const showResults = (html) => {
                results.innerHTML = html;
                results.classList.remove('d-none');
            };
            const hideResults = () => {
                results.classList.add('d-none');
                results.innerHTML = '';
            };

            const doSearch = async (q) => {
                const params = new URLSearchParams({ q, limit: '15' });
                if (rubricFilter) {params.set('rubric_id', rubricFilter);}
                if (excludeId) {params.set('exclude_id', excludeId);}

                try {
                    const res = await fetch(`${searchUrl}?${params.toString()}`, {
                        headers: { Accept: 'application/json' },
                    });
                    const json = await res.json().catch(() => ({}));
                    const items = json.results || [];

                    if (items.length === 0) {
                        showResults('<div class="doc-link-results-empty">Ничего не найдено</div>');
                        return;
                    }

                    showResults(
                        items
                            .map(
                                (d) => `
                        <div class="doc-link-result" data-id="${d.id}"
                             data-title="${escapeHtml(d.title)}"
                             data-rubric="${escapeHtml(d.rubric_title)}">
                            <div class="doc-link-result-title">${escapeHtml(d.title)}</div>
                            <div class="doc-link-result-meta">
                                <span class="badge bg-secondary-subtle text-secondary me-1">${escapeHtml(d.rubric_title)}</span>
                                <span class="badge bg-${escapeHtml(d.status_class)}-subtle text-${escapeHtml(d.status_class)}">${escapeHtml(d.status)}</span>
                                ${d.alias ? `<span class="ms-1 text-muted">/${escapeHtml(d.alias)}</span>` : ''}
                                <span class="ms-1 text-muted">#${d.id}</span>
                            </div>
                        </div>
                    `,
                            )
                            .join(''),
                    );
                } catch {
                    showResults('<div class="doc-link-results-empty text-danger">Ошибка загрузки</div>');
                }
            };

            input.addEventListener('input', () => {
                const q = input.value.trim();
                clearTimeout(timer);
                if (q.length < 2) {
                    hideResults();
                    return;
                }
                timer = setTimeout(() => doSearch(q), 250);
            });
results.addEventListener('click', (e) => {
                const row = e.target.closest('.doc-link-result');
                if (!row) {return;}

                hiddenId.value = row.dataset.id;
                titleEl.textContent = row.dataset.title;
                if (rubricEl) {rubricEl.textContent = '· ' + row.dataset.rubric;}

                selected.classList.remove('d-none');
                search.classList.add('d-none');
                input.value = '';
                hideResults();
            });
if (clearBtn) {
                clearBtn.addEventListener('click', () => {
                    hiddenId.value = '';
                    const anchorInput = wrapper.querySelector('.doc-link-anchor');
                    if (anchorInput) {anchorInput.value = '';}
                    selected.classList.add('d-none');
                    search.classList.remove('d-none');
                    input.focus();
                });
            }
document.addEventListener('click', (e) => {
                if (!wrapper.contains(e.target)) {
                    hideResults();
                }
            });
input.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {hideResults();}
            });
        });
    })();
(function initGallery() {
        const uploadUrl = cfg.uploadUrl || '/admin/upload/image';
document.addEventListener('click', (e) => {
            const addBtn = e.target.closest('.ztr-gallery-add');
            if (!addBtn) {return;}
            const wrapper = addBtn.closest('.ztr-gallery-field');
            wrapper?.querySelector('.ztr-gallery-file')?.click();
        });
document.addEventListener('change', async (e) => {
            const input = e.target.closest('.ztr-gallery-file');
            if (!input || !input.files.length) {return;}
            const wrapper = input.closest('.ztr-gallery-field');
            const container = wrapper.querySelector('.ztr-gallery-items');
            const max = parseInt(wrapper.dataset.maxItems || '0', 10);const maxKb = parseInt(wrapper.dataset.maxSizeKb || '0', 10);
            const files = Array.from(input.files);
            input.value = '';

            for (const file of files) {
                if (max > 0 && container.children.length >= max) {
                    showToast(`Достигнут лимит: не более ${max} изображений`, 'error');
                    break;
                }
                if (!validateFileSize(file, maxKb)) {
                    continue;
                }
                const fd = new FormData();
                fd.append('file', file);
                try {
                    const res = await fetch(uploadUrl, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: fd,
                    });
                    const json = await res.json().catch(() => ({}));
                    if (!json.ok || !json.url) {
                        const msg = json.errors
                            ? Object.values(json.errors).flat().join(' ')
                            : (json.message ?? 'Ошибка загрузки');
                        showToast(msg, 'error');
                        continue;
                    }
                    container.appendChild(buildGalleryItem(json.url));
                } catch (err) {
                    showToast('Ошибка сети: ' + (err.message ?? ''), 'error');
                }
            }
        });

        function buildGalleryItem(url) {
            const div = document.createElement('div');
            div.className = 'ztr-gallery-item';
            div.dataset.path = url;
            div.innerHTML = `
                <button type="button" class="ztr-gallery-drag btn btn-sm btn-link text-muted" title="Перетащить для сортировки"><i class="bi bi-grip-vertical"></i></button>
                <div class="ztr-gallery-thumb"><img src="${url}" alt=""></div>
                <div class="ztr-gallery-inputs">
                    <input type="text" class="form-control form-control-sm ztr-gallery-alt" placeholder="Alt (для SEO и скринридеров)">
                    <input type="text" class="form-control form-control-sm ztr-gallery-desc" placeholder="Описание (caption под картинкой)">
                </div>
                <button type="button" class="ztr-gallery-del btn btn-sm btn-link text-danger" title="Удалить"><i class="bi bi-x-circle"></i></button>
            `;
            return div;
        }
document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.ztr-gallery-del');
            if (!btn) {return;}
            e.preventDefault();
            const item = btn.closest('.ztr-gallery-item');
            if (!item) {return;}

            if (!(await confirmDeleteMedia())) {return;}

            btn.disabled = true;
            const path = item.dataset.path || '';
            if (path.startsWith('/media/')) {deleteMediaFile(path);
            }
            item.remove();
            showToast('Изображение удалено', 'success');
        });
if (typeof Sortable !== 'undefined') {
            document.querySelectorAll('.ztr-gallery-items').forEach((container) => {
                Sortable.create(container, {
                    animation: 150,
                    handle: '.ztr-gallery-drag',
                    ghostClass: 'ztr-gallery-ghost',
                });
            });
        }
    })();
(function initSlider() {
        document.querySelectorAll('.ztr-slider-field').forEach((wrapper) => {
            const input = wrapper.querySelector('.ztr-slider-input');
            const badge = wrapper.querySelector('.ztr-slider-value');
            const suffix = wrapper.dataset.suffix || '';
            if (!input || !badge) {return;}
            input.addEventListener('input', () => {
                badge.textContent = input.value + suffix;
            });
        });
    })();
(function initIconPicker() {
        const fields = document.querySelectorAll('.ztr-icon-field');
        if (!fields.length) {return;}

        const modal = ensureIconPickerModal();
        const bs = new bootstrap.Modal(modal);
        let activeWrapper = null;
        let activeCategoryPrefixes = [];
document.addEventListener('click', (e) => {
            const btn = e.target.closest('.ztr-icon-pick');
            if (!btn) {return;}
            activeWrapper = btn.closest('.ztr-icon-field');
const filterRaw = (activeWrapper?.dataset.categoryFilter || '').trim();
            activeCategoryPrefixes =
                filterRaw === ''
                    ? []
                    : filterRaw
                          .split(',')
                          .map((s) => s.trim().toLowerCase())
                          .filter(Boolean);

            renderGrid('');
            modal.querySelector('.ztr-icon-picker-search').value = '';
            bs.show();
            setTimeout(() => modal.querySelector('.ztr-icon-picker-search').focus(), 200);
        });
document.addEventListener('click', (e) => {
            const btn = e.target.closest('.ztr-icon-clear');
            if (!btn) {return;}
            const wrapper = btn.closest('.ztr-icon-field');
            applyIcon(wrapper, '');
            btn.remove();
        });
document.addEventListener('input', (e) => {
            const input = e.target.closest('.ztr-icon-input');
            if (!input) {return;}
            const wrapper = input.closest('.ztr-icon-field');
            const preview = wrapper?.querySelector('.ztr-icon-preview i');
            if (preview) {preview.className = input.value.trim() ? 'bi ' + input.value.trim() : '';}
        });
modal.querySelector('.ztr-icon-picker-search').addEventListener('input', (e) => {
            renderGrid(e.target.value.trim().toLowerCase());
        });
modal.querySelector('.ztr-icon-picker-grid').addEventListener('click', (e) => {
            const cell = e.target.closest('.ztr-icon-picker-cell');
            if (!cell || !activeWrapper) {return;}
            applyIcon(activeWrapper, cell.dataset.icon);
            bs.hide();
        });

        function applyIcon(wrapper, name) {
            if (!wrapper) {return;}
            const input = wrapper.querySelector('.ztr-icon-input');
            const preview = wrapper.querySelector('.ztr-icon-preview i');
            if (input) {input.value = name;}
            if (preview) {preview.className = name ? 'bi ' + name : '';}
if (name && !wrapper.querySelector('.ztr-icon-clear')) {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'btn btn-outline-secondary ztr-icon-clear';
                btn.title = 'Очистить';
                btn.innerHTML = '<i class="bi bi-x-lg"></i>';
                wrapper.querySelector('.input-group')?.appendChild(btn);
            }
        }

        function renderGrid(filter) {
            const grid = modal.querySelector('.ztr-icon-picker-grid');
            const all = window.ZentraBootstrapIcons || [];
let list = all;
            if (activeCategoryPrefixes.length > 0) {
                list = list.filter((n) => activeCategoryPrefixes.some((p) => n.startsWith(p)));
            }if (filter) {
                list = list.filter((n) => n.includes(filter));
            }

            const max = 600;
            const slice = list.slice(0, max);
            const counter = modal.querySelector('.ztr-icon-picker-counter');
            const filterHint =
                activeCategoryPrefixes.length > 0 ? ` (фильтр по полю: ${activeCategoryPrefixes.join(', ')})` : '';
            counter.textContent =
                list.length > max
                    ? `Показаны первые ${max} из ${list.length}${filterHint}. Уточните поиск.`
                    : `Найдено: ${list.length}${filterHint}`;

            grid.innerHTML = slice
                .map(
                    (name) =>
                        `<button type="button" class="ztr-icon-picker-cell" data-icon="bi-${name}" title="bi-${name}">` +
                        `<i class="bi bi-${name}"></i></button>`,
                )
                .join('');
        }

        function ensureIconPickerModal() {
            let el = document.getElementById('modalIconPicker');
            if (el) {return el;}
            el = document.createElement('div');
            el.id = 'modalIconPicker';
            el.className = 'modal fade';
            el.tabIndex = -1;
            el.innerHTML = `
                <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title"><i class="bi bi-grid-3x3-gap me-2"></i>Выбор иконки</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="text" class="form-control mb-2 ztr-icon-picker-search" placeholder="Поиск (heart, arrow, user…)" autocomplete="off">
                            <div class="ztr-icon-picker-counter text-muted small mb-2"></div>
                            <div class="ztr-icon-picker-grid"></div>
                        </div>
                    </div>
                </div>
            `;
            document.body.appendChild(el);
            return el;
        }
    })();

    (function initTagsCounter() {
        const inputs = document.querySelectorAll('input.ztr-tags-field[data-max-items]');
        inputs.forEach((input) => {
            const max = parseInt(input.dataset.maxItems || '0', 10);
            if (max <= 0) {return;}
let badge = input.parentElement.querySelector('.ztr-tags-counter');
            if (!badge) {
                badge = document.createElement('div');
                badge.className = 'form-text ztr-tags-counter';
                input.insertAdjacentElement('afterend', badge);
            }

            const update = () => {
                const tags = input.value
                    .split(',')
                    .map((s) => s.trim())
                    .filter(Boolean);
                const uniq = new Set(tags);
                const over = uniq.size > max;
                badge.textContent = `Тегов: ${uniq.size} / ${max}` + (over ? ' - превышен лимит' : '');
                badge.classList.toggle('text-danger', over);
                input.classList.toggle('is-invalid', over);
            };

            input.addEventListener('input', update);
            update();
        });
    })();

    (function initCodeFields() {
        if (typeof ace === 'undefined') {return;}

        document.querySelectorAll('textarea.ztr-codefield').forEach((textarea) => {
            if (textarea.dataset.aceInit === '1') {return;}
            textarea.dataset.aceInit = '1';

            const mode = (textarea.dataset.aceMode || 'text').trim();

            const wrap = document.createElement('div');
            wrap.className = 'ztr-codefield-ace';
            wrap.style.cssText =
                'min-height:240px;border:1px solid var(--bs-border-color);border-radius:.375rem;font-size:.8125rem';
            textarea.parentNode.insertBefore(wrap, textarea);
            textarea.style.display = 'none';

            const editor = ace.edit(wrap);
            editor.session.setMode('ace/mode/' + mode);
            editor.setTheme('ace/theme/monokai');
            editor.setOptions({
                fontSize: '13px',
                showPrintMargin: false,
                useWorker: false,
                wrap: true,
            });
            editor.setValue(textarea.value, -1);
editor.session.on('change', () => {
                textarea.value = editor.getValue();
            });
        });
    })();
(function initKeyValue() {
        const fields = document.querySelectorAll('.ztr-keyvalue-field');
        if (!fields.length) {return;}

        document.addEventListener('click', (e) => {const addBtn = e.target.closest('.ztr-keyvalue-add');
            if (addBtn) {
                const wrapper = addBtn.closest('.ztr-keyvalue-field');
                const items = wrapper?.querySelector('.ztr-keyvalue-items');
                if (!items) {return;}const max = parseInt(wrapper.dataset.maxItems || '0', 10);
                if (max > 0 && items.children.length >= max) {
                    showToast(`Достигнут лимит: не более ${max} пар`, 'error');
                    return;
                }
                items.appendChild(buildKvPair());
                return;
            }const delBtn = e.target.closest('.ztr-keyvalue-del');
            if (delBtn) {
                delBtn.closest('.ztr-keyvalue-item')?.remove();
            }
        });

        if (typeof Sortable !== 'undefined') {
            fields.forEach((wrapper) => {
                const items = wrapper.querySelector('.ztr-keyvalue-items');
                if (items) {
                    Sortable.create(items, {
                        animation: 150,
                        handle: '.ztr-keyvalue-drag',
                        ghostClass: 'ztr-keyvalue-ghost',
                    });
                }
            });
        }

        function buildKvPair() {
            const div = document.createElement('div');
            div.className = 'ztr-keyvalue-item';
            div.innerHTML = `
                <button type="button" class="ztr-keyvalue-drag btn btn-sm btn-link text-muted p-0" title="Перетащить"><i class="bi bi-grip-vertical"></i></button>
                <input type="text" class="form-control form-control-sm ztr-keyvalue-key"   value="" placeholder="Ключ">
                <input type="text" class="form-control form-control-sm ztr-keyvalue-value" value="" placeholder="Значение">
                <button type="button" class="ztr-keyvalue-del btn btn-sm btn-link text-danger p-0" title="Удалить"><i class="bi bi-x-circle"></i></button>
            `;
            return div;
        }
    })();

    (function initMaps() {
        const fields = document.querySelectorAll('.ztr-map-field');
        if (!fields.length) {return;}

        const cfgMaps = window.ZentraMaps || {};
        if (!cfgMaps.key) {return;}

        const provider = cfgMaps.provider || 'yandex';

        const sdkUrl =
            provider === 'google'
                ? `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(cfgMaps.key)}&loading=async`
                : `https://api-maps.yandex.ru/v3/?apikey=${encodeURIComponent(cfgMaps.key)}&lang=ru_RU`;

        loadScript(sdkUrl)
            .then(async () => {
                if (provider === 'yandex' && window.ymaps3) {
                    await ymaps3.ready;
                    fields.forEach(initYandexMap);
                } else if (provider === 'google' && window.google?.maps) {
                    fields.forEach(initGoogleMap);
                }
            })
            .catch((err) => {
                console.warn('Maps SDK load failed:', err);
            });

        function initYandexMap(wrapper) {
            const canvas = wrapper.querySelector('.ztr-map-canvas');
            if (!canvas) {return;}
            const latInput = wrapper.querySelector('.ztr-map-lat');
            const lngInput = wrapper.querySelector('.ztr-map-lng');

            const startLat = parseFloat(latInput.value) || 55.7558;
            const startLng = parseFloat(lngInput.value) || 37.6173;
            const hasValue = !!(latInput.value && lngInput.value);
const {YMap, YMapDefaultSchemeLayer, YMapDefaultFeaturesLayer, YMapMarker, YMapListener} = ymaps3;

            const map = new YMap(canvas, {
                location: {center: [startLng, startLat], zoom: 12},
            });
            map.addChild(new YMapDefaultSchemeLayer({}));
            map.addChild(new YMapDefaultFeaturesLayer({}));

            function makeMarkerEl() {
                const el = document.createElement('div');
                el.style.cssText = 'width:16px;height:16px;border-radius:50%;background:#c00;border:3px solid #fff;box-shadow:0 2px 4px rgba(0,0,0,.4);cursor:pointer;';
                return el;
            }

            let marker = null;
            if (hasValue) {
                marker = new YMapMarker({coordinates: [startLng, startLat]}, makeMarkerEl());
                map.addChild(marker);
            }

            map.addChild(new YMapListener({
                layer: 'any',
                onClick(obj, event) {
                    const [lng, lat] = event.coordinates;
                    latInput.value = lat.toFixed(6);
                    lngInput.value = lng.toFixed(6);
                    if (marker) {
                        marker.update({coordinates: [lng, lat]});
                    } else {
                        marker = new YMapMarker({coordinates: [lng, lat]}, makeMarkerEl());
                        map.addChild(marker);
                    }
                },
            }));
        }

        function initGoogleMap(wrapper) {
            const canvas = wrapper.querySelector('.ztr-map-canvas');
            if (!canvas) {return;}
            const latInput = wrapper.querySelector('.ztr-map-lat');
            const lngInput = wrapper.querySelector('.ztr-map-lng');

            const startLat = parseFloat(latInput.value) || 55.7558;
            const startLng = parseFloat(lngInput.value) || 37.6173;
            const hasValue = latInput.value && lngInput.value;

            const map = new google.maps.Map(canvas, {
                center: { lat: startLat, lng: startLng },
                zoom: 12,
            });

            let marker = null;
            if (hasValue) {
                marker = new google.maps.Marker({ position: { lat: startLat, lng: startLng }, map });
            }

            map.addListener('click', (e) => {
                const lat = e.latLng.lat();
                const lng = e.latLng.lng();
                latInput.value = lat.toFixed(6);
                lngInput.value = lng.toFixed(6);
                if (marker) {
                    marker.setPosition({ lat, lng });
                } else {
                    marker = new google.maps.Marker({ position: { lat, lng }, map });
                }
            });
        }

        function loadScript(src) {if (loadScript._cache?.[src]) {return loadScript._cache[src];}
            loadScript._cache = loadScript._cache || {};
            return (loadScript._cache[src] = new Promise((resolve, reject) => {
                const script = document.createElement('script');
                script.src = src;
                script.async = true;
                script.onload = resolve;
                script.onerror = reject;
                document.head.appendChild(script);
            }));
        }
    })();
(function initRepeater() {
        const fields = document.querySelectorAll('.ztr-repeater-field');
        if (!fields.length) {return;}

        fields.forEach((wrapper) => {
            const groupsEl = wrapper.querySelector('.ztr-repeater-groups');
            const template = wrapper.querySelector('.ztr-repeater-template');
            const addBtn = wrapper.querySelector('.ztr-repeater-add');
            const maxItems = parseInt(wrapper.dataset.maxItems || '0', 10);
            if (!groupsEl || !template || !addBtn) {return;}

            const checkLimit = () => {
                if (maxItems > 0 && groupsEl.children.length >= maxItems) {
                    addBtn.disabled = true;
                    addBtn.title = `Достигнут лимит: ${maxItems}`;
                } else {
                    addBtn.disabled = false;
                    addBtn.title = '';
                }
            };
addBtn.addEventListener('click', () => {
                if (maxItems > 0 && groupsEl.children.length >= maxItems) {
                    showToast(`Лимит: не более ${maxItems} групп`, 'error');
                    return;
                }
                const clone = template.content.firstElementChild.cloneNode(true);
                replaceIndex(clone, groupsEl.children.length);
                groupsEl.appendChild(clone);
                checkLimit();
            });
groupsEl.addEventListener('click', (e) => {
                const delBtn = e.target.closest('.ztr-repeater-del');
                if (delBtn) {
                    delBtn.closest('.ztr-repeater-group')?.remove();
                    renumberGroups(groupsEl);
                    checkLimit();
                    return;
                }
                const collapseBtn = e.target.closest('.ztr-repeater-collapse');
                if (collapseBtn) {
                    const group = collapseBtn.closest('.ztr-repeater-group');
                    if (!group) {return;}
                    const body = group.querySelector('.ztr-repeater-group-body');
                    const icon = collapseBtn.querySelector('i');
                    const collapsed = body.classList.toggle('d-none');
                    if (icon) {icon.className = collapsed ? 'bi bi-chevron-down' : 'bi bi-chevron-up';}
                }
            });
if (typeof Sortable !== 'undefined') {
                Sortable.create(groupsEl, {
                    animation: 150,
                    handle: '.ztr-repeater-drag',
                    ghostClass: 'ztr-repeater-ghost',
                    onEnd: () => renumberGroups(groupsEl),
                });
            }

            checkLimit();
        });

        function replaceIndex(node, newIdx) {
            node.dataset.idx = String(newIdx);
            const num = node.querySelector('.ztr-repeater-group-num');
            if (num) {num.textContent = String(newIdx + 1);}

            node.querySelectorAll('[name*="__INDEX__"]').forEach((el) => {
                el.name = el.name.replaceAll('__INDEX__', String(newIdx));
            });
            node.querySelectorAll('[id*="__INDEX__"]').forEach((el) => {
                el.id = el.id.replaceAll('__INDEX__', String(newIdx));
            });
            node.querySelectorAll('label[for*="__INDEX__"]').forEach((el) => {
                el.setAttribute('for', el.getAttribute('for').replaceAll('__INDEX__', String(newIdx)));
            });
        }

        function renumberGroups(groupsEl) {
            [...groupsEl.children].forEach((g, i) => {
                const num = g.querySelector('.ztr-repeater-group-num');
                if (num) {num.textContent = String(i + 1);}
                g.dataset.idx = String(i);g.querySelectorAll('[name^="fields["]').forEach((el) => {
                    el.name = el.name.replace(/(fields\[\d+\])\[\d+\]/, '$1[' + i + ']');
                });
            });
        }
    })();
(function initRelationMulti() {
        const fields = document.querySelectorAll('.ztr-rel-multi');
        if (!fields.length) {return;}

        const searchUrl = cfg.docSearchUrl || '/admin/documents/search';
        const excludeId = cfg.documentId || null;

        const escapeHtml = (s) =>
            String(s ?? '').replace(
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

        fields.forEach((wrapper) => {
            const itemsEl = wrapper.querySelector('.ztr-rel-multi-items');
            const input = wrapper.querySelector('.ztr-rel-multi-input');
            const results = wrapper.querySelector('.ztr-rel-multi-results');
            if (!itemsEl || !input || !results) {return;}

            const maxItems = parseInt(wrapper.dataset.maxItems || '0', 10);
            const rubricIds = (wrapper.dataset.rubricIds || '')
                .split(',')
                .map((s) => parseInt(s.trim(), 10))
                .filter((n) => n > 0);

            let timer = null;

            const showResults = (html) => {
                results.innerHTML = html;
                results.classList.remove('d-none');
            };
            const hideResults = () => {
                results.classList.add('d-none');
                results.innerHTML = '';
            };

            const collectExcludeIds = () => {
                const ids = [];
                wrapper.querySelectorAll('.ztr-rel-multi-chip').forEach((c) => {
                    const id = parseInt(c.dataset.id || '0', 10);
                    if (id > 0) {ids.push(id);}
                });
                if (excludeId) {ids.push(excludeId);}
                return ids;
            };

            const checkLimit = () => {
                if (maxItems > 0 && itemsEl.children.length >= maxItems) {
                    input.disabled = true;
                    input.placeholder = `Достигнут лимит: ${maxItems}`;
                } else {
                    input.disabled = false;
                    input.placeholder = 'Начните вводить название документа…';
                }
            };

            const doSearch = async (q) => {
                const params = new URLSearchParams({ q, limit: '15' });
                rubricIds.forEach((id) => params.append('rubric_ids[]', id));
                collectExcludeIds().forEach((id) => params.append('exclude_ids[]', id));

                try {
                    const res = await fetch(`${searchUrl}?${params.toString()}`, {
                        headers: { Accept: 'application/json' },
                    });
                    const json = await res.json().catch(() => ({}));
                    const items = json.results || [];

                    if (items.length === 0) {
                        showResults('<div class="doc-link-results-empty">Ничего не найдено</div>');
                        return;
                    }

                    showResults(
                        items
                            .map(
                                (d) => `
                        <div class="doc-link-result" data-id="${d.id}"
                             data-title="${escapeHtml(d.title)}"
                             data-rubric="${escapeHtml(d.rubric_title)}">
                            <div class="doc-link-result-title">${escapeHtml(d.title)}</div>
                            <div class="doc-link-result-meta">
                                <span class="badge bg-secondary-subtle text-secondary me-1">${escapeHtml(d.rubric_title)}</span>
                                <span class="badge bg-${escapeHtml(d.status_class)}-subtle text-${escapeHtml(d.status_class)}">${escapeHtml(d.status)}</span>
                                ${d.alias ? `<span class="ms-1 text-muted">/${escapeHtml(d.alias)}</span>` : ''}
                                <span class="ms-1 text-muted">#${d.id}</span>
                            </div>
                        </div>
                    `,
                            )
                            .join(''),
                    );
                } catch {
                    showResults('<div class="doc-link-results-empty text-danger">Ошибка загрузки</div>');
                }
            };

            input.addEventListener('input', () => {
                const q = input.value.trim();
                clearTimeout(timer);
                if (q.length < 2) {
                    hideResults();
                    return;
                }
                timer = setTimeout(() => doSearch(q), 250);
            });

            results.addEventListener('click', (e) => {
                const row = e.target.closest('.doc-link-result');
                if (!row) {return;}

                if (maxItems > 0 && itemsEl.children.length >= maxItems) {
                    showToast(`Лимит: не более ${maxItems} документов`, 'error');
                    return;
                }

                const id = row.dataset.id;
                const title = row.dataset.title;
                const rubric = row.dataset.rubric;
                itemsEl.appendChild(buildChip(id, title, rubric));
                input.value = '';
                hideResults();
                checkLimit();
            });
itemsEl.addEventListener('click', (e) => {
                const btn = e.target.closest('.ztr-rel-multi-del');
                if (!btn) {return;}
                btn.closest('.ztr-rel-multi-chip')?.remove();
                checkLimit();
            });

            document.addEventListener('click', (e) => {
                if (!wrapper.contains(e.target)) {hideResults();}
            });

            input.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {hideResults();}
            });

            if (typeof Sortable !== 'undefined') {
                Sortable.create(itemsEl, {
                    animation: 150,
                    handle: '.ztr-rel-multi-drag',
                    ghostClass: 'ztr-rel-multi-ghost',
                });
            }

            checkLimit();
        });

        function buildChip(id, title, rubric) {
            const div = document.createElement('div');
            div.className = 'ztr-rel-multi-chip';
            div.dataset.id = id;
            div.innerHTML = `
                <button type="button" class="ztr-rel-multi-drag btn btn-sm btn-link text-muted p-0" title="Перетащить"><i class="bi bi-grip-vertical"></i></button>
                <i class="bi bi-file-earmark-text"></i>
                <span class="ztr-rel-multi-chip-title">${escapeHtml(title)}</span>
                ${rubric ? `<span class="ztr-rel-multi-chip-rubric text-muted">· ${escapeHtml(rubric)}</span>` : ''}
                <button type="button" class="ztr-rel-multi-del btn btn-sm btn-link text-danger p-0 ms-auto" title="Убрать"><i class="bi bi-x-circle"></i></button>
            `;
            return div;
        }
    })();

    const revisionsCard = document.getElementById('revisionsCard');
    if (revisionsCard) {
        document.querySelectorAll('#docTabs a[data-bs-toggle="tab"]').forEach((tab) => {
            tab.addEventListener('shown.bs.tab', (e) => {
                revisionsCard.classList.toggle('d-none', e.target.getAttribute('href') !== '#tabContent');
            });
        });

        revisionsCard.classList.remove('d-none');
        loadRevisions();
    }

    (function () {
        const toggle = document.getElementById('publicCacheDisabled');
        const lbl    = toggle ? document.querySelector('label[for="publicCacheDisabled"]') : null;
        if (!toggle || !lbl) { return; }
        const sync = () => {
            lbl.textContent = toggle.checked
                ? 'Не кешировать эту страницу'
                : 'Кешировать (по настройкам сайта)';
        };
        toggle.addEventListener('change', sync);
        sync();
    })();
})();
