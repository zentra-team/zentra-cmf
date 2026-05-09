document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const rubricId = cfg.rubricId;
    const baseUrl = `/admin/rubrics/${rubricId}/fields`;

    const sortableEl = document.getElementById('fieldsSortable');
    if (sortableEl && cfg.canEdit) {
        Sortable.create(sortableEl, {
            handle: '.field-drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: async () => {
                const order = [...sortableEl.querySelectorAll('tr[data-id]')].map((tr) => parseInt(tr.dataset.id));
                try {
                    await fetch(`${baseUrl}/reorder`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': csrf,
                        },
                        body: JSON.stringify({ order }),
                    });
                } catch (err) {
                    showToast('Не удалось сохранить порядок: ' + (err.message ?? ''), 'error');
                }
            },
        });
    }

    const btnSaveFields = document.getElementById('btnSaveFields');
    if (btnSaveFields) {
        btnSaveFields.addEventListener('click', async () => {
            if (btnSaveFields.disabled) {return;}
            const rows = [...document.querySelectorAll('#fieldsSortable tr[data-id]')];
            btnSaveFields.disabled = true;

            const promises = rows.map((tr) => {
                const id = tr.dataset.id;
                return fetch(`${baseUrl}/${id}`, {
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
                showToast(allOk ? 'Изменения сохранены' : 'Часть полей не сохранилась', allOk ? 'success' : 'error');
            } catch (err) {
                showToast('Сетевая ошибка: ' + (err.message ?? 'не удалось отправить запрос'), 'error');
            } finally {
                btnSaveFields.disabled = false;
            }
        });
    }

    document.getElementById('btnSaveDescription')?.addEventListener('click', async () => {
        const btn = document.getElementById('btnSaveDescription');
        const desc = document.getElementById('rubricDescription').value;
        btn.disabled = true;

        try {
            const res = await fetch(`/admin/rubrics/${rubricId}`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ description: desc }),
            });
            const data = await res.json();
            showToast(data.ok ? 'Описание сохранено' : (data.message ?? 'Ошибка'), data.ok ? 'success' : 'error');
        } catch {
            showToast('Ошибка запроса', 'error');
        }

        btn.disabled = false;
    });

    document.getElementById('btnAddField').addEventListener('click', async () => {
        const title = document.getElementById('newFieldTitle').value.trim();
        const type = document.getElementById('newFieldType').value;
        const errEl = document.getElementById('addFieldError');
        const btn = document.getElementById('btnAddField');
        errEl.style.display = 'none';

        if (!title) {
            errEl.textContent = 'Введите название поля';
            errEl.style.display = 'block';
            return;
        }

        btn.disabled = true;

        try {
            const res = await fetch(baseUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ title, type }),
            });
            const data = await res.json();

            if (res.ok && data.ok) {
                showToast('Поле добавлено', 'success');
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

        btn.disabled = false;
    });

    document.querySelectorAll('.field-type-link').forEach((link) => {
        link.addEventListener('click', () => {
            const id = link.dataset.id;
            link.style.display = 'none';
            document.getElementById(`type-select-${id}`).style.display = 'inline-block';
            document.getElementById(`type-ok-${id}`).style.display = 'inline-block';
        });
    });

    document.querySelectorAll('.field-type-ok').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (btn.disabled) {return;}
            const id = btn.dataset.id;
            const sel = document.getElementById(`type-select-${id}`);
            const label = document.getElementById(`type-label-${id}`);

            btn.disabled = true;

            try {
                const res = await fetch(`${baseUrl}/${id}`, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ type: sel.value }),
                });
                const data = await res.json().catch(() => ({}));

                if (data.ok) {
                    label.textContent = data.type_name ?? sel.options[sel.selectedIndex].text;
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

    let aliasFieldId = null;
    const modalAlias = new bootstrap.Modal(document.getElementById('modalAlias'));

    document.querySelectorAll('.btn-change-alias').forEach((btn) => {
        btn.addEventListener('click', () => {
            aliasFieldId = btn.dataset.id;
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
            const res = await fetch(`${baseUrl}/${aliasFieldId}/alias`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ alias }),
            });
            const data = await res.json();

            if (res.ok && data.ok) {
                document.querySelector(`.field-alias-text[data-id="${aliasFieldId}"]`).textContent = data.alias;
                document.querySelector(`.btn-change-alias[data-id="${aliasFieldId}"]`).dataset.alias = data.alias;
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

    let configFieldId = null;
    let configFieldType = null;
    const modalConfig = new bootstrap.Modal(document.getElementById('modalFieldConfig'));

    const OPTIONS_TYPES = ['select', 'radio', 'checkbox_list'];

    const TYPE_BLOCKS = {
        text: ['Maxlength'],
        textarea: ['Maxlength', 'Rows'],
        wysiwyg: [],
        markdown: [],
        code: ['CodeMode'],
        number: ['Number'],
        checkbox: [],
        tags: ['MaxItems'],
        select: ['Options'],
        radio: ['Options'],
        checkbox_list: ['Options'],
        rating: [],
        slider: ['Slider'],
        repeater: ['Repeater'],
        price: ['Price'],
        keyvalue: ['MaxItems'],
        date: ['DateFormat'],
        datetime: ['DateFormat'],
        time: ['DateFormat'],
        email: ['Maxlength'],
        url: ['Maxlength'],
        phone: [],
        color: ['Color'],
        file: ['File'],
        doc_link: ['DocLink'],
        relation_multi: ['Relation'],
        image: ['Image'],
        gallery: ['Gallery'],
        youtube: ['Youtube'],
        icon: ['Icon'],
        video: [],
        map: [],
    };

    const ALL_BLOCKS = [
        'Options',
        'Slider',
        'Relation',
        'Repeater',
        'Maxlength',
        'Rows',
        'Number',
        'MaxItems',
        'File',
        'Image',
        'Gallery',
        'DocLink',
        'DateFormat',
        'CodeMode',
        'Price',
        'Icon',
        'Color',
        'Youtube',
    ];

    const NO_DEFAULT_TYPES = new Set([
        'checkbox',
        'checkbox_list',
        'relation_multi',
        'repeater',
        'price',
        'keyvalue',
        'file',
        'image',
        'gallery',
        'youtube',
        'video',
        'map',
        'doc_link',
    ]);

    document.querySelectorAll('.btn-field-config').forEach((btn) => {
        btn.addEventListener('click', () => {
            configFieldId = btn.dataset.id;
            configFieldType = btn.dataset.type ?? '';

            ALL_BLOCKS.forEach((b) => {
                const el = document.getElementById('config' + b + 'Block');
                if (el) {el.style.display = 'none';}
            });

            const blocks = TYPE_BLOCKS[configFieldType] ?? [];
            blocks.forEach((b) => {
                const el = document.getElementById('config' + b + 'Block');
                if (el) {el.style.display = '';}
            });

            const isCheckbox = configFieldType === 'checkbox';
            const hideDefault = NO_DEFAULT_TYPES.has(configFieldType);
            document.getElementById('configDefaultBlock').style.display = hideDefault ? 'none' : '';
            document.getElementById('configDefaultCheckboxBlock').style.display = isCheckbox ? '' : 'none';
            document.getElementById('configDefaultHint').style.display =
                (configFieldType === 'select' || configFieldType === 'radio') ? '' : 'none';
            document.getElementById('configDefault').value = btn.dataset.default ?? '';
            document.getElementById('configDefaultCheckbox').checked = btn.dataset.default === '1';
            document.getElementById('configDesc').value = btn.dataset.desc ?? '';

            document.getElementById('configOptions').value = btn.dataset.options ?? '';

            document.getElementById('configSliderMin').value = btn.dataset.cfgMin ?? '';
            document.getElementById('configSliderMax').value = btn.dataset.cfgMax ?? '';
            document.getElementById('configSliderStep').value = btn.dataset.cfgStep ?? '';
            document.getElementById('configSliderSuffix').value = btn.dataset.cfgSuffix ?? '';

            const relIds = (btn.dataset.cfgRubricIds ?? '')
                .split(',')
                .map((s) => s.trim())
                .filter((s) => s !== '');
            const relSelect = document.getElementById('configRelationRubrics');
            Array.from(relSelect.options).forEach((o) => {
                o.selected = relIds.includes(o.value);
            });
            document.getElementById('configRelationMaxItems').value = btn.dataset.cfgMaxItems ?? '';

            const repLink = document.getElementById('configRepeaterSubfieldsLink');
            repLink.href = `${baseUrl}/${configFieldId}/subfields`;
            document.getElementById('configRepeaterMaxItems').value = btn.dataset.cfgMaxItems ?? '';

            document.getElementById('configMaxlength').value = btn.dataset.cfgMaxlength ?? '';

            document.getElementById('configRows').value = btn.dataset.cfgRows ?? '';

            document.getElementById('configNumberMin').value = btn.dataset.cfgMin ?? '';
            document.getElementById('configNumberMax').value = btn.dataset.cfgMax ?? '';
            document.getElementById('configNumberStep').value = btn.dataset.cfgStep ?? '';

            const maxItemsLabel = document.getElementById('configMaxItemsLabel');
            if (configFieldType === 'tags') {maxItemsLabel.textContent = 'Максимальное количество тегов';}
            else if (configFieldType === 'keyvalue') {maxItemsLabel.textContent = 'Максимальное количество пар';}
            else {maxItemsLabel.textContent = 'Максимальное количество элементов';}
            document.getElementById('configMaxItems').value = btn.dataset.cfgMaxItems ?? '';

            document.getElementById('configFileExtensions').value = btn.dataset.cfgAcceptedExtensions ?? '';
            document.getElementById('configFileMaxSize').value = btn.dataset.cfgMaxSizeKb ?? '';

            document.getElementById('configImageMaxSize').value = btn.dataset.cfgMaxSizeKb ?? '';

            document.getElementById('configGalleryMaxItems').value = btn.dataset.cfgMaxItems ?? '';
            document.getElementById('configGalleryMaxSize').value = btn.dataset.cfgMaxSizeKb ?? '';

            document.getElementById('configDocLinkRubric').value = btn.dataset.cfgRubricId ?? '';

            document.getElementById('configDisplayFormat').value = btn.dataset.cfgDisplayFormat ?? '';

            document.getElementById('configCodeMode').value = btn.dataset.cfgMode || 'text';

            document.getElementById('configPriceDefaultCurrency').value = btn.dataset.cfgDefaultCurrency || 'RUB';

            document.getElementById('configIconCategoryFilter').value = btn.dataset.cfgCategoryFilter ?? '';

            document.getElementById('configColorFormat').value = btn.dataset.cfgFormat || 'hex';

            document.getElementById('configImageFormats').value = btn.dataset.cfgImageFormats ?? '';

            document.getElementById('configYoutubeWidth').value = btn.dataset.cfgYoutubeWidth ?? '';
            document.getElementById('configYoutubeHeight').value = btn.dataset.cfgYoutubeHeight ?? '';
            document.getElementById('configYoutubeFullscreen').checked = btn.dataset.cfgYoutubeFullscreen === '1';

            modalConfig.show();
        });
    });

    document.getElementById('btnConfigSave').addEventListener('click', async () => {
        const btn = document.getElementById('btnConfigSave');
        if (btn.disabled) {return;}
        btn.disabled = true;

        const defaultValue =
            configFieldType === 'checkbox'
                ? document.getElementById('configDefaultCheckbox').checked
                    ? '1'
                    : '0'
                : document.getElementById('configDefault').value;

        const payload = {
            default_value: defaultValue,
            description: document.getElementById('configDesc').value,
        };

        const intOrZero = (id) => parseInt(document.getElementById(id).value || '0', 10) || 0;
        const strVal = (id) => document.getElementById(id).value;

        if (OPTIONS_TYPES.includes(configFieldType)) {
            const raw = strVal('configOptions');
            const options = raw
                .split('\n')
                .map((s) => s.trim())
                .filter((s) => s !== '');
            payload.config = { options };
        } else if (configFieldType === 'slider') {
            payload.config = {
                min: strVal('configSliderMin'),
                max: strVal('configSliderMax'),
                step: strVal('configSliderStep'),
                suffix: strVal('configSliderSuffix'),
            };
        } else if (configFieldType === 'relation_multi') {
            const select = document.getElementById('configRelationRubrics');
            const rubric_ids = Array.from(select.selectedOptions)
                .map((o) => parseInt(o.value, 10))
                .filter((n) => n > 0);
            payload.config = {
                rubric_ids,
                max_items: intOrZero('configRelationMaxItems'),
            };
        } else if (configFieldType === 'repeater') {
            payload.config = {
                max_items: intOrZero('configRepeaterMaxItems'),
            };
        } else if (configFieldType === 'text' || configFieldType === 'email' || configFieldType === 'url') {
            payload.config = { maxlength: intOrZero('configMaxlength') };
        } else if (configFieldType === 'textarea') {
            payload.config = {
                maxlength: intOrZero('configMaxlength'),
                rows: intOrZero('configRows'),
            };
        } else if (configFieldType === 'number') {
            payload.config = {
                min: strVal('configNumberMin'),
                max: strVal('configNumberMax'),
                step: strVal('configNumberStep'),
            };
        } else if (configFieldType === 'tags' || configFieldType === 'keyvalue') {
            payload.config = { max_items: intOrZero('configMaxItems') };
        } else if (configFieldType === 'file') {
            payload.config = {
                accepted_extensions: strVal('configFileExtensions').trim(),
                max_size_kb: intOrZero('configFileMaxSize'),
            };
        } else if (configFieldType === 'gallery') {
            payload.config = {
                max_items: intOrZero('configGalleryMaxItems'),
                max_size_kb: intOrZero('configGalleryMaxSize'),
            };
        } else if (configFieldType === 'doc_link') {
            payload.config = {
                rubric_id: parseInt(strVal('configDocLinkRubric') || '0', 10) || 0,
            };
        } else if (configFieldType === 'date' || configFieldType === 'datetime' || configFieldType === 'time') {
            payload.config = { display_format: strVal('configDisplayFormat').trim() };
        } else if (configFieldType === 'code') {
            payload.config = { mode: strVal('configCodeMode') || 'text' };
        } else if (configFieldType === 'price') {
            payload.config = { default_currency: strVal('configPriceDefaultCurrency') || 'RUB' };
        } else if (configFieldType === 'icon') {
            payload.config = { category_filter: strVal('configIconCategoryFilter').trim() };
        } else if (configFieldType === 'color') {
            payload.config = { format: strVal('configColorFormat') || 'hex' };
        } else if (configFieldType === 'image') {
            payload.config = {
                accepted_formats: strVal('configImageFormats').trim(),
                max_size_kb: intOrZero('configImageMaxSize'),
            };
        } else if (configFieldType === 'youtube') {
            payload.config = {
                default_width: intOrZero('configYoutubeWidth') || 560,
                default_height: intOrZero('configYoutubeHeight') || 315,
                default_fullscreen: document.getElementById('configYoutubeFullscreen').checked,
            };
        }

        try {
            const res = await fetch(`${baseUrl}/${configFieldId}/config`, {
                method: 'PATCH',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(payload),
            });
            const data = await res.json().catch(() => ({}));

            if (data.ok) {
                const srcBtn = document.querySelector(`.btn-field-config[data-id="${configFieldId}"]`);
                if (srcBtn) {
                    srcBtn.dataset.default = payload.default_value;
                    srcBtn.dataset.desc = payload.description;
                    const cfg = payload.config ?? {};
                    if (OPTIONS_TYPES.includes(configFieldType)) {
                        srcBtn.dataset.options = (cfg.options ?? []).join('\n');
                    } else if (configFieldType === 'slider') {
                        srcBtn.dataset.cfgMin = cfg.min ?? '';
                        srcBtn.dataset.cfgMax = cfg.max ?? '';
                        srcBtn.dataset.cfgStep = cfg.step ?? '';
                        srcBtn.dataset.cfgSuffix = cfg.suffix ?? '';
                    } else if (configFieldType === 'relation_multi') {
                        srcBtn.dataset.cfgRubricIds = (cfg.rubric_ids ?? []).join(',');
                        srcBtn.dataset.cfgMaxItems = String(cfg.max_items ?? 0);
                    } else if (configFieldType === 'repeater') {
                        srcBtn.dataset.cfgMaxItems = String(cfg.max_items ?? 0);
                    } else if (configFieldType === 'text' || configFieldType === 'email' || configFieldType === 'url') {
                        srcBtn.dataset.cfgMaxlength = String(cfg.maxlength ?? '');
                    } else if (configFieldType === 'textarea') {
                        srcBtn.dataset.cfgMaxlength = String(cfg.maxlength ?? '');
                        srcBtn.dataset.cfgRows = String(cfg.rows ?? '');
                    } else if (configFieldType === 'number') {
                        srcBtn.dataset.cfgMin = cfg.min ?? '';
                        srcBtn.dataset.cfgMax = cfg.max ?? '';
                        srcBtn.dataset.cfgStep = cfg.step ?? '';
                    } else if (configFieldType === 'tags' || configFieldType === 'keyvalue') {
                        srcBtn.dataset.cfgMaxItems = String(cfg.max_items ?? 0);
                    } else if (configFieldType === 'file') {
                        srcBtn.dataset.cfgAcceptedExtensions = cfg.accepted_extensions ?? '';
                        srcBtn.dataset.cfgMaxSizeKb = String(cfg.max_size_kb ?? 0);
                    } else if (configFieldType === 'image') {
                        srcBtn.dataset.cfgImageFormats = cfg.accepted_formats ?? '';
                        srcBtn.dataset.cfgMaxSizeKb = String(cfg.max_size_kb ?? 0);
                    } else if (configFieldType === 'gallery') {
                        srcBtn.dataset.cfgMaxItems = String(cfg.max_items ?? 0);
                        srcBtn.dataset.cfgMaxSizeKb = String(cfg.max_size_kb ?? 0);
                    } else if (configFieldType === 'doc_link') {
                        srcBtn.dataset.cfgRubricId = String(cfg.rubric_id ?? '');
                    } else if (
                        configFieldType === 'date' ||
                        configFieldType === 'datetime' ||
                        configFieldType === 'time'
                    ) {
                        srcBtn.dataset.cfgDisplayFormat = cfg.display_format ?? '';
                    } else if (configFieldType === 'code') {
                        srcBtn.dataset.cfgMode = cfg.mode ?? 'text';
                    } else if (configFieldType === 'price') {
                        srcBtn.dataset.cfgDefaultCurrency = cfg.default_currency ?? 'RUB';
                    } else if (configFieldType === 'icon') {
                        srcBtn.dataset.cfgCategoryFilter = cfg.category_filter ?? '';
                    } else if (configFieldType === 'color') {
                        srcBtn.dataset.cfgFormat = cfg.format ?? 'hex';
                    } else if (configFieldType === 'youtube') {
                        srcBtn.dataset.cfgYoutubeWidth = String(cfg.default_width ?? '');
                        srcBtn.dataset.cfgYoutubeHeight = String(cfg.default_height ?? '');
                        srcBtn.dataset.cfgYoutubeFullscreen = cfg.default_fullscreen ? '1' : '0';
                    }
                }
            }

            modalConfig.hide();
            showToast(data.ok ? 'Параметры сохранены' : (data.message ?? 'Ошибка'), data.ok ? 'success' : 'error');
        } catch (err) {
            showToast('Сетевая ошибка: ' + (err.message ?? ''), 'error');
        } finally {
            btn.disabled = false;
        }
    });

    let deleteFieldId = null;
    const modalDeleteField = new bootstrap.Modal(document.getElementById('modalDeleteField'));

    document.querySelectorAll('.btn-delete-field').forEach((btn) => {
        btn.addEventListener('click', () => {
            deleteFieldId = btn.dataset.id;
            document.getElementById('deleteFieldTitle').textContent = '\u00ab' + btn.dataset.title + '\u00bb';
            document.getElementById('deleteFieldWarning').textContent = '';
            modalDeleteField.show();
        });
    });

    document.getElementById('btnDeleteFieldConfirm').addEventListener('click', async () => {
        const btn = document.getElementById('btnDeleteFieldConfirm');
        if (btn.disabled) {return;}
        btn.disabled = true;

        try {
            const res = await fetch(`${baseUrl}/${deleteFieldId}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json().catch(() => ({}));

            modalDeleteField.hide();

            if (data.ok) {
                document.getElementById('frow-' + deleteFieldId)?.remove();
                showToast('Поле удалено', 'success');
                return;
            }
            showToast(data.message ?? 'Ошибка', 'error');
        } catch (err) {
            showToast('Сетевая ошибка: ' + (err.message ?? ''), 'error');
        } finally {
            btn.disabled = false;
        }
    });

    const inApiUrlTpl = cfg.inApiUrlTpl || '';
    document.querySelectorAll('.field-in-api').forEach((cb) => {
        cb.addEventListener('change', async () => {
            const id = cb.dataset.id;
            const url = inApiUrlTpl.replace('/fields/0/in-api', '/fields/' + id + '/in-api');
            const prev = !cb.checked;
            cb.disabled = true;
            try {
                const params = new URLSearchParams();
                params.append('_method', 'PATCH');
                params.append('in_api', cb.checked ? '1' : '0');
                const res = await fetch(url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: params,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) {
                    cb.checked = prev;
                    showToast(data.message || 'Ошибка', 'error');
                } else {
                    showToast(data.in_api ? 'Поле включено в API' : 'Поле исключено из API', 'success');
                }
            } catch (e) {
                cb.checked = prev;
                showToast('Ошибка соединения', 'error');
            } finally {
                cb.disabled = false;
            }
        });
    });
});
