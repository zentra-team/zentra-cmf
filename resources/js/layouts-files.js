const _csrf = document.querySelector('meta[name="csrf-token"]').content;

document.body.appendChild(document.getElementById('modalDeleteFile'));
document.body.appendChild(document.getElementById('modalCreateFile'));
document.body.appendChild(document.getElementById('modalUploadFile'));

const modalDeleteFile = new bootstrap.Modal(document.getElementById('modalDeleteFile'));
const modalCreateFile = new bootstrap.Modal(document.getElementById('modalCreateFile'));
const modalUploadFile = new bootstrap.Modal(document.getElementById('modalUploadFile'));
let _createFileType = null;
let _uploadFileType = null;
let _uploadFileObj = null;

function setUploadFile(file) {
    if (!file) {return;}
    _uploadFileObj = file;
    document.getElementById('uploadFileSelectedName').textContent = file.name;
    document.getElementById('btnUploadFileConfirm').disabled = false;
    document.getElementById('uploadFileError').style.display = 'none';
}

document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-upload-file');
    if (!btn) {return;}
    _uploadFileType = btn.dataset.type;
    _uploadFileObj = null;
    document.getElementById('modalUploadFileTitle').innerHTML =
        '<i class="bi bi-upload me-2"></i>Загрузить ' + _uploadFileType.toUpperCase() + '-файл';
    document.getElementById('uploadFileInput').accept = '.' + _uploadFileType;
    document.getElementById('uploadFileInput').value = '';
    document.getElementById('uploadFileSelectedName').textContent = '';
    document.getElementById('uploadFileError').style.display = 'none';
    document.getElementById('uploadFileProgress').classList.add('d-none');
    document.getElementById('btnUploadFileConfirm').disabled = true;
    modalUploadFile.show();
});

document.getElementById('uploadFileInput').addEventListener('change', (e) => {
    setUploadFile(e.target.files[0] ?? null);
});

const dropZone = document.getElementById('uploadFileDropZone');
dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.style.background = 'rgba(255,255,255,.05)';
});
dropZone.addEventListener('dragleave', () => {
    dropZone.style.background = '';
});
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.style.background = '';
    setUploadFile(e.dataTransfer.files[0] ?? null);
});
dropZone.addEventListener('click', (e) => {
    if (!e.target.closest('label')) {
        document.getElementById('uploadFileInput').click();
    }
});

document.getElementById('btnUploadFileConfirm').addEventListener('click', async () => {
    if (!_uploadFileObj) {return;}

    const errEl = document.getElementById('uploadFileError');
    const btn = document.getElementById('btnUploadFileConfirm');
    errEl.style.display = 'none';

    btn.disabled = true;
    document.getElementById('uploadFileProgress').classList.remove('d-none');

    const formData = new FormData();
    formData.append('file', _uploadFileObj);
    formData.append('_token', _csrf);

    try {
        const res = await fetch(`/admin/layouts/assets/${_uploadFileType}/upload`, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: formData,
        });
        const data = await res.json();

        if (res.ok && data.ok) {
            window.location = data.redirect;
            return;
        }

        document.getElementById('uploadFileProgress').classList.add('d-none');
        errEl.textContent = data.errors?.file?.[0] ?? data.message ?? 'Ошибка загрузки';
        errEl.style.display = 'block';
    } catch (e) {
        document.getElementById('uploadFileProgress').classList.add('d-none');
        errEl.textContent = 'Ошибка запроса. Попробуйте ещё раз.';
        errEl.style.display = 'block';
    }

    btn.disabled = false;
});

document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-create-file');
    if (!btn) {return;}
    _createFileType = btn.dataset.type;
    document.getElementById('modalCreateFileTitle').innerHTML =
        '<i class="bi bi-file-earmark-plus me-2"></i>Создать ' + _createFileType.toUpperCase() + '-файл';
    document.getElementById('createFileInput').value = '';
    document.getElementById('createFileInput').placeholder =
        _createFileType === 'css' ? 'style.css или subdir/style.css' : 'app.js или subdir/app.js';
    document.getElementById('createFileError').style.display = 'none';
    modalCreateFile.show();
    setTimeout(() => document.getElementById('createFileInput').focus(), 300);
});

document.getElementById('btnCreateFileConfirm').addEventListener('click', async () => {
    const filename = document.getElementById('createFileInput').value.trim();
    const errEl = document.getElementById('createFileError');
    const btn = document.getElementById('btnCreateFileConfirm');
    errEl.style.display = 'none';

    if (!filename) {
        errEl.textContent = 'Введите имя файла';
        errEl.style.display = 'block';
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>';

    try {
        const res = await fetch(`/admin/layouts/assets/${_createFileType}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': _csrf,
            },
            body: JSON.stringify({ filename }),
        });
        const data = await res.json();

        if (res.ok && data.ok) {
            window.location = data.redirect;
            return;
        }

        errEl.textContent = data.errors?.filename?.[0] ?? data.message ?? 'Ошибка';
        errEl.style.display = 'block';
    } catch (e) {
        errEl.textContent = 'Ошибка запроса. Попробуйте ещё раз.';
        errEl.style.display = 'block';
    }

    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-plus-lg me-1"></i>Создать';
});

document.getElementById('createFileInput').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') {document.getElementById('btnCreateFileConfirm').click();}
});

let _deleteFileType = null;
let _deleteFileName = null;

document.addEventListener('click', (e) => {
    const btn = e.target.closest('.btn-delete-file');
    if (!btn) {return;}
    _deleteFileType = btn.dataset.type;
    _deleteFileName = btn.dataset.file;
    document.getElementById('deleteFileName').textContent = '«' + _deleteFileName + '»';
    modalDeleteFile.show();
});

document.getElementById('btnDeleteFileConfirm').addEventListener('click', async () => {
    const btn = document.getElementById('btnDeleteFileConfirm');
    btn.disabled = true;

    const res = await fetch(`/admin/layouts/assets/${_deleteFileType}/${_deleteFileName}`, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': _csrf },
    });
    const data = await res.json();

    btn.disabled = false;
    modalDeleteFile.hide();

    if (data.ok) {
        const row = document.querySelector(`[data-file-row="${_deleteFileType}/${_deleteFileName}"]`);
        if (row) {
            const tbody = row.closest('tbody');
            row.remove();

            if (tbody && tbody.querySelectorAll('tr').length === 0) {
                const table = tbody.closest('table');
                if (table) {
                    const msg = document.createElement('p');
                    msg.className = 'text-muted mb-0';
                    msg.style.fontSize = '.875rem';
                    msg.textContent = 'Файлов нет. Создайте или загрузите первый файл.';
                    table.replaceWith(msg);
                }
            }
        }
        showToast('Файл удалён', 'success');
    } else {
        showToast(data.message ?? 'Ошибка удаления', 'error');
    }
});
