document.addEventListener('DOMContentLoaded', () => {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const rubricTitle = document.getElementById('rubricTitle');
    const rubricAlias = document.getElementById('rubricAlias');

    if (rubricTitle && rubricAlias) {
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

        let aliasManual = false;
        rubricAlias.addEventListener('input', () => {
            aliasManual = true;
        });
        rubricTitle.addEventListener('input', () => {
            if (aliasManual) {return;}
            rubricAlias.value = toAlias(rubricTitle.value);
        });

        const createForm = rubricTitle.closest('form');
        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = createForm.querySelector('[type="submit"]');
            if (btn.disabled) {return;}
            btn.disabled = true;

            try {
                const res = await fetch(createForm.action, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({
                        title: rubricTitle.value.trim(),
                        alias: rubricAlias.value.trim() || null,
                        layout_id: createForm.querySelector('[name="layout_id"]')?.value || '',
                        color: createForm.querySelector('[name="color"]')?.value || null,
                        _token: csrf,
                    }),
                });
                const json = await res.json().catch(() => ({}));

                if (json.ok) {
                    showToast(json.message, 'success');
                    setTimeout(() => {
                        window.location.href = json.redirect;
                    }, 600);
                    return;
                }
                const msg = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Ошибка');
                showToast(msg, 'error');
            } catch (err) {
                showToast('Сетевая ошибка: ' + (err.message ?? 'не удалось отправить запрос'), 'error');
            } finally {
                btn.disabled = false;
            }
        });
    }

    const sortableEl = document.getElementById('rubricsSortable');
    if (sortableEl && cfg.canEdit) {
        Sortable.create(sortableEl, {
            handle: '.rubric-drag-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            onEnd: async () => {
                const order = [...sortableEl.querySelectorAll('tr[data-id]')].map((tr) => parseInt(tr.dataset.id));

                try {
                    await fetch(cfg.orderUrl, {
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

    const btnSaveAll = document.getElementById('btnSaveAll');
    if (btnSaveAll) {
        btnSaveAll.addEventListener('click', async () => {
            const rows = [];

            document.querySelectorAll('#rubricsSortable tr[data-id]').forEach((tr) => {
                const id = parseInt(tr.dataset.id);
                rows.push({
                    id,
                    title: tr.querySelector('.rubric-title')?.value.trim() ?? '',
                    alias: tr.querySelector('.rubric-alias')?.value.trim() || null,
                    layout_id: tr.querySelector('.rubric-layout')?.value || '',
                    color: tr.querySelector('.rubric-color')?.value || null,
                });
            });

            btnSaveAll.disabled = true;
            btnSaveAll.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Сохранение...';

            try {
                const res = await fetch(cfg.saveAllUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ rubrics: rows }),
                });
                const data = await res.json().catch(() => ({}));

                if (data.ok) {
                    showToast(data.message ?? 'Сохранено', 'success');
                } else if (data.errors) {
                    const messages = [...new Set(Object.values(data.errors).flat())];
                    showToast(messages.join(' '), 'error');
                } else {
                    showToast(data.message ?? 'Ошибка сохранения', 'error');
                }
            } catch (err) {
                showToast('Сетевая ошибка: ' + (err.message ?? ''), 'error');
            }

            btnSaveAll.disabled = false;
            btnSaveAll.innerHTML = '<i class="bi bi-floppy me-1"></i>Сохранить изменения';
        });
    }

    const baseDocsUrl = cfg.baseUrl;

    document.querySelectorAll('.btn-docs-count').forEach((btn) => {
        btn.addEventListener('click', async (e) => {
            e.preventDefault();
            const id = btn.dataset.id;
            const cell = document.getElementById(`docs-cell-${id}`);

            cell.innerHTML = '<span class="spinner-border spinner-border-sm ztr-rubrics-spinner-xs"></span>';

            try {
                const res = await fetch(`${baseDocsUrl}/${id}/docs-count`, {
                    headers: { Accept: 'application/json' },
                });
                const data = await res.json().catch(() => ({}));
                const count = parseInt(data.count ?? 0) || 0;
                const span = document.createElement('span');
                span.className = 'text-muted ztr-rubrics-small';
                span.textContent = String(count);
                cell.replaceChildren(span);
            } catch (err) {
                const span = document.createElement('span');
                span.className = 'text-danger ztr-rubrics-small';
                span.textContent = 'ошибка';
                cell.replaceChildren(span);
                showToast('Не удалось получить число документов: ' + (err.message ?? ''), 'error');
            }
        });
    });

    let copyRubricId = null;
    const modalCopyRubric = new bootstrap.Modal(document.getElementById('modalCopyRubric'));

    document.querySelectorAll('.btn-copy-rubric').forEach((btn) => {
        btn.addEventListener('click', () => {
            copyRubricId = btn.dataset.id;
            document.getElementById('copyRubricSource').textContent = '\u00ab' + btn.dataset.title + '\u00bb';
            document.getElementById('copyRubricTitle').value = btn.dataset.title + ' (копия)';
            document.getElementById('copyRubricAlias').value = btn.dataset.alias ? btn.dataset.alias + '-copy' : '';
            document.getElementById('copyRubricError').style.display = 'none';
            modalCopyRubric.show();
            setTimeout(() => document.getElementById('copyRubricTitle').select(), 300);
        });
    });

    document.getElementById('btnCopyRubricConfirm').addEventListener('click', async () => {
        const title = document.getElementById('copyRubricTitle').value.trim();
        const alias = document.getElementById('copyRubricAlias').value.trim();
        const errEl = document.getElementById('copyRubricError');
        const btn = document.getElementById('btnCopyRubricConfirm');
        errEl.style.display = 'none';

        if (!title) {
            errEl.textContent = 'Введите название';
            errEl.style.display = 'block';
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

        try {
            const res = await fetch(`${baseDocsUrl}/${copyRubricId}/copy`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ title, alias: alias || null }),
            });
            const data = await res.json();

            if (res.ok && data.ok) {
                window.location = data.redirect;
                return;
            }

            const msg = data.errors?.title?.[0] ?? data.errors?.alias?.[0] ?? data.message ?? 'Ошибка';
            errEl.textContent = msg;
            errEl.style.display = 'block';
        } catch {
            errEl.textContent = 'Ошибка запроса';
            errEl.style.display = 'block';
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-copy me-1"></i>Копировать';
    });

    let deleteRubricId = null;
    const modalDeleteRubric = new bootstrap.Modal(document.getElementById('modalDeleteRubric'));

    document.querySelectorAll('.btn-delete-rubric').forEach((btn) => {
        btn.addEventListener('click', () => {
            deleteRubricId = btn.dataset.id;
            document.getElementById('deleteRubricTitle').textContent = '\u00ab' + btn.dataset.title + '\u00bb';
            modalDeleteRubric.show();
        });
    });

    document.getElementById('btnDeleteRubricConfirm').addEventListener('click', async () => {
        const btn = document.getElementById('btnDeleteRubricConfirm');
        if (btn.disabled) {return;}
        btn.disabled = true;

        try {
            const res = await fetch(`${baseDocsUrl}/${deleteRubricId}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json().catch(() => ({}));

            modalDeleteRubric.hide();

            if (data.ok) {
                document.getElementById('row-' + deleteRubricId)?.remove();
                showToast('Рубрика удалена', 'success');

                if (!sortableEl || sortableEl.querySelectorAll('tr[data-id]').length === 0) {
                    const pane = document.getElementById('tabRubrics');
                    if (pane) {
                        const p = document.createElement('p');
                        p.className = 'text-muted ztr-rubrics-smaller';
                        p.textContent = 'Рубрик пока нет. Создайте первую во вкладке «Создать».';
                        pane.replaceChildren(p);
                    }
                }
                return;
            }
            showToast(data.message ?? 'Ошибка', 'error');
        } catch (err) {
            showToast('Сетевая ошибка: ' + (err.message ?? 'не удалось отправить запрос'), 'error');
        } finally {
            btn.disabled = false;
        }
    });

    const modalSeoEl = document.getElementById('modalRubricSeo');
    if (modalSeoEl) {
        const modalSeo = new bootstrap.Modal(modalSeoEl);
        const seoId = document.getElementById('rubricSeoId');
        const seoInclude = document.getElementById('rubricSeoInclude');
        const seoCf = document.getElementById('rubricSeoChangefreq');
        const seoPr = document.getElementById('rubricSeoPriority');
        const seoIcf = document.getElementById('rubricSeoIndexChangefreq');
        const seoIpr = document.getElementById('rubricSeoIndexPriority');
        const cacheDisabled = document.getElementById('rubricCacheDisabled');
        const cacheTtl = document.getElementById('rubricCacheTtl');
        const btnSeoSave = document.getElementById('btnRubricSeoSave');
        const seoUrlTpl = window.ZentraConfig?.seoUrlTpl || '';

        document.querySelectorAll('.btn-rubric-seo').forEach((btn) => {
            btn.addEventListener('click', () => {
                seoId.value = btn.dataset.id;
                seoInclude.checked = btn.dataset.include === '1';
                seoCf.value = btn.dataset.changefreq || '';
                seoPr.value = btn.dataset.priority || '';
                seoIcf.value = btn.dataset.indexChangefreq || '';
                seoIpr.value = btn.dataset.indexPriority || '';
                cacheDisabled.checked = btn.dataset.cacheDisabled === '1';
                cacheTtl.value = btn.dataset.cacheTtl || '';
                modalSeo.show();
            });
        });

        const modalRssEl = document.getElementById('modalRubricRss');
        if (modalRssEl) {
            const modalRss = new bootstrap.Modal(modalRssEl);
            const rssId = document.getElementById('rubricRssId');
            const rssUrl = document.getElementById('rubricRssUrl');
            const rssEnabled = document.getElementById('rubricRssEnabled');
            const rssTitleInput = document.getElementById('rubricRssTitleInput');
            const rssDesc = document.getElementById('rubricRssDescription');
            const rssLimit = document.getElementById('rubricRssLimit');
            const rssDescField = document.getElementById('rubricRssDescField');
            const rssImageField = document.getElementById('rubricRssImageField');
            const rssCategoryField = document.getElementById('rubricRssCategoryField');
            const btnRssSave = document.getElementById('btnRubricRssSave');

            const rssUrlTpl = window.ZentraConfig?.rssUrlTpl || '';
            const fieldsMetaUrlTpl = window.ZentraConfig?.fieldsMetaUrlTpl || '';
            const publicUrl = window.ZentraConfig?.publicUrl || '';

            const DESC_TYPES = new Set(['text', 'textarea', 'wysiwyg', 'markdown']);
            const IMAGE_TYPES = new Set(['image', 'gallery']);
            const CATEGORY_TYPES = new Set(['tags']);

            function fillSelect(select, fields, allowedTypes, currentId, emptyLabel) {
                select.innerHTML = '';
                const empty = document.createElement('option');
                empty.value = '';
                empty.textContent = emptyLabel;
                select.appendChild(empty);
                fields
                    .filter((f) => allowedTypes.has(f.type))
                    .forEach((f) => {
                        const opt = document.createElement('option');
                        opt.value = f.id;
                        opt.textContent = f.title + ' (' + f.alias + ' - ' + f.type + ')';
                        if (currentId && Number(currentId) === Number(f.id)) {opt.selected = true;}
                        select.appendChild(opt);
                    });
            }

            document.querySelectorAll('.btn-rubric-rss').forEach((btn) => {
                btn.addEventListener('click', async () => {
                    const id = btn.dataset.id;
                    const alias = btn.dataset.alias || '';
                    rssId.value = id;
                    rssUrl.textContent = (publicUrl || '') + '/' + alias + '/feed.xml';
                    rssEnabled.checked = btn.dataset.rssEnabled === '1';
                    rssTitleInput.value = btn.dataset.rssTitle || '';
                    rssDesc.value = btn.dataset.rssDescription || '';
                    rssLimit.value = btn.dataset.rssLimit || '';

                    rssDescField.innerHTML = '<option value="">— загрузка… —</option>';
                    rssImageField.innerHTML = '<option value="">— загрузка… —</option>';
                    rssCategoryField.innerHTML = '<option value="">— загрузка… —</option>';
                    modalRss.show();

                    try {
                        const url = fieldsMetaUrlTpl.replace('/0/fields-meta', '/' + id + '/fields-meta');
                        const res = await fetch(url, { headers: { Accept: 'application/json' } });
                        const data = await res.json();
                        if (!res.ok || !data.ok) {
                            showToast(data.message || 'Не удалось загрузить поля рубрики', 'error');
                            return;
                        }
                        const fields = data.fields || [];
                        fillSelect(
                            rssDescField,
                            fields,
                            DESC_TYPES,
                            btn.dataset.rssDescField,
                            '— не указано (используется meta_description документа) —',
                        );
                        fillSelect(rssImageField, fields, IMAGE_TYPES, btn.dataset.rssImageField, '— не указано —');
                        fillSelect(
                            rssCategoryField,
                            fields,
                            CATEGORY_TYPES,
                            btn.dataset.rssCategoryField,
                            '— не указано —',
                        );
                    } catch (_e) {
                        showToast('Ошибка соединения', 'error');
                    }
                });
            });

            btnRssSave?.addEventListener('click', async () => {
                const id = rssId.value;
                if (!id) {return;}
                btnRssSave.disabled = true;
                try {
                    const url = rssUrlTpl.replace('/0/rss', '/' + id + '/rss');
                    const params = new URLSearchParams();
                    params.append('_method', 'PATCH');
                    params.append('rss_enabled', rssEnabled.checked ? '1' : '0');
                    params.append('rss_title', rssTitleInput.value);
                    params.append('rss_description', rssDesc.value);
                    params.append('rss_limit', rssLimit.value);
                    params.append('rss_description_field_id', rssDescField.value);
                    params.append('rss_image_field_id', rssImageField.value);
                    params.append('rss_category_field_id', rssCategoryField.value);

                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: params,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.ok) {
                        showToast(data.message || 'Ошибка', 'error');
                        return;
                    }
                    modalRss.hide();
                    showToast(data.message, 'success');

                    const btn = document.querySelector('.btn-rubric-rss[data-id="' + id + '"]');
                    if (btn) {
                        btn.dataset.rssEnabled = rssEnabled.checked ? '1' : '0';
                        btn.dataset.rssTitle = rssTitleInput.value;
                        btn.dataset.rssDescription = rssDesc.value;
                        btn.dataset.rssLimit = rssLimit.value;
                        btn.dataset.rssDescField = rssDescField.value;
                        btn.dataset.rssImageField = rssImageField.value;
                        btn.dataset.rssCategoryField = rssCategoryField.value;
                    }
                } catch (_e) {
                    showToast('Ошибка соединения', 'error');
                } finally {
                    btnRssSave.disabled = false;
                }
            });
        }

        const modalApiEl = document.getElementById('modalRubricApi');
        if (modalApiEl) {
            const modalApi = new bootstrap.Modal(modalApiEl);
            const apiAlias = document.getElementById('rubricApiAlias');
            const apiId = document.getElementById('rubricApiId');
            const apiEnabled = document.getElementById('rubricApiEnabled');
            const apiDefault = document.getElementById('rubricApiDefaultLimit');
            const apiMax = document.getElementById('rubricApiMaxLimit');
            const btnApiSave = document.getElementById('btnRubricApiSave');

            const apiUrlTpl = window.ZentraConfig?.apiUrlTpl || '';

            document.querySelectorAll('.btn-rubric-api').forEach((btn) => {
                btn.addEventListener('click', () => {
                    apiId.value = btn.dataset.id;
                    apiAlias.textContent = btn.dataset.alias || '';
                    apiEnabled.checked = btn.dataset.apiEnabled === '1';
                    apiDefault.value = btn.dataset.apiDefaultLimit || '';
                    apiMax.value = btn.dataset.apiMaxLimit || '';
                    modalApi.show();
                });
            });

            btnApiSave?.addEventListener('click', async () => {
                const id = apiId.value;
                if (!id) {return;}
                btnApiSave.disabled = true;
                try {
                    const url = apiUrlTpl.replace('/0/api', '/' + id + '/api');
                    const params = new URLSearchParams();
                    params.append('_method', 'PATCH');
                    params.append('api_enabled', apiEnabled.checked ? '1' : '0');
                    params.append('api_default_limit', apiDefault.value);
                    params.append('api_max_limit', apiMax.value);

                    const res = await fetch(url, {
                        method: 'POST',
                        headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                        body: params,
                    });
                    const data = await res.json().catch(() => ({}));
                    if (!res.ok || !data.ok) {
                        showToast(data.message || 'Ошибка', 'error');
                        return;
                    }
                    modalApi.hide();
                    showToast(data.message, 'success');

                    const btn = document.querySelector('.btn-rubric-api[data-id="' + id + '"]');
                    if (btn) {
                        btn.dataset.apiEnabled = apiEnabled.checked ? '1' : '0';
                        btn.dataset.apiDefaultLimit = apiDefault.value;
                        btn.dataset.apiMaxLimit = apiMax.value;
                    }
                } catch (_e) {
                    showToast('Ошибка соединения', 'error');
                } finally {
                    btnApiSave.disabled = false;
                }
            });
        }

        btnSeoSave?.addEventListener('click', async () => {
            const id = seoId.value;
            if (!id) {return;}
            btnSeoSave.disabled = true;
            try {
                const url = seoUrlTpl.replace('/0/seo', '/' + id + '/seo');
                const params = new URLSearchParams();
                params.append('_method', 'PATCH');
                params.append('sitemap_include', seoInclude.checked ? '1' : '0');
                params.append('sitemap_changefreq', seoCf.value);
                params.append('sitemap_priority', seoPr.value);
                params.append('sitemap_index_changefreq', seoIcf.value);
                params.append('sitemap_index_priority', seoIpr.value);
                params.append('public_cache_disabled', cacheDisabled.checked ? '1' : '0');
                params.append('public_cache_ttl', cacheTtl.value);

                const res = await fetch(url, {
                    method: 'POST',
                    headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: params,
                });
                const data = await res.json().catch(() => ({}));
                if (!res.ok || !data.ok) {
                    showToast(data.message || 'Ошибка', 'error');
                    return;
                }
                modalSeo.hide();
                showToast(data.message, 'success');

                const btn = document.querySelector('.btn-rubric-seo[data-id="' + id + '"]');
                if (btn) {
                    btn.dataset.include = seoInclude.checked ? '1' : '0';
                    btn.dataset.changefreq = seoCf.value;
                    btn.dataset.priority = seoPr.value;
                    btn.dataset.indexChangefreq = seoIcf.value;
                    btn.dataset.indexPriority = seoIpr.value;
                }
            } catch (_e) {
                showToast('Ошибка соединения', 'error');
            } finally {
                btnSeoSave.disabled = false;
            }
        });
    }
});
