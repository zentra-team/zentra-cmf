(function () {
    const form = document.getElementById('redirectForm');
    if (!form) {return;}

    const fromUrl = document.getElementById('fromUrl');
    const toUrl = document.getElementById('toUrl');
    const cycleAlert = document.getElementById('cycleAlert');
    const cycleAlertText = document.getElementById('cycleAlertText');
    const btnSave = document.getElementById('btnSave');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    let cycleCheckTimer;
    function checkPotentialCycle() {
        clearTimeout(cycleCheckTimer);
        cycleCheckTimer = setTimeout(async () => {
            const to = (toUrl.value || '').trim();
            if (!to || /^https?:\/\//.test(to)) {
                cycleAlert.classList.add('d-none');
                return;
            }
            try {
                const res = await fetch('/admin/redirects/inspect', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-CSRF-TOKEN': csrf,
                    },
                    body: JSON.stringify({ url: to }),
                });
                if (!res.ok) {return;}
                const data = await res.json();
                if (data.matched) {
                    cycleAlertText.textContent = `Целевой URL сам редиректит на ${data.to}. Получится цепочка из ${data.hops + 1} редиректов - браузер может их пройти, но лучше указать сразу финальный адрес.`;
                    cycleAlert.classList.remove('d-none');
                } else {
                    cycleAlert.classList.add('d-none');
                }
            } catch {}
        }, 500);
    }

    toUrl?.addEventListener('input', checkPotentialCycle);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        if (btnSave.disabled) {return;}
        btnSave.disabled = true;

        const formData = new FormData(form);
        const url = form.dataset.method === 'PUT' ? form.dataset.updateUrl : form.dataset.storeUrl;

        try {
            ensureBoolField(formData, 'is_active');
            ensureBoolField(formData, 'preserve_query_string');

            const params = new URLSearchParams(formData);
            if (form.dataset.method === 'PUT') {params.append('_method', 'PUT');}

            const res = await fetch(url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: params,
            });

            const text = await res.text();
            let data;
            try {
                data = JSON.parse(text);
            } catch {
                data = { message: 'Неверный ответ сервера' };
            }

            if (!res.ok) {
                if (data.errors) {
                    const firstError = Object.values(data.errors)[0]?.[0];
                    showToast(firstError || data.message || 'Ошибка валидации', 'error');
                } else {
                    showToast(data.message || 'Ошибка сохранения', 'error');
                }
                return;
            }

            showToast(data.message || 'Сохранено', 'success');
            if (data.redirect) {
                setTimeout(() => {
                    location.href = data.redirect;
                }, 400);
            }
        } catch (e) {
            showToast('Ошибка соединения', 'error');
        } finally {
            btnSave.disabled = false;
        }
    });

    function ensureBoolField(fd, name) {
        const cb = form.querySelector(`input[name="${name}"]`);
        if (!cb) {return;}
        if (!cb.checked) {fd.set(name, '0');}
    }

    const btnDelete = document.getElementById('btnDelete');
    const deleteModalEl = document.getElementById('deleteRedirectModal');
    const deleteModal = deleteModalEl ? new bootstrap.Modal(deleteModalEl) : null;

    btnDelete?.addEventListener('click', () => deleteModal?.show());

    document.getElementById('btnDeleteConfirm')?.addEventListener('click', async () => {
        const btn = document.getElementById('btnDeleteConfirm');
        btn.disabled = true;
        try {
            const res = await fetch(btnDelete.dataset.url, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            });
            const data = await res.json();
            if (!res.ok) {
                showToast(data.message || 'Ошибка', 'error');
                return;
            }
            showToast(data.message || 'Удалено', 'success');
            setTimeout(() => {
                location.href = form.dataset.indexUrl;
            }, 400);
        } catch (e) {
            showToast('Ошибка соединения', 'error');
        } finally {
            btn.disabled = false;
        }
    });
})();
