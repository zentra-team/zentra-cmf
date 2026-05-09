(function () {
    'use strict';

    const { csrf } = window.ZentraConfig;

    const navTitle = document.getElementById('navTitle');
    const navAlias = document.getElementById('navAlias');
    const navTagPreview = document.getElementById('navTagPreview');

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
    if (navAlias) {
        navAlias.addEventListener('input', () => {
            aliasManual = true;
            navTagPreview.textContent = '[nav:' + navAlias.value + ']';
        });
        navTitle.addEventListener('input', () => {
            if (aliasManual) {return;}
            const a = toAlias(navTitle.value);
            navAlias.value = a;
            navTagPreview.textContent = '[nav:' + a + ']';
        });
    }

    const createForm = document.querySelector('#tabCreate form');
    if (createForm) {
        createForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = createForm.querySelector('[type="submit"]');
            btn.disabled = true;

            const title = navTitle.value.trim();
            const alias = navAlias.value.trim();

            const res = await fetch(createForm.action, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ title, alias }),
            });
            const json = await res.json();

            btn.disabled = false;

            if (json.ok) {
                showToast(json.message, 'success');
                setTimeout(() => {
                    window.location.href = json.redirect;
                }, 600);
            } else {
                const msg = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Ошибка');
                showToast(msg, 'error');
            }
        });
    }

    document.querySelectorAll('.btn-copy-tag').forEach((btn) => {
        btn.addEventListener('click', () => {
            navigator.clipboard.writeText(btn.dataset.tag).then(() => showToast('Тег скопирован', 'success'));
        });
    });

    let copyNavId = null;
    const copyNavModal = new bootstrap.Modal(document.getElementById('copyNavModal'));
    const copyTitleInput = document.getElementById('copyNavTitle');
    const copyAliasInput = document.getElementById('copyNavAlias');
    const btnCopyConfirm = document.getElementById('btnCopyNavConfirm');

    const updateCopyConfirmState = () => {
        btnCopyConfirm.disabled = !copyTitleInput.value.trim() || !copyAliasInput.value.trim();
    };
    copyTitleInput.addEventListener('input', updateCopyConfirmState);
    copyAliasInput.addEventListener('input', updateCopyConfirmState);

    document.querySelectorAll('.btn-nav-copy').forEach((btn) => {
        btn.addEventListener('click', () => {
            copyNavId = btn.dataset.navId;
            copyTitleInput.value = btn.dataset.navTitle + ' (копия)';
            copyAliasInput.value = '';
            updateCopyConfirmState();
            copyNavModal.show();
        });
    });

    btnCopyConfirm.addEventListener('click', async () => {
        const title = copyTitleInput.value.trim();
        const alias = copyAliasInput.value.trim();
        if (!title || !alias) {return;}

        btnCopyConfirm.disabled = true;

        const res = await fetch('/admin/navigations/' + copyNavId + '/copy', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ title, alias }),
        });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message, 'success');
            copyNavModal.hide();
            setTimeout(() => location.reload(), 800);
        } else {
            const msg = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Ошибка');
            showToast(msg, 'error');
            updateCopyConfirmState();
        }
    });

    let deleteNavId = null;
    const deleteNavModal = new bootstrap.Modal(document.getElementById('deleteNavModal'));

    document.querySelectorAll('.btn-nav-delete').forEach((btn) => {
        btn.addEventListener('click', () => {
            deleteNavId = btn.dataset.navId;
            document.getElementById('deleteNavName').textContent = btn.dataset.navTitle;
            deleteNavModal.show();
        });
    });

    document.getElementById('btnDeleteNavConfirm').addEventListener('click', async () => {
        const res = await fetch('/admin/navigations/' + deleteNavId, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message, 'success');
            deleteNavModal.hide();
            setTimeout(() => location.reload(), 800);
        } else {
            showToast(json.message ?? 'Ошибка', 'error');
        }
    });
})();
