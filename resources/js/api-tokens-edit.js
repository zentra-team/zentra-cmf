(function () {
    'use strict';

    const cfg = window.ZentraConfig || {};
    const form = document.getElementById('tokenForm');
    if (!form) {return;}

    const radioAll = document.getElementById('rubricsModeAll');
    const radioSelected = document.getElementById('rubricsModeSelected');
    const rubricsListEl = document.getElementById('rubricsList');

    function syncRubricsVisibility() {
        if (!rubricsListEl) {return;}
        rubricsListEl.classList.toggle('d-none', radioAll?.checked);
    }
    radioAll?.addEventListener('change', syncRubricsVisibility);
    radioSelected?.addEventListener('change', syncRubricsVisibility);

    document.getElementById('btnCopyPlainToken')?.addEventListener('click', async () => {
        const code = document.getElementById('plainTokenValue');
        if (!code) {return;}
        try {
            await navigator.clipboard.writeText(code.textContent.trim());
            window.showToast?.('Токен скопирован в буфер', 'success');
        } catch {
            const r = document.createRange();
            r.selectNode(code);
            window.getSelection()?.removeAllRanges();
            window.getSelection()?.addRange(r);
            document.execCommand('copy');
            window.showToast?.('Токен выделен - скопируйте Ctrl+C', 'info');
        }
    });

    document.getElementById('btnCopyApiDocsUrl')?.addEventListener('click', async () => {
        const code = document.getElementById('apiDocsUrl');
        if (!code) {return;}
        try {
            await navigator.clipboard.writeText(code.textContent.trim());
            window.showToast?.('Ссылка на документацию скопирована', 'success');
        } catch {
            window.showToast?.('Не удалось скопировать ссылку', 'danger');
        }
    });

    const btnRegenerate = document.getElementById('btnRegenerate');
    const regenerateModalEl = document.getElementById('regenerateTokenModal');
    const btnRegenerateConfirm = document.getElementById('btnRegenerateConfirm');

    btnRegenerate?.addEventListener('click', () => {
        if (!regenerateModalEl) {return;}
        bootstrap.Modal.getOrCreateInstance(regenerateModalEl).show();
    });

    btnRegenerateConfirm?.addEventListener('click', async () => {
        const url = btnRegenerate?.dataset.url;
        if (!url) {return;}

        btnRegenerateConfirm.disabled = true;
        btnRegenerate.disabled = true;

        const expiresInput = document.getElementById('regenerateExpiresAt');
        const fd = new FormData();
        if (expiresInput && expiresInput.value.trim() !== '') {
            fd.set('expires_at', expiresInput.value);
        }

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { 'X-CSRF-TOKEN': cfg.csrf, Accept: 'application/json' },
                body: fd,
            });
            const data = await res.json();
            if (!res.ok || !data.ok) {
                if (res.status === 422 && data.errors) {
                    const first = Object.values(data.errors)[0]?.[0] ?? data.message ?? 'Ошибка валидации';
                    throw new Error(first);
                }
                throw new Error(data?.message || 'Ошибка');
            }

            bootstrap.Modal.getInstance(regenerateModalEl)?.hide();
            window.showToast?.('Токен перегенерирован - копируйте новое значение', 'success');

            const placeholder = document.createElement('div');
            placeholder.className = 'alert alert-warning ztr-api-token-revealed mt-3';
            placeholder.innerHTML = `
                <h6 class="mb-2"><i class="bi bi-shield-exclamation me-2"></i>Новый токен - скопируйте сейчас</h6>
                <div class="ztr-api-token-plain-row">
                    <code>${escapeHtml(data.plain_token)}</code>
                    <button type="button" class="btn btn-sm btn-warning text-dark" id="btnCopyNewPlainToken">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>`;
            const old = document.querySelector('.ztr-api-token-revealed');
            if (old) {old.replaceWith(placeholder);}
            else {form.parentNode.insertBefore(placeholder, form);}

            placeholder.querySelector('#btnCopyNewPlainToken').addEventListener('click', async () => {
                await navigator.clipboard.writeText(data.plain_token);
                window.showToast?.('Скопировано', 'success');
            });

            document.querySelector('.ztr-api-token-mask code').textContent = data.prefix;
            const rotatedCell = document.getElementById('auditSecretRotatedAt');
            if (rotatedCell && data.secret_rotated_at) {
                rotatedCell.textContent = data.secret_rotated_at;
            }

            const expiresFormInput = form.querySelector('input[name="expires_at"]');
            if (expiresFormInput && data.expires_at) {
                const m = data.expires_at.match(/^(\d{2})\.(\d{2})\.(\d{4}) (\d{2}):(\d{2})$/);
                if (m) {
                    expiresFormInput.value = `${m[3]}-${m[2]}-${m[1]}T${m[4]}:${m[5]}`;
                }
            }
        } catch (e) {
            window.showToast?.(e.message, 'danger');
        } finally {
            btnRegenerateConfirm.disabled = false;
            btnRegenerate.disabled = false;
        }
    });

    function escapeHtml(s) {
        return String(s).replace(
            /[&<>"']/g,
            (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c],
        );
    }

    // ----- Сохранение формы -----
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        const isNew = !!cfg.isNew;
        const url = isNew ? submitBtn.dataset.storeUrl : submitBtn.dataset.updateUrl;

        // Если режим «все рубрики» - не отправляем allowed_rubrics
        const fd = new FormData(form);
        if (radioAll?.checked) {
            fd.delete('allowed_rubrics[]');
        }
        // Принудительно булевые
        fd.set('is_active', form.querySelector('#is_active').checked ? '1' : '0');
        if (!isNew) {
            fd.set('_method', 'PUT');
        }

        submitBtn.disabled = true;

        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': cfg.csrf,
                    Accept: 'application/json',
                },
                body: fd,
            });
            const data = await res.json();
            if (!res.ok || !data.ok) {
                if (res.status === 422 && data.errors) {
                    const first = Object.values(data.errors)[0]?.[0] ?? data.message ?? 'Ошибка валидации';
                    throw new Error(first);
                }
                throw new Error(data?.message || 'Ошибка');
            }

            window.showToast?.(data.message || 'Готово', 'success');

            if (isNew && data.redirect && data.plain_token) {
                // Кладём plain в sessionStorage чтобы edit-страница его подняла из URL-параметра
                sessionStorage.setItem('zentra_api_token_just_created_' + data.token_id, data.plain_token);
                location.href = data.redirect + '?just_created=1';
            }
        } catch (e) {
            window.showToast?.(e.message, 'danger');
            submitBtn.disabled = false;
        }
    });

    // На странице после редиректа после создания - поднимаем plain из sessionStorage,
    // вставляем баннер «скопируйте сейчас», очищаем storage.
    if (location.search.includes('just_created=1')) {
        const id = form.dataset.id;
        const key = 'zentra_api_token_just_created_' + id;
        const plain = sessionStorage.getItem(key);
        if (plain) {
            sessionStorage.removeItem(key);
            const placeholder = document.createElement('div');
            placeholder.className = 'alert alert-warning ztr-api-token-revealed';
            placeholder.innerHTML = `
                <h6 class="mb-2"><i class="bi bi-shield-exclamation me-2"></i>Скопируйте токен - он показывается ОДИН раз</h6>
                <div class="ztr-api-token-plain-row">
                    <code>${escapeHtml(plain)}</code>
                    <button type="button" class="btn btn-sm btn-warning text-dark" id="btnCopyJustCreatedToken">
                        <i class="bi bi-clipboard"></i>
                    </button>
                </div>
                <div class="small text-muted mt-2">
                    После закрытия страницы получить токен будет невозможно - только перегенерировать новый.
                </div>`;
            form.parentNode.insertBefore(placeholder, form);
            placeholder.querySelector('#btnCopyJustCreatedToken').addEventListener('click', async () => {
                await navigator.clipboard.writeText(plain);
                window.showToast?.('Скопировано', 'success');
            });

            // уберём ?just_created=1 из URL
            history.replaceState({}, '', location.pathname);
        }
    }
})();
