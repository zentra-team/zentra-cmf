function showToast(message, type = 'info') {
    const types = { success: 'Успешно', error: 'Ошибка', warning: 'Предупреждение', info: 'Информация' };
    const container = document.getElementById('toastContainer');
    const id = 'toast_' + Date.now();
    container.insertAdjacentHTML(
        'beforeend',
        `
        <div id="${id}" class="toast toast-${type}" role="alert" aria-live="assertive" data-bs-autohide="true" data-bs-delay="4000">
            <div class="toast-header">
                <strong class="me-auto">${types[type] ?? type}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>
        </div>`,
    );
    bootstrap.Toast.getOrCreateInstance(document.getElementById(id)).show();
}

document.addEventListener('DOMContentLoaded', function () {
    const acceptCb = document.getElementById('acceptLicense');
    const btnCont = document.getElementById('btnContinue');
    if (acceptCb && btnCont) {
        acceptCb.addEventListener('change', function () {
            btnCont.disabled = !this.checked;
        });
    }

    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach(function (el) {
        new bootstrap.Tooltip(el, { trigger: 'hover focus' });
    });

    const pwd = document.getElementById('password');
    const conf = document.getElementById('passwordConfirm');
    const err = document.getElementById('confirmError');
    if (pwd && conf && err) {
        conf.addEventListener('input', function () {
            const match = conf.value === '' || pwd.value === conf.value;
            conf.classList.toggle('is-invalid', !match);
            err.style.display = match ? 'none' : 'block';
        });
    }
});
