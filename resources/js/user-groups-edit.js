(function () {
    const cfg = window.ZentraConfig || {};
    const csrf = document.querySelector('meta[name="csrf-token"]').content;

    if (cfg.isSystem) {
        wireCollapseControls();
        return;
    }

    const form = document.getElementById('groupForm');
    const nameInput = form.querySelector('input[name="name"]');
    const descInput = form.querySelector('input[name="description"]');
    const defaultToggle = form.querySelector('input[name="is_default"]');
    const btnSaveInfo = document.getElementById('btnSave');
    const btnSavePerms = document.getElementById('btnSavePerms');
    const statusInfo = document.getElementById('saveStatus');
    const statusPerms = document.getElementById('savePermsStatus');

    let baselineName = nameInput.value;
    let baselineDesc = descInput?.value ?? '';
    let baselineDefault = !!defaultToggle?.checked;
    let baselinePerms = collectPermissions();

    function collectPermissions() {
        return Array.from(form.querySelectorAll('.perm-checkbox:checked'))
            .map((cb) => cb.value)
            .sort();
    }

    function arraysEqual(a, b) {
        if (a.length !== b.length) {return false;}
        for (let i = 0; i < a.length; i++) {if (a[i] !== b[i]) {return false;}}
        return true;
    }

    function isInfoDirty() {
        if (nameInput.value !== baselineName) {return true;}
        if ((descInput?.value ?? '') !== baselineDesc) {return true;}
        if (!!defaultToggle?.checked !== baselineDefault) {return true;}
        return false;
    }
    function isPermsDirty() {
        return !arraysEqual(collectPermissions(), baselinePerms);
    }

    function refreshInfoButton() {
        btnSaveInfo.disabled = !isInfoDirty();
    }
    function refreshPermsButton() {
        btnSavePerms.disabled = !isPermsDirty();
        refreshBadges();
        refreshAllOverride();
    }

    function refreshBadges() {
        document.querySelectorAll('.perm-section').forEach((section) => {
            const header = section.querySelector('.perm-section-header');
            const allCbs = section.querySelectorAll('.perm-checkbox');
            const checked = section.querySelectorAll('.perm-checkbox:checked').length;
            const total = allCbs.length;
            if (!header || total === 0) {return;}

            let badge = header.querySelector('.ztr-ge-perm-badge-active');
            if (checked > 0) {
                if (!badge) {
                    badge = document.createElement('span');
                    badge.className = 'badge bg-purple ms-1 ztr-ge-perm-badge-active';
                    header.appendChild(badge);
                }
                badge.textContent = `${checked}/${total}`;
            } else {
                badge?.remove();
            }
        });
    }

    nameInput.addEventListener('input', refreshInfoButton);
    descInput?.addEventListener('input', refreshInfoButton);
    defaultToggle?.addEventListener('change', refreshInfoButton);

    document.querySelectorAll('.perm-checkbox').forEach((cb) => {
        cb.addEventListener('change', refreshPermsButton);
    });

    const allCb = document.querySelector('.perm-checkbox[value="all"]');

    function refreshAllOverride() {
        if (!allCb) {return;}
        const masterOn = allCb.checked;
        document.querySelectorAll('.perm-checkbox').forEach((cb) => {
            if (cb === allCb) {return;}
            const wrapper = cb.closest('.perm-check');
            cb.disabled = masterOn;
            if (masterOn) {
                wrapper?.classList.add('perm-covered-by-all');
                wrapper?.setAttribute('title', 'Включено через «Разрешить все права»');
            } else {
                wrapper?.classList.remove('perm-covered-by-all');
                wrapper?.removeAttribute('title');
            }
        });
        document.querySelectorAll('.perm-section').forEach((section) => {
            section.classList.toggle('perm-section-all-covered', masterOn);
        });
    }

    allCb?.addEventListener('change', function () {
        document.querySelectorAll('.perm-checkbox').forEach((cb) => {
            if (cb === allCb) {return;}
            cb.checked = allCb.checked;
        });
        refreshPermsButton();
    });

    refreshAllOverride();

    (function setupDatabaseCascade() {
        const accessCb = form.querySelector('.perm-checkbox[value="db.access"]');
        if (!accessCb) {return;}

        const subValues = ['db.backup', 'db.restore', 'db.optimize'];
        const subCbs = subValues.map((v) => form.querySelector(`.perm-checkbox[value="${v}"]`)).filter(Boolean);

        if (subCbs.length === 0) {return;}

        const backupCb = subCbs.find((cb) => cb.value === 'db.backup');

        accessCb.addEventListener('change', () => {
            if (accessCb.checked) {
                if (!subCbs.some((cb) => cb.checked) && backupCb) {
                    backupCb.checked = true;
                }
            } else {
                subCbs.forEach((cb) => {
                    cb.checked = false;
                });
            }
            refreshPermsButton();
        });

        subCbs.forEach((cb) => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    accessCb.checked = true;
                } else if (!subCbs.some((c) => c.checked)) {
                    accessCb.checked = false;
                }
                refreshPermsButton();
            });
        });
    })();

    (function setupLogsCascade() {
        const accessCb = form.querySelector('.perm-checkbox[value="logs.access"]');
        const tabCbs = Array.from(
            form.querySelectorAll(
                '.perm-checkbox[value="logs.tab.admin"], ' +
                    '.perm-checkbox[value="logs.tab.404"], ' +
                    '.perm-checkbox[value="logs.tab.db"], ' +
                    '.perm-checkbox[value="logs.tab.framework"]',
            ),
        );
        if (!accessCb || tabCbs.length === 0) {return;}

        const adminTabCb = tabCbs.find((cb) => cb.value === 'logs.tab.admin');

        accessCb.addEventListener('change', () => {
            if (accessCb.checked) {
                if (!tabCbs.some((cb) => cb.checked) && adminTabCb) {
                    adminTabCb.checked = true;
                }
            } else {
                tabCbs.forEach((cb) => {
                    cb.checked = false;
                });
            }
            refreshPermsButton();
        });

        tabCbs.forEach((cb) => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    accessCb.checked = true;
                } else if (!tabCbs.some((c) => c.checked)) {
                    accessCb.checked = false;
                }
                refreshPermsButton();
            });
        });
    })();

    (function setupSettingsCascade() {
        const accessCb = form.querySelector('.perm-checkbox[value="settings.access"]');
        if (!accessCb) {return;}

        const tabValues = ['settings.tab.general', 'settings.tab.env', 'settings.tab.seo', 'settings.tab.cache'];
        const editValues = ['settings.edit.general', 'settings.edit.env', 'settings.edit.seo'];

        const tabCbs = tabValues.map((v) => form.querySelector(`.perm-checkbox[value="${v}"]`)).filter(Boolean);
        const editCbs = editValues.map((v) => form.querySelector(`.perm-checkbox[value="${v}"]`)).filter(Boolean);

        const tabByName = Object.fromEntries(tabCbs.map((cb) => [cb.value.split('.').pop(), cb]));
        const editByName = Object.fromEntries(editCbs.map((cb) => [cb.value.split('.').pop(), cb]));

        accessCb.addEventListener('change', () => {
            if (accessCb.checked) {
                if (!tabCbs.some((cb) => cb.checked) && tabByName.general) {
                    tabByName.general.checked = true;
                }
            } else {
                tabCbs.forEach((cb) => {
                    cb.checked = false;
                });
                editCbs.forEach((cb) => {
                    cb.checked = false;
                });
            }
            refreshPermsButton();
        });

        tabCbs.forEach((cb) => {
            const name = cb.value.split('.').pop();
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    accessCb.checked = true;
                } else {
                    if (editByName[name]) {editByName[name].checked = false;}

                    if (!tabCbs.some((c) => c.checked)) {accessCb.checked = false;}
                }
                refreshPermsButton();
            });
        });

        editCbs.forEach((cb) => {
            const name = cb.value.split('.').pop();
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    if (tabByName[name]) {tabByName[name].checked = true;}
                    accessCb.checked = true;
                }
                refreshPermsButton();
            });
        });
    })();

    (function setupLayoutsCascade() {
        const accessCb = form.querySelector('.perm-checkbox[value="layouts.access"]');
        if (!accessCb) {return;}

        const listCb = form.querySelector('.perm-checkbox[value="layouts.list"]');
        if (!listCb) {return;}

        const actionValues = ['layouts.edit', 'layouts.create', 'layouts.delete', 'layouts.files'];
        const actionCbs = actionValues.map((v) => form.querySelector(`.perm-checkbox[value="${v}"]`)).filter(Boolean);

        const allSubCbs = [listCb, ...actionCbs];

        accessCb.addEventListener('change', () => {
            if (accessCb.checked) {
                if (!allSubCbs.some((cb) => cb.checked)) {listCb.checked = true;}
            } else {
                allSubCbs.forEach((cb) => {
                    cb.checked = false;
                });
            }
            refreshPermsButton();
        });

        listCb.addEventListener('change', () => {
            if (listCb.checked) {
                accessCb.checked = true;
            } else {
                actionCbs.forEach((cb) => {
                    cb.checked = false;
                });
                if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
            }
            refreshPermsButton();
        });

        actionCbs.forEach((cb) => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    listCb.checked = true;
                    accessCb.checked = true;
                } else {
                    if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
                }
                refreshPermsButton();
            });
        });
    })();

    (function setupNavigationsCascade() {
        const accessCb = form.querySelector('.perm-checkbox[value="navigations.access"]');
        if (!accessCb) {return;}

        const listCb = form.querySelector('.perm-checkbox[value="navigations.list"]');
        if (!listCb) {return;}

        const actionValues = ['navigations.create', 'navigations.edit', 'navigations.delete'];
        const actionCbs = actionValues.map((v) => form.querySelector(`.perm-checkbox[value="${v}"]`)).filter(Boolean);

        const allSubCbs = [listCb, ...actionCbs];

        accessCb.addEventListener('change', () => {
            if (accessCb.checked) {
                if (!allSubCbs.some((cb) => cb.checked)) {listCb.checked = true;}
            } else {
                allSubCbs.forEach((cb) => {
                    cb.checked = false;
                });
            }
            refreshPermsButton();
        });

        listCb.addEventListener('change', () => {
            if (listCb.checked) {
                accessCb.checked = true;
            } else {
                actionCbs.forEach((cb) => {
                    cb.checked = false;
                });
                if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
            }
            refreshPermsButton();
        });

        actionCbs.forEach((cb) => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    listCb.checked = true;
                    accessCb.checked = true;
                } else {
                    if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
                }
                refreshPermsButton();
            });
        });
    })();

    (function setupDocumentsCascade() {
        const accessCb = form.querySelector('.perm-checkbox[value="documents.access"]');
        if (!accessCb) {return;}

        const listCb = form.querySelector('.perm-checkbox[value="documents.list"]');
        if (!listCb) {return;}

        const actionValues = ['documents.create', 'documents.edit', 'documents.delete'];
        const actionCbs = actionValues.map((v) => form.querySelector(`.perm-checkbox[value="${v}"]`)).filter(Boolean);

        const allSubCbs = [listCb, ...actionCbs];

        accessCb.addEventListener('change', () => {
            if (accessCb.checked) {
                if (!allSubCbs.some((cb) => cb.checked)) {listCb.checked = true;}
            } else {
                allSubCbs.forEach((cb) => {
                    cb.checked = false;
                });
            }
            refreshPermsButton();
        });

        listCb.addEventListener('change', () => {
            if (listCb.checked) {
                accessCb.checked = true;
            } else {
                actionCbs.forEach((cb) => {
                    cb.checked = false;
                });
                if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
            }
            refreshPermsButton();
        });

        actionCbs.forEach((cb) => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    listCb.checked = true;
                    accessCb.checked = true;
                } else {
                    if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
                }
                refreshPermsButton();
            });
        });
    })();

    (function setupUsersCascade() {
        const accessCb = form.querySelector('.perm-checkbox[value="users.access"]');
        if (!accessCb) {return;}

        const listCb = form.querySelector('.perm-checkbox[value="users.list"]');
        if (!listCb) {return;}

        const actionValues = ['users.create', 'users.edit', 'users.delete', 'users.groups'];
        const actionCbs = actionValues.map((v) => form.querySelector(`.perm-checkbox[value="${v}"]`)).filter(Boolean);

        const allSubCbs = [listCb, ...actionCbs];

        accessCb.addEventListener('change', () => {
            if (accessCb.checked) {
                if (!allSubCbs.some((cb) => cb.checked)) {
                    listCb.checked = true;
                }
            } else {
                allSubCbs.forEach((cb) => {
                    cb.checked = false;
                });
            }
            refreshPermsButton();
        });

        listCb.addEventListener('change', () => {
            if (listCb.checked) {
                accessCb.checked = true;
            } else {
                actionCbs.forEach((cb) => {
                    cb.checked = false;
                });

                if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
            }
            refreshPermsButton();
        });

        actionCbs.forEach((cb) => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    listCb.checked = true;
                    accessCb.checked = true;
                } else {
                    if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
                }
                refreshPermsButton();
            });
        });
    })();

    (function setupBlocksCascade() {
        const accessCb = form.querySelector('.perm-checkbox[value="blocks.access"]');
        if (!accessCb) {return;}

        const listCb = form.querySelector('.perm-checkbox[value="blocks.list"]');
        if (!listCb) {return;}

        const blockActionValues = ['blocks.create', 'blocks.edit', 'blocks.delete'];
        const blockActionCbs = blockActionValues
            .map((v) => form.querySelector(`.perm-checkbox[value="${v}"]`))
            .filter(Boolean);

        const groupsCb = form.querySelector('.perm-checkbox[value="blocks.groups"]');

        const allSubCbs = [listCb, ...blockActionCbs, ...(groupsCb ? [groupsCb] : [])];

        accessCb.addEventListener('change', () => {
            if (accessCb.checked) {
                if (!allSubCbs.some((cb) => cb.checked)) {listCb.checked = true;}
            } else {
                allSubCbs.forEach((cb) => {
                    cb.checked = false;
                });
            }
            refreshPermsButton();
        });

        listCb.addEventListener('change', () => {
            if (listCb.checked) {
                accessCb.checked = true;
            } else {
                blockActionCbs.forEach((cb) => {
                    cb.checked = false;
                });
                if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
            }
            refreshPermsButton();
        });

        blockActionCbs.forEach((cb) => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    listCb.checked = true;
                    accessCb.checked = true;
                } else {
                    if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
                }
                refreshPermsButton();
            });
        });

        if (groupsCb) {
            groupsCb.addEventListener('change', () => {
                if (groupsCb.checked) {
                    accessCb.checked = true;
                } else {
                    if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
                }
                refreshPermsButton();
            });
        }
    })();

    (function setupRubricsCascade() {
        const accessCb = form.querySelector('.perm-checkbox[value="rubrics.access"]');
        if (!accessCb) {return;}

        const listCb = form.querySelector('.perm-checkbox[value="rubrics.list"]');
        if (!listCb) {return;}

        const rubricActionValues = ['rubrics.create', 'rubrics.edit', 'rubrics.delete'];
        const rubricActionCbs = rubricActionValues
            .map((v) => form.querySelector(`.perm-checkbox[value="${v}"]`))
            .filter(Boolean);

        const permsCb = form.querySelector('.perm-checkbox[value="rubrics.permissions"]');

        const allSubCbs = [listCb, ...rubricActionCbs, ...(permsCb ? [permsCb] : [])];

        accessCb.addEventListener('change', () => {
            if (accessCb.checked) {
                if (!allSubCbs.some((cb) => cb.checked)) {listCb.checked = true;}
            } else {
                allSubCbs.forEach((cb) => {
                    cb.checked = false;
                });
            }
            refreshPermsButton();
        });

        listCb.addEventListener('change', () => {
            if (listCb.checked) {
                accessCb.checked = true;
            } else {
                rubricActionCbs.forEach((cb) => {
                    cb.checked = false;
                });
                if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
            }
            refreshPermsButton();
        });

        rubricActionCbs.forEach((cb) => {
            cb.addEventListener('change', () => {
                if (cb.checked) {
                    listCb.checked = true;
                    accessCb.checked = true;
                } else {
                    if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
                }
                refreshPermsButton();
            });
        });

        if (permsCb) {
            permsCb.addEventListener('change', () => {
                if (permsCb.checked) {
                    accessCb.checked = true;
                } else {
                    if (!allSubCbs.some((c) => c.checked)) {accessCb.checked = false;}
                }
                refreshPermsButton();
            });
        }
    })();

    async function saveInfo() {
        if (!isInfoDirty()) {return;}

        btnSaveInfo.disabled = true;
        const originalHtml = btnSaveInfo.innerHTML;
        btnSaveInfo.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
        statusInfo.textContent = '';

        try {
            const payload = {
                name: nameInput.value,
                description: descInput?.value ?? '',
                is_default: defaultToggle?.checked ? 1 : 0,
            };
            const res = await fetch(cfg.saveInfoUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify(payload),
            });
            const json = await res.json();

            if (json.ok) {
                baselineName = nameInput.value;
                baselineDesc = descInput?.value ?? '';
                baselineDefault = !!defaultToggle?.checked;
                statusInfo.textContent = 'Сохранено ' + new Date().toLocaleTimeString('ru');
                showToast(json.message ?? 'Сохранено', 'success');
            } else {
                const msg = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Ошибка');
                showToast(msg, 'error');
            }
        } catch (e) {
            showToast('Ошибка соединения', 'error');
        } finally {
            btnSaveInfo.innerHTML = originalHtml;
            refreshInfoButton();
        }
    }

    async function savePermissions() {
        if (!isPermsDirty()) {return;}

        btnSavePerms.disabled = true;
        const originalHtml = btnSavePerms.innerHTML;
        btnSavePerms.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';
        statusPerms.textContent = '';

        try {
            const permissions = Array.from(form.querySelectorAll('.perm-checkbox:checked')).map((cb) => cb.value);

            const res = await fetch(cfg.savePermsUrl, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ permissions }),
            });
            const json = await res.json();

            if (json.ok) {
                baselinePerms = collectPermissions();
                statusPerms.textContent = 'Сохранено ' + new Date().toLocaleTimeString('ru');
                showToast(json.message ?? 'Сохранено', 'success');
            } else {
                const msg = json.errors ? Object.values(json.errors).flat().join(' ') : (json.message ?? 'Ошибка');
                showToast(msg, 'error');
            }
        } catch (e) {
            showToast('Ошибка соединения', 'error');
        } finally {
            btnSavePerms.innerHTML = originalHtml;
            refreshPermsButton();
        }
    }

    btnSaveInfo.addEventListener('click', saveInfo);
    btnSavePerms.addEventListener('click', savePermissions);

    document.addEventListener('keydown', (e) => {
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
            if (isInfoDirty()) {saveInfo();}
            if (isPermsDirty()) {savePermissions();}
        }
    });

    wireCollapseControls();

    function wireCollapseControls() {
        document.querySelectorAll('.perm-section-header').forEach((header) => {
            const body = document.querySelector(header.dataset.bsTarget);
            if (!body) {return;}
            body.addEventListener('hide.bs.collapse', () => header.classList.add('collapsed'));
            body.addEventListener('show.bs.collapse', () => header.classList.remove('collapsed'));
        });

        document.getElementById('btnCollapseAll').addEventListener('click', () => {
            document.querySelectorAll('.perm-section-body.show').forEach((body) => {
                bootstrap.Collapse.getOrCreateInstance(body).hide();
            });
        });
        document.getElementById('btnExpandAll').addEventListener('click', () => {
            document.querySelectorAll('.perm-section-body:not(.show)').forEach((body) => {
                bootstrap.Collapse.getOrCreateInstance(body).show();
            });
        });
    }
})();
