(function () {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const createRubric = document.getElementById('createRubric');
    const btnCreateSubmit = document.getElementById('btnCreateSubmit');
    if (createRubric && btnCreateSubmit) {
        createRubric.addEventListener('change', () => {
            btnCreateSubmit.disabled = !createRubric.value;
        });
    }

    const titleInput = document.getElementById('createTitle');
    const aliasInput = document.getElementById('createAlias');
    const aliasPreview = document.getElementById('aliasPreview');
    let aliasManual = false;

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
        const slug = translit(titleInput.value);
        aliasInput.value = slug;
        if (aliasPreview) {aliasPreview.textContent = slug ? `[request:${slug}]` : '';}
    });
    aliasInput?.addEventListener('input', () => {
        aliasManual = aliasInput.value.length > 0;
        if (aliasPreview) {aliasPreview.textContent = aliasInput.value ? `[request:${aliasInput.value}]` : '';}
    });

    document.addEventListener('click', function (e) {
        const copyBtn = e.target.closest('.btn-copy-tag');
        if (copyBtn) {
            navigator.clipboard.writeText(copyBtn.dataset.tag);
            showToast('Тег скопирован', 'success');
            return;
        }

        const copyReqBtn = e.target.closest('.btn-req-copy');
        if (copyReqBtn) {
            copyRequest(copyReqBtn.dataset.id);
            return;
        }

        const delBtn = e.target.closest('.btn-req-delete');
        if (delBtn) {
            deleteId = delBtn.dataset.id;
            document.getElementById('deleteTitle').textContent = delBtn.dataset.title;
            deleteModal.show();
            return;
        }
    });

    async function copyRequest(id) {
        const res = await fetch(`/admin/requests/${id}/copy`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message, 'success');
            const tbody = document.getElementById('requestsTableBody');
            const tr = document.createElement('tr');
            tr.dataset.id = json.id;
            tr.innerHTML = `
                <td class="text-muted">${json.id}</td>
                <td><a href="/admin/requests/${json.id}/edit">${json.title}</a></td>
                <td style="font-size:.82rem;color:var(--ztr-text-muted)">${json.rubric}</td>
                <td>
                    <div class="d-flex align-items-center gap-1">
                        <code class="text-warning" style="font-size:.8rem">${json.tag}</code>
                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-copy-tag"
                            data-tag="${json.tag}" title="Скопировать тег">
                            <i class="bi bi-copy" style="font-size:.8rem"></i>
                        </button>
                    </div>
                </td>
                <td style="white-space:nowrap">
                    <a href="/admin/requests/${json.id}/edit" class="btn btn-sm btn-outline-success"><i class="bi bi-pencil"></i></a>
                    <button type="button" class="btn btn-sm btn-outline-success btn-req-copy" data-id="${json.id}"><i class="bi bi-copy"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-req-delete" data-id="${json.id}" data-title="${json.title}"><i class="bi bi-trash"></i></button>
                </td>`;
            tbody?.appendChild(tr);
        } else {
            showToast(json.message, 'error');
        }
    }

    let deleteId = null;
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    document.getElementById('btnDeleteConfirm').addEventListener('click', async () => {
        const res = await fetch(`/admin/requests/${deleteId}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message, 'success');
            deleteModal.hide();
            document.querySelector(`tr[data-id="${deleteId}"]`)?.remove();
        } else {
            showToast(json.message, 'error');
        }
    });

    if (cfg.hasErrors) {
        new bootstrap.Tab(document.querySelector('[href="#tabCreate"]')).show();
        showToast(cfg.errorsText, 'error');
    }
})();
