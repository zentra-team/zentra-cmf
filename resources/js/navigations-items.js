(function () {
    'use strict';

    const { csrf, navId, imageUploadUrl, imageDestroyUrl, docSearchUrl } = window.ZentraConfig;
    const baseUrl = '/admin/navigations/' + navId + '/items';

    function serializeTree(ul, parentId) {
        const result = [];
        ul.querySelectorAll(':scope > .nav-tree-item').forEach((li, pos) => {
            result.push({
                id: parseInt(li.dataset.id),
                parent_id: parentId ? parseInt(parentId) : null,
                position: pos,
            });
            const childUl = li.querySelector(':scope > .nav-tree-children');
            if (childUl) {result.push(...serializeTree(childUl, li.dataset.id));}
        });
        return result;
    }

    let reorderTimer = null;
    function saveReorder() {
        clearTimeout(reorderTimer);
        reorderTimer = setTimeout(async () => {
            const items = serializeTree(document.getElementById('navTreeRoot'), null);
            await fetch(baseUrl + '/reorder', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ items }),
            });
        }, 500);
    }

    function getListDepth(ul) {
        let depth = 0;
        let el = ul;
        while (el) {
            if (el.classList && el.classList.contains('nav-tree-children')) {depth++;}
            el = el.parentElement;
        }
        return depth;
    }

    function getItemSubtreeDepth(li) {
        const childUl = li.querySelector(':scope > .nav-tree-children');
        if (!childUl) {return 0;}
        let maxDepth = 0;
        childUl.querySelectorAll(':scope > .nav-tree-item').forEach((child) => {
            maxDepth = Math.max(maxDepth, 1 + getItemSubtreeDepth(child));
        });
        return maxDepth;
    }

    function initSortable(ul) {
        Sortable.create(ul, {
            group: 'nav-items',
            handle: '.nav-drag-handle',
            animation: 150,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onMove(evt) {
                return getListDepth(evt.to) + getItemSubtreeDepth(evt.dragged) <= 2;
            },
            onEnd: saveReorder,
        });
        ul.querySelectorAll(':scope > .nav-tree-item > .nav-tree-children').forEach((child) => initSortable(child));
    }
    initSortable(document.getElementById('navTreeRoot'));

    function collectItems(ul, depth, result) {
        ul.querySelectorAll(':scope > .nav-tree-item').forEach((li) => {
            const title = li.querySelector('.nav-item-title').textContent.trim();
            result.push({ id: li.dataset.id, title: '\u2014'.repeat(depth) + ' ' + title });
            const childUl = li.querySelector(':scope > .nav-tree-children');
            if (childUl) {collectItems(childUl, depth + 1, result);}
        });
        return result;
    }

    function fillParentSelect(excludeId) {
        const select = document.getElementById('itemParentId');
        select.innerHTML =
            '<option value="">\u2014 \u041a\u043e\u0440\u043d\u0435\u0432\u043e\u0439 \u0443\u0440\u043e\u0432\u0435\u043d\u044c \u2014</option>';
        const items = collectItems(document.getElementById('navTreeRoot'), 0, []);
        items.forEach((item) => {
            if (excludeId && item.id === excludeId) {return;}
            const opt = document.createElement('option');
            opt.value = item.id;
            opt.textContent = item.title;
            select.appendChild(opt);
        });
    }

    const btnImageUpload = document.getElementById('btnImageUpload');
    const imageFileInput = document.getElementById('imageFileInput');
    const imagePreview = document.getElementById('imagePreview');
    const imagePreviewWrap = document.getElementById('imagePreviewWrap');
    const imageUploadStatus = document.getElementById('imageUploadStatus');
    const itemImageField = document.getElementById('itemImage');

    function updatePreview(url) {
        if (url) {
            imagePreview.src = url;
            imagePreviewWrap.classList.add('ztr-nav-img-visible');
        } else {
            imagePreviewWrap.classList.remove('ztr-nav-img-visible');
        }
    }

    const itemModal = new bootstrap.Modal(document.getElementById('itemModal'));
    let editItemId = null;

    function openAddModal() {
        editItemId = null;
        document.getElementById('itemModalTitle').textContent =
            '\u0414\u043e\u0431\u0430\u0432\u0438\u0442\u044c \u043f\u0443\u043d\u043a\u0442 \u043c\u0435\u043d\u044e';
        document.getElementById('itemId').value = '';
        document.getElementById('itemTitle').value = '';
        document.getElementById('itemUrl').value = '';
        document.getElementById('itemTarget').value = '_self';
        document.getElementById('itemClass').value = '';
        document.getElementById('itemCssId').value = '';
        document.getElementById('itemStyle').value = '';
        document.getElementById('itemDescription').value = '';
        document.getElementById('itemIcon').value = '';
        document.getElementById('itemImage').value = '';
        document.getElementById('itemExtraHtml').value = '';
        updatePreview('');
        fillParentSelect(null);
        itemModal.show();
    }

    function openEditModal(btn) {
        editItemId = btn.dataset.itemId;
        document.getElementById('itemModalTitle').textContent =
            '\u0420\u0435\u0434\u0430\u043a\u0442\u0438\u0440\u043e\u0432\u0430\u0442\u044c \u043f\u0443\u043d\u043a\u0442';
        document.getElementById('itemId').value = editItemId;
        document.getElementById('itemTitle').value = btn.dataset.title;
        document.getElementById('itemUrl').value = btn.dataset.url;
        document.getElementById('itemTarget').value = btn.dataset.target || '_self';
        document.getElementById('itemClass').value = btn.dataset.class;
        document.getElementById('itemCssId').value = btn.dataset.idAttr;
        document.getElementById('itemStyle').value = btn.dataset.style;
        document.getElementById('itemDescription').value = btn.dataset.description;
        document.getElementById('itemIcon').value = btn.dataset.icon;
        document.getElementById('itemImage').value = btn.dataset.image;
        document.getElementById('itemExtraHtml').value = btn.dataset.extraHtml || '';
        updatePreview(btn.dataset.image);
        fillParentSelect(editItemId);
        document.getElementById('itemParentId').value = btn.dataset.parentId || '';
        itemModal.show();
    }

    document.getElementById('btnAddItem').addEventListener('click', openAddModal);
    const addFirstItem = document.getElementById('addFirstItem');
    if (addFirstItem) {
        addFirstItem.addEventListener('click', (e) => {
            e.preventDefault();
            openAddModal();
        });
    }

    document.addEventListener('click', function (e) {
        const editBtn = e.target.closest('.btn-item-edit');
        if (editBtn) {
            openEditModal(editBtn);
            return;
        }

        const deleteBtn = e.target.closest('.btn-item-delete');
        if (deleteBtn) {
            deleteItemId = deleteBtn.dataset.itemId;
            document.getElementById('deleteItemName').textContent = deleteBtn.dataset.itemTitle;
            deleteItemModal.show();
            return;
        }

        const toggleBtn = e.target.closest('.btn-status');
        if (toggleBtn) {
            toggleStatus(toggleBtn);
            return;
        }

        const collapseBtn = e.target.closest('.nav-item-toggle');
        if (collapseBtn) {
            toggleCollapse(collapseBtn);
            return;
        }
    });

    document.getElementById('btnItemSave').addEventListener('click', async () => {
        const title = document.getElementById('itemTitle').value.trim();
        if (!title) {
            showToast(
                '\u0412\u0432\u0435\u0434\u0438\u0442\u0435 \u043d\u0430\u0437\u0432\u0430\u043d\u0438\u0435',
                'error',
            );
            return;
        }

        const payload = {
            title: title,
            parent_id: document.getElementById('itemParentId').value || null,
            url: document.getElementById('itemUrl').value.trim() || null,
            target: document.getElementById('itemTarget').value,
            css_class: document.getElementById('itemClass').value.trim() || null,
            css_id: document.getElementById('itemCssId').value.trim() || null,
            css_style: document.getElementById('itemStyle').value.trim() || null,
            description: document.getElementById('itemDescription').value.trim() || null,
            icon: document.getElementById('itemIcon').value.trim() || null,
            image: document.getElementById('itemImage').value.trim() || null,
            extra_html: document.getElementById('itemExtraHtml').value.trim() || null,
        };

        const url = editItemId ? baseUrl + '/' + editItemId : baseUrl;
        const method = editItemId ? 'PUT' : 'POST';

        const res = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(payload),
        });
        const json = await res.json();

        if (json.ok) {
            showToast(json.message, 'success');
            itemModal.hide();
            setTimeout(() => location.reload(), 600);
        } else {
            const msg = json.errors
                ? Object.values(json.errors).flat().join(' ')
                : (json.message ?? '\u041e\u0448\u0438\u0431\u043a\u0430');
            showToast(msg, 'error');
        }
    });

    let deleteItemId = null;
    const deleteItemModal = new bootstrap.Modal(document.getElementById('deleteItemModal'));

    document.getElementById('btnDeleteItemConfirm').addEventListener('click', async () => {
        const res = await fetch(baseUrl + '/' + deleteItemId, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message, 'success');
            deleteItemModal.hide();
            setTimeout(() => location.reload(), 600);
        } else {
            showToast(json.message ?? '\u041e\u0448\u0438\u0431\u043a\u0430', 'error');
        }
    });

    function cascadeVisibility(parentLi, parentIsActive) {
        parentLi.querySelectorAll('.nav-tree-children .btn-status').forEach((childBtn) => {
            const row = childBtn.closest('.nav-item-row');

            if (!parentIsActive) {
                const count = parseInt(childBtn.dataset.parentHiddenCount || '0');

                if (count === 0) {
                    childBtn.dataset.ownWasActive = childBtn.classList.contains('btn-outline-success') ? '1' : '0';
                }
                childBtn.dataset.parentHiddenCount = count + 1;
                childBtn.innerHTML = '<i class="bi bi-eye-slash"></i>';
                childBtn.className = 'btn btn-sm btn-outline-secondary btn-status';
                row.classList.add('nav-item-inactive');
            } else {
                const count = Math.max(0, parseInt(childBtn.dataset.parentHiddenCount || '0') - 1);
                childBtn.dataset.parentHiddenCount = count;
                if (count === 0) {
                    if (childBtn.dataset.ownWasActive === '1') {
                        childBtn.innerHTML = '<i class="bi bi-eye"></i>';
                        childBtn.className = 'btn btn-sm btn-outline-success btn-status';
                        row.classList.remove('nav-item-inactive');
                    }
                    delete childBtn.dataset.ownWasActive;
                    delete childBtn.dataset.parentHiddenCount;
                }
            }
        });
    }

    async function toggleStatus(btn) {
        const itemId = btn.dataset.itemId;
        const res = await fetch(baseUrl + '/' + itemId + '/toggle', {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const json = await res.json();
        if (json.ok) {
            const li = btn.closest('.nav-tree-item');
            const row = btn.closest('.nav-item-row');
            if (json.is_active) {
                btn.innerHTML = '<i class="bi bi-eye"></i>';
                btn.title = 'Видимый - нажмите чтобы скрыть';
                btn.className = 'btn btn-sm btn-outline-success btn-status';
                row.classList.remove('nav-item-inactive');
            } else {
                btn.innerHTML = '<i class="bi bi-eye-slash"></i>';
                btn.title = 'Скрытый - нажмите чтобы показать';
                btn.className = 'btn btn-sm btn-outline-secondary btn-status';
                row.classList.add('nav-item-inactive');
            }
            cascadeVisibility(li, json.is_active);
        }
    }

    function toggleCollapse(btn) {
        const li = btn.closest('.nav-tree-item');
        const childUl = li.querySelector(':scope > .nav-tree-children');
        if (!childUl) {return;}

        const isHidden = childUl.style.display === 'none';
        childUl.style.display = isHidden ? '' : 'none';
        btn.classList.toggle('collapsed', !isHidden);
    }

    itemImageField.addEventListener('input', () => updatePreview(itemImageField.value));
    btnImageUpload.addEventListener('click', () => imageFileInput.click());

    document.getElementById('btnImageClear').addEventListener('click', async () => {
        const url = itemImageField.value.trim();
        if (url) {
            try {
                await fetch(imageDestroyUrl, {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ url }),
                });
            } catch {}
        }
        itemImageField.value = '';
        updatePreview('');
        imageUploadStatus.textContent = '';
    });

    imageFileInput.addEventListener('change', async () => {
        const file = imageFileInput.files[0];
        if (!file) {return;}

        btnImageUpload.disabled = true;
        btnImageUpload.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
        imageUploadStatus.textContent = '\u0417\u0430\u0433\u0440\u0443\u0437\u043a\u0430...';
        imageUploadStatus.style.color = 'var(--ztr-text-muted)';

        const fd = new FormData();
        fd.append('file', file);
        fd.append('_token', csrf);

        try {
            const res = await fetch(imageUploadUrl, { method: 'POST', body: fd });
            const json = await res.json();

            if (json.ok) {
                itemImageField.value = json.url;
                updatePreview(json.url);
                imageUploadStatus.textContent = '\u0417\u0430\u0433\u0440\u0443\u0436\u0435\u043d\u043e';
                imageUploadStatus.style.color = 'var(--bs-success, #22c55e)';
            } else {
                const msg =
                    json.errors?.file?.[0] ??
                    json.message ??
                    '\u041e\u0448\u0438\u0431\u043a\u0430 \u0437\u0430\u0433\u0440\u0443\u0437\u043a\u0438';
                imageUploadStatus.textContent = msg;
                imageUploadStatus.style.color = 'var(--bs-danger, #ef4444)';
            }
        } catch {
            imageUploadStatus.textContent =
                '\u041e\u0448\u0438\u0431\u043a\u0430 \u0441\u043e\u0435\u0434\u0438\u043d\u0435\u043d\u0438\u044f';
            imageUploadStatus.style.color = 'var(--bs-danger, #ef4444)';
        }

        btnImageUpload.disabled = false;
        btnImageUpload.innerHTML =
            '<i class="bi bi-upload me-1"></i>\u0417\u0430\u0433\u0440\u0443\u0437\u0438\u0442\u044c';
        imageFileInput.value = '';
    });

    document.getElementById('itemModal').addEventListener('show.bs.modal', () => {
        imageUploadStatus.textContent = '';
    });

    const itemUrlInput = document.getElementById('itemUrl');
    const docDropdown = document.getElementById('docSearchDropdown');
    let docSearchTimer = null;

    function escapeHtml(str) {
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    function hideDocDropdown() {
        docDropdown.style.display = 'none';
        docDropdown.innerHTML = '';
    }

    function renderDocDropdown(docs) {
        if (!docs.length) {
            hideDocDropdown();
            return;
        }
        docDropdown.innerHTML = docs
            .map(
                (doc) => `
            <div class="doc-search-item" data-url="${escapeHtml(doc.url)}">
                <span class="doc-search-title">${escapeHtml(doc.title)}</span>
                <span class="doc-search-url">${escapeHtml(doc.url)}</span>
                ${doc.is_draft ? '<span class="doc-search-draft">Черновик</span>' : ''}
            </div>
        `,
            )
            .join('');
        docDropdown.style.display = '';
        docDropdown.querySelectorAll('.doc-search-item').forEach((item) => {
            item.addEventListener('mousedown', (e) => {
                e.preventDefault(); // prevent blur before value is set
                itemUrlInput.value = item.dataset.url;
                hideDocDropdown();
            });
        });
    }

    itemUrlInput.addEventListener('input', () => {
        const val = itemUrlInput.value;
        // Don't intercept manual URL entry
        if (val.startsWith('/') || val.startsWith('http') || val.length < 2) {
            hideDocDropdown();
            return;
        }
        clearTimeout(docSearchTimer);
        docSearchTimer = setTimeout(async () => {
            const res = await fetch(docSearchUrl + '?q=' + encodeURIComponent(val));
            const docs = await res.json();
            renderDocDropdown(docs);
        }, 300);
    });

    itemUrlInput.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {hideDocDropdown();}
    });

    itemUrlInput.addEventListener('blur', () => {
        // Small delay so mousedown on dropdown item fires first
        setTimeout(hideDocDropdown, 150);
    });

    document.getElementById('itemModal').addEventListener('hide.bs.modal', hideDocDropdown);

    // ── Expand / collapse all ─────────────────────────────────────────────────
    document.getElementById('btnExpandAll').addEventListener('click', () => {
        document.querySelectorAll('.nav-tree-children').forEach((ul) => {
            ul.style.display = '';
        });
        document.querySelectorAll('.nav-item-toggle').forEach((btn) => {
            btn.classList.remove('collapsed');
        });
    });
    document.getElementById('btnCollapseAll').addEventListener('click', () => {
        document.querySelectorAll('.nav-tree-children').forEach((ul) => {
            ul.style.display = 'none';
        });
        document.querySelectorAll('.nav-item-toggle').forEach((btn) => {
            btn.classList.add('collapsed');
        });
    });
})();
