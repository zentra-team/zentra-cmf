(function () {
    const cfg = window.ZentraConfig || {};

    const modal = document.getElementById('modalPlatformUpdate');
    const checkingBody = document.getElementById('updateCheckingBody');
    const upToDateBody = document.getElementById('updateUpToDateBody');
    const modalBody = document.getElementById('updateModalBody');
    const manualBody = document.getElementById('updateManualBody');
    const btnUpdate = document.getElementById('btnPerformUpdate');
    const btnAvailable = document.getElementById('btnUpdateAvailable');
    const labelBadge = document.getElementById('updateVersionLabel');
    const labelCurrent = document.getElementById('updateCurrentVersion');
    const labelNew = document.getElementById('updateNewVersion');
    const labelUpToDate = document.getElementById('updateCurrentVersionUpToDate');
    const changelog = document.getElementById('updateChangelog');
    const manualSteps = document.getElementById('updateManualSteps');
    const downloadLink = document.getElementById('updateDownloadLink');

    if (!modal) {return;}

    let updateData = null;
    let checkDone = false;

    function showState(state) {
        [checkingBody, upToDateBody, modalBody, manualBody].forEach(function (el) {
            if (el) {el.classList.add('d-none');}
        });
        if (state === 'checking' && checkingBody) {checkingBody.classList.remove('d-none');}
        if (state === 'up-to-date' && upToDateBody) {upToDateBody.classList.remove('d-none');}
        if (state === 'update' && modalBody) {modalBody.classList.remove('d-none');}
        if (state === 'manual' && manualBody) {manualBody.classList.remove('d-none');}

        if (btnUpdate) {
            btnUpdate.classList.toggle('d-none', state !== 'update');
        }
    }

    function applyUpdateData(data) {
        checkDone = true;

        if (data && data.update) {
            updateData = data.update;
            if (labelBadge) {labelBadge.textContent = '↑ v' + updateData.version;}
            if (btnAvailable) {btnAvailable.classList.remove('d-none');}
        } else {
            updateData = false;
        }

        if (modal.classList.contains('show')) {fillModal();}
    }

    function fillModal() {
        const ver = 'v' + (cfg.currentVersion || '—');

        if (!checkDone) {
            showState('checking');
            return;
        }

        if (!updateData) {
            showState('up-to-date');
            if (labelUpToDate) {labelUpToDate.textContent = ver;}
            return;
        }

        showState('update');
        if (labelCurrent) {labelCurrent.textContent = ver;}
        if (labelNew) {labelNew.textContent = 'v' + updateData.version;}
        if (changelog) {changelog.textContent = updateData.changelog || 'Список изменений недоступен.';}
    }

    const cached = sessionStorage.getItem('ztr_platform_update');
    if (cached) {
        try {
            applyUpdateData(JSON.parse(cached));
        } catch (e) {}
    }

    if (!checkDone && cfg.platformCheckUrl) {
        fetch(cfg.platformCheckUrl)
            .then(function (r) {
                return r.ok ? r.json() : null;
            })
            .then(function (data) {
                if (data) {sessionStorage.setItem('ztr_platform_update', JSON.stringify(data));}
                applyUpdateData(data || {});
            })
            .catch(function () {
                applyUpdateData({});
            });
    }

    modal.addEventListener('show.bs.modal', function () {
        fillModal();
    });

    if (btnUpdate) {
        btnUpdate.addEventListener('click', function () {
            if (!cfg.platformUpdateUrl) {return;}

            btnUpdate.disabled = true;
            btnUpdate.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Обновление…';

            fetch(cfg.platformUpdateUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': cfg.csrfToken || '',
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                },
            })
                .then(function (r) {
                    return r.text();
                })
                .then(function (text) {
                    let res;
                    try {
                        res = JSON.parse(text);
                    } catch (e) {
                        res = { ok: false, message: text };
                    }

                    btnUpdate.disabled = false;
                    btnUpdate.innerHTML = '<i class="bi bi-arrow-up-circle me-1"></i>Обновить';

                    if (res.manual) {
                        showManualInstructions(res);
                        return;
                    }

                    if (res.ok) {
                        sessionStorage.removeItem('ztr_platform_update');
                        bootstrap.Modal.getInstance(modal)?.hide();
                        showToast(res.message || 'Обновление выполнено', 'success');
                        setTimeout(function () {
                            location.reload();
                        }, 2000);
                    } else {
                        showToast(res.message || 'Ошибка обновления', 'error');
                    }
                })
                .catch(function (e) {
                    btnUpdate.disabled = false;
                    btnUpdate.innerHTML = '<i class="bi bi-arrow-up-circle me-1"></i>Обновить';
                    showToast('Ошибка запроса: ' + e.message, 'error');
                });
        });
    }

    function showManualInstructions(res) {
        showState('manual');

        if (manualSteps) {
            manualSteps.innerHTML = '';
            (res.steps || []).forEach(function (step) {
                const li = document.createElement('li');
                li.textContent = step;
                li.style.marginBottom = '.25rem';
                manualSteps.appendChild(li);
            });
        }

        if (downloadLink && res.download_url) {
            downloadLink.href = res.download_url;
        }
    }
})();
