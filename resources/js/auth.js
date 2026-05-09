function showToast(message, type = 'info') {
    const labels = { success: 'Успешно', error: 'Ошибка', warning: 'Предупреждение', info: 'Информация' };
    const container = document.getElementById('toastContainer');
    const id = 'toast_' + Date.now();
    container.insertAdjacentHTML(
        'beforeend',
        `
        <div id="${id}" class="toast toast-${type}" role="alert" aria-live="assertive" data-bs-autohide="true" data-bs-delay="5000">
            <div class="toast-header">
                <strong class="me-auto">${labels[type] ?? type}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>
        </div>`,
    );
    bootstrap.Toast.getOrCreateInstance(document.getElementById(id)).show();
}

document.addEventListener('DOMContentLoaded', function () {
    const btn = document.getElementById('btnTogglePass');
    if (btn) {
        btn.addEventListener('click', function () {
            const input = document.getElementById('password');
            const icon = document.getElementById('iconTogglePass');
            const isHidden = input.type === 'password';
            input.type = isHidden ? 'text' : 'password';
            icon.className = isHidden ? 'bi bi-eye-slash' : 'bi bi-eye';
        });
    }
});
