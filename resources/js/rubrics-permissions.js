document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('tbody tr').forEach((row) => {
        const canAll = row.querySelector('input[name$="[can_all]"]');
        const canModerated = row.querySelector('input[name$="[can_create_moderated]"]');
        const canCreate = row.querySelector('input[name$="[can_create]"]');

        if (!canAll) {return;}

        const subCbs = [...row.querySelectorAll('input[type="checkbox"]')].filter((cb) => cb !== canAll);

        const syncAllFromSubs = () => {
            const createUnitOn = (canCreate?.checked ?? false) || (canModerated?.checked ?? false);
            const othersOn = subCbs.filter((cb) => cb !== canCreate && cb !== canModerated).every((cb) => cb.checked);
            canAll.checked = createUnitOn && othersOn;
        };

        canAll.addEventListener('change', () => {
            if (canAll.checked) {
                subCbs.forEach((cb) => {
                    cb.checked = true;
                });
                if (canModerated) {canModerated.checked = false;}
                if (canCreate) {canCreate.checked = true;}
            } else {
                subCbs.forEach((cb) => {
                    cb.checked = false;
                });
            }
        });

        subCbs.forEach((cb) => {
            cb.addEventListener('change', () => {
                syncAllFromSubs();
            });
        });

        if (canModerated && canCreate) {
            canModerated.addEventListener('change', () => {
                if (canModerated.checked) {canCreate.checked = false;}
                syncAllFromSubs();
            });
            canCreate.addEventListener('change', () => {
                if (canCreate.checked) {canModerated.checked = false;}
                syncAllFromSubs();
            });
        }

        syncAllFromSubs();
    });
});
