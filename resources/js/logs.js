(function () {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    document.querySelectorAll('.ztr-log-search-toggle').forEach((toggle) => {
        toggle.addEventListener('click', () => {
            const panelId = toggle.dataset.target;
            const panel = document.getElementById(panelId);
            const chevron = toggle.querySelector('.ztr-log-search-chevron');
            const isOpen = panel.classList.contains('show');
            bootstrap.Collapse.getOrCreateInstance(panel).toggle();
            chevron.style.transform = isOpen ? '' : 'rotate(180deg)';
        });
    });

    const tabParam = new URLSearchParams(location.search).get('tab');
    if (tabParam === '404') {new bootstrap.Tab(document.querySelector('[href="#tab404"]')).show();}
    else if (tabParam === 'db') {new bootstrap.Tab(document.querySelector('[href="#tabDb"]')).show();}
    else if (tabParam === 'framework') {new bootstrap.Tab(document.querySelector('[href="#tabFramework"]')).show();}

    document.querySelectorAll('#logTabs .nav-link').forEach((link) => {
        link.addEventListener('shown.bs.tab', () => {
            const params = new URLSearchParams(location.search);
            ['admin_page', 'log404_page', 'logdb_page', 'tab'].forEach((p) => params.delete(p));
            const href = link.getAttribute('href');
            if (href === '#tab404') {params.set('tab', '404');}
            else if (href === '#tabDb') {params.set('tab', 'db');}
            else if (href === '#tabFramework') {params.set('tab', 'framework');}
            history.replaceState(null, '', location.pathname + (params.toString() ? '?' + params.toString() : ''));
        });
    });

    let clearType = null;
    const clearModal = new bootstrap.Modal(document.getElementById('clearModal'));

    document.querySelectorAll('.btn-clear-log').forEach((btn) => {
        btn.addEventListener('click', () => {
            clearType = btn.dataset.type;
            clearModal.show();
        });
    });

    let fwClearFile = null;
    const clearFrameworkModal = new bootstrap.Modal(document.getElementById('clearFrameworkModal'));

    document.getElementById('btnClearFramework')?.addEventListener('click', function () {
        fwClearFile = this.dataset.file;
        document.getElementById('fwClearFilename').textContent = fwClearFile;
        clearFrameworkModal.show();
    });

    document.getElementById('btnClearFrameworkConfirm').addEventListener('click', async () => {
        const btn = document.getElementById('btnClearFrameworkConfirm');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Очистка...';

        const res = await fetch(`/admin/logs/framework/${encodeURIComponent(fwClearFile)}/clear`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message, 'success');
            clearFrameworkModal.hide();
            setTimeout(() => (location.href = cfg.logsIndexUrl + '?tab=framework'), 600);
        } else {
            showToast(json.message ?? 'Ошибка', 'error');
        }

        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-trash me-1"></i>Да, очистить файл';
    });

    function activeTabParam() {
        const active = document.querySelector('#logTabs .nav-link.active');
        if (!active) {return '';}
        const href = active.getAttribute('href');
        if (href === '#tab404') {return 'tab=404';}
        if (href === '#tabDb') {return 'tab=db';}
        if (href === '#tabFramework') {return 'tab=framework';}
        return '';
    }

    document.getElementById('btnClearConfirm').addEventListener('click', async () => {
        const res = await fetch(`/admin/logs/${clearType}/clear`, {
            method: 'POST',
            headers: { 'X-CSRF-TOKEN': csrf },
        });
        const json = await res.json();
        if (json.ok) {
            showToast(json.message, 'success');
            clearModal.hide();
            const param = activeTabParam();
            setTimeout(() => (location.href = cfg.logsIndexUrl + (param ? '?' + param : '')), 600);
        } else {
            showToast(json.message ?? 'Ошибка', 'error');
        }
    });
})();
