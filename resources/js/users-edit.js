(function () {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    const primaryGroupSelect = document.querySelector('[name="group_id"]');
    if (primaryGroupSelect && !primaryGroupSelect.disabled) {
        function syncAdditionalGroups() {
            const selectedId = primaryGroupSelect.value;
            document.querySelectorAll('.additional-group-check').forEach((cb) => {
                const wrap = cb.closest('.form-check');
                if (cb.value === selectedId) {
                    cb.checked = false;
                    wrap.style.display = 'none';
                } else {
                    wrap.style.display = '';
                }
            });
        }
        primaryGroupSelect.addEventListener('change', syncAdditionalGroups);
        syncAdditionalGroups();
    }

    if (!cfg.canEdit) {return;}

    let passwordRequired = cfg.isNewUser;

    document.getElementById('btnShowPassword')?.addEventListener('click', function () {
        const field = document.getElementById('passwordField');
        const isPass = field.type === 'password';
        field.type = isPass ? 'text' : 'password';
        this.innerHTML = isPass ? '<i class="bi bi-eye-slash"></i>' : '<i class="bi bi-eye"></i>';
    });

    if (!cfg.isNewUser) {
        const confirmWrap = document.getElementById('passwordConfirmWrap');
        if (confirmWrap) {
            confirmWrap.style.display = 'none';
            document.getElementById('passwordField')?.addEventListener('input', function () {
                const show = this.value.length > 0;
                confirmWrap.style.display = show ? '' : 'none';
                document.getElementById('passwordConfirmField').required = show;
            });
        }
    }

    document.getElementById('btnSendPassword')?.addEventListener('click', async function () {
        this.disabled = true;
        this.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Отправка...';

        const res = await fetch(cfg.sendPwdUrl, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const json = await res.json();

        this.disabled = false;
        this.innerHTML = '<i class="bi bi-envelope me-1"></i>Выслать пароль';

        if (json.ok) {passwordRequired = false;}
        showToast(json.message, json.ok ? 'success' : 'error');
    });

    async function saveUser() {
        const btn = document.getElementById('btnSave');
        const status = document.getElementById('saveStatus');
        if (!btn) {return;}

        const form = document.getElementById('userForm');
        const fd = new FormData(form);
        const password = fd.get('password');
        const confirm = fd.get('password_confirmation');

        if (passwordRequired && !password) {
            showToast('Укажите пароль или воспользуйтесь кнопкой «Выслать пароль»', 'error');
            return;
        }
        if (password && password.length < 8) {
            showToast('Минимальная длина пароля - 8 символов', 'error');
            return;
        }
        if (password && password !== confirm) {
            showToast('Пароли не совпадают', 'error');
            return;
        }

        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
        if (status) {status.textContent = '';}

        const additionalGroups = Array.from(form.querySelectorAll('.additional-group-check:checked')).map((cb) =>
            parseInt(cb.value),
        );

        const data = {
            is_new_user: passwordRequired ? 1 : 0,
            first_name: fd.get('first_name') || null,
            last_name: fd.get('last_name') || null,
            email: fd.get('email'),
            group_id: fd.get('group_id') || null,
            additional_groups: additionalGroups,
            is_active: form.querySelector('[name="is_active"]').checked ? 1 : 0,
        };

        if (password) {
            data.password = password;
            data.password_confirmation = confirm;
        }

        try {
            const res = await fetch(cfg.saveUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify(data),
            });
            const json = await res.json();

            if (json.ok) {
                showToast(json.message ?? 'Сохранено', 'success');
                if (status) {status.textContent = 'Сохранено ' + new Date().toLocaleTimeString('ru');}
                const pwdField = document.getElementById('passwordField');
                const pwdConfirm = document.getElementById('passwordConfirmField');
                if (pwdField) {pwdField.value = '';}
                if (pwdConfirm) {pwdConfirm.value = '';}
                if (!cfg.isNewUser) {
                    const wrap = document.getElementById('passwordConfirmWrap');
                    if (wrap) {wrap.style.display = 'none';}
                }
            } else {
                const msg = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Ошибка');
                showToast(msg, 'error');
            }
        } catch (e) {
            showToast('Ошибка соединения с сервером', 'error');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-floppy me-1"></i>Сохранить';
        }
    }

    document.getElementById('btnSave')?.addEventListener('click', saveUser);
    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            saveUser();
        }
    });
})();
