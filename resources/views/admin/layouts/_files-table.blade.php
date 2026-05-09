
@if($canFiles)
<div class="mb-3 d-flex gap-2">
    <button type="button" class="btn btn-primary btn-sm btn-create-file" data-type="{{ $type }}">
        <i class="bi bi-plus-lg me-1"></i>Создать файл
    </button>
    <button type="button" class="btn btn-secondary btn-sm btn-upload-file" data-type="{{ $type }}">
        <i class="bi bi-upload me-1"></i>Загрузить файл
    </button>
</div>
@endif

@if(empty($files))
    <p class="text-muted mb-0 ztr-lft-empty">
        Файлов нет.@if($canFiles) Создайте или загрузите первый файл.@endif
    </p>
@else
    <table class="table table-hover mb-0">
        <thead>
            <tr>
                <th>Файл</th>
                <th class="ztr-lft-col-path">Путь</th>
                <th class="ztr-lft-col-size text-end">Размер</th>
                <th class="ztr-lft-col-modified text-end">Изменён</th>
                <th class="ztr-lft-col-actions"></th>
            </tr>
        </thead>
        <tbody>
            @foreach($files as $file)
            <tr data-file-row="{{ $type }}/{{ $file['name'] }}">
                <td class="align-middle">
                    <a href="{{ route('admin.layouts.asset.edit', [$type, $file['name']]) }}">
                        {{ $file['name'] }}
                    </a>
                </td>
                <td class="align-middle text-muted ztr-lft-cell-path">
                    /assets/{{ $type }}/{{ $file['name'] }}
                </td>
                <td class="align-middle text-end text-muted ztr-lft-cell-size">
                    {{ number_format($file['size'] / 1024, 1) }} KB
                </td>
                <td class="align-middle text-end text-muted ztr-lft-cell-modified">
                    {{ date('d.m.Y H:i', $file['modified']) }}
                </td>
                <td class="align-middle text-end ztr-lft-cell-actions">
                    @if($canFiles)
                    <a href="{{ route('admin.layouts.asset.edit', [$type, $file['name']]) }}"
                        class="btn btn-sm btn-outline-success" title="Редактировать">
                        <i class="bi bi-pencil"></i>
                    </a>
                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete-file"
                        data-type="{{ $type }}"
                        data-file="{{ $file['name'] }}"
                        title="Удалить">
                        <i class="bi bi-trash"></i>
                    </button>
                    @else
                    <a href="{{ route('admin.layouts.asset.edit', [$type, $file['name']]) }}"
                        class="btn btn-sm btn-outline-secondary" title="Просмотр">
                        <i class="bi bi-eye"></i>
                    </a>
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
@endif

@once

@if($canFiles)
<div class="modal fade" id="modalUploadFile" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered ztr-lft-modal-upload">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalUploadFileTitle">
                    <i class="bi bi-upload me-2"></i>Загрузить файл
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="uploadFileDropZone"
                    class="border border-secondary rounded p-4 text-center ztr-lft-dropzone">
                    <i class="bi bi-cloud-upload ztr-lft-upload-icon"></i>
                    <p class="mt-2 mb-1 text-secondary">Перетащите файл сюда</p>
                    <p class="text-secondary mb-3 ztr-lft-upload-or">или</p>
                    <label class="btn btn-sm btn-outline-secondary" for="uploadFileInput">Выбрать файл</label>
                    <input type="file" id="uploadFileInput" class="d-none">
                    <p class="text-secondary mt-2 mb-0 ztr-lft-upload-name" id="uploadFileSelectedName"></p>
                </div>
                <div id="uploadFileProgress" class="mt-3 d-none">
                    <div class="progress ztr-lft-progress">
                        <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
                    </div>
                    <p class="text-secondary mt-2 mb-0 text-center ztr-lft-progress-label">Загрузка...</p>
                </div>
                <div id="uploadFileError" class="text-danger mt-2 ztr-lft-upload-error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnUploadFileConfirm" disabled>
                    <i class="bi bi-upload me-1"></i>Загрузить
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDeleteFile" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Удалить файл</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 ztr-lft-delete-body">
                    Удалить файл <strong id="deleteFileName"></strong>? Это действие необратимо.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteFileConfirm">Удалить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalCreateFile" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered ztr-lft-modal-create">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalCreateFileTitle">
                    <i class="bi bi-file-earmark-plus me-2"></i>Создать файл
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label">Имя файла</label>
                <input type="text" id="createFileInput" class="form-control"
                    placeholder="style.css или subdir/style.css"
                    autocomplete="off" spellcheck="false">
                <div class="form-text text-muted mt-1">
                    Расширение будет добавлено автоматически, если не указано.
                    Поддерживаются поддиректории: <code>dir/file.css</code>
                </div>
                <div id="createFileError" class="text-danger mt-2 ztr-lft-create-error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnCreateFileConfirm">
                    <i class="bi bi-plus-lg me-1"></i>Создать
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@push('scripts')
<script src="{{ route('admin.asset', 'js/layouts-files.js') }}"></script>
@endpush
@endonce
