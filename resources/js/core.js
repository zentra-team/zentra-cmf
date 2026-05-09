function showToast(message, type = 'info') {
    const labels = { success: 'Успешно', error: 'Ошибка', warning: 'Предупреждение', info: 'Информация' };
    const container = document.getElementById('toastContainer');
    const id = 'toast_' + Date.now();
    container.insertAdjacentHTML(
        'beforeend',
        `
        <div id="${id}" class="toast toast-${type}" role="alert" aria-live="assertive" data-bs-autohide="true" data-bs-delay="2000">
            <div class="toast-header">
                <strong class="me-auto">${labels[type] ?? type}</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
            </div>
            <div class="toast-body">${message}</div>
        </div>`,
    );
    bootstrap.Toast.getOrCreateInstance(document.getElementById(id)).show();
}

const sidebar = document.getElementById('sidebar');
const collapseBtn = document.getElementById('sidebarCollapseBtn');
const collapseIcon = document.getElementById('sidebarCollapseIcon');
const toggleBtn = document.getElementById('sidebarToggleBtn');

function setSidebarState(collapsed) {
    sidebar.classList.toggle('collapsed', collapsed);
    collapseIcon.className = collapsed ? 'bi bi-arrow-bar-right' : 'bi bi-arrow-bar-left';
    localStorage.setItem('ztr_sidebar_collapsed', collapsed ? '1' : '0');
}

if (localStorage.getItem('ztr_sidebar_collapsed') === '1') {
    setSidebarState(true);
}

collapseBtn.addEventListener('click', () => setSidebarState(!sidebar.classList.contains('collapsed')));
toggleBtn.addEventListener('click', () => setSidebarState(!sidebar.classList.contains('collapsed')));

const modalClearCache = new bootstrap.Modal(document.getElementById('modalClearCache'));
const btnClearCache = document.getElementById('btnClearCache');
const btnClearCacheConfirm = document.getElementById('btnClearCacheConfirm');

if (btnClearCache) {
    btnClearCache.addEventListener('click', () => modalClearCache.show());
}

if (btnClearCacheConfirm) {
    btnClearCacheConfirm.addEventListener('click', () => {
        const btn = btnClearCacheConfirm;
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Очистка...';

        const cacheRoute = btn.dataset.route;

        fetch(cacheRoute, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                Accept: 'application/json',
            },
        })
            .then((r) => r.json())
            .then((data) => {
                modalClearCache.hide();
                showToast(data.message ?? 'Кеш очищен', data.ok ? 'success' : 'error');
            })
            .catch(() => {
                modalClearCache.hide();
                showToast('Не удалось очистить кеш', 'error');
            })
            .finally(() => {
                btn.disabled = false;
                btn.textContent = 'Очистить';
            });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('[data-bs-toggle="tooltip"]').forEach((el) => bootstrap.Tooltip.getOrCreateInstance(el));
    document.querySelectorAll('[data-bs-toggle="popover"]').forEach((el) => bootstrap.Popover.getOrCreateInstance(el));
});
