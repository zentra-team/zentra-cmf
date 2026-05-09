@extends('admin.layout')

@section('title', 'Модули')

@section('content')
<div class="ztr-page-header d-flex align-items-center justify-content-between mb-4">
    <h1 class="ztr-page-title mb-0"><i class="bi bi-box-seam me-2"></i>Модули</h1>
</div>

<ul class="nav nav-tabs mb-4" id="moduleTabs">
    <li class="nav-item">
        <a class="nav-link active" data-bs-toggle="tab" href="#tabInstalled">
            <i class="bi bi-check-circle me-1"></i> Установленные
            @if($installed->count())
                <span class="badge bg-secondary ms-1">{{ $installed->count() }}</span>
            @endif
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" data-bs-toggle="tab" href="#tabAvailable">
            <i class="bi bi-download me-1"></i> Доступные
            @if(count($available))
                <span class="badge bg-secondary ms-1">{{ count($available) }}</span>
            @endif
        </a>
    </li>
</ul>

<div class="tab-content">

    <div class="tab-pane fade show active" id="tabInstalled">

        @if($installed->isEmpty())
            <div class="text-center py-5 text-secondary">
                <i class="bi bi-box-seam" style="font-size:3rem;opacity:.3"></i>
                <p class="mt-3 mb-0">Нет установленных модулей</p>
                <p style="font-size:.875rem">Перейдите на вкладку «Доступные» чтобы установить первый модуль</p>
            </div>
        @else
            <div class="card ztr-card">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0" style="font-size:.875rem">
                        <thead>
                            <tr>
                                <th style="width:34px"></th>
                                <th>Название</th>
                                <th style="width:200px">Системный тег</th>
                                <th style="width:130px">Версия</th>
                                <th style="width:80px" class="text-center">Статус</th>
                                <th style="width:160px" class="text-end">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($installed as $module)
                            <tr id="row_{{ $module->sys_name }}">

                                <td class="text-center align-middle">
                                    <i class="bi bi-info-circle text-secondary"
                                       data-bs-toggle="tooltip"
                                       data-bs-placement="right"
                                       title="{{ $module->description }}{{ $module->github ? ' · ' . $module->github : '' }}"
                                       style="cursor:default"></i>
                                </td>

                                <td class="align-middle">
                                    @if($module->has_admin_page)
                                        <a href="{{ route('admin.modules.dispatch', $module->sys_name) }}" class="text-decoration-none">
                                            {{ $module->name }}
                                        </a>
                                    @else
                                        <span style="color:var(--ztr-purple-400)">{{ $module->name }}</span>
                                    @endif
                                </td>

                                <td class="align-middle">
                                    @if($module->tag)
                                        <div class="d-flex align-items-center gap-1">
                                            <code class="text-warning" style="font-size:.8rem">{{ $module->tag }}</code>
                                            <button class="btn btn-link btn-sm p-0 text-secondary btn-copy-tag"
                                                    data-tag="{{ $module->tag }}"
                                                    title="Скопировать тег">
                                                <i class="bi bi-copy" style="font-size:.8rem"></i>
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-secondary">—</span>
                                    @endif
                                </td>

                                <td class="align-middle">
                                    <span class="version-cell" id="ver_{{ $module->sys_name }}">
                                        v{{ $module->version }}
                                    </span>
                                    @if($module->github)
                                        <span class="update-badge d-none badge bg-warning text-dark ms-1"
                                              id="upd_{{ $module->sys_name }}"
                                              style="cursor:pointer;font-size:.7rem"
                                              data-sys-name="{{ $module->sys_name }}"
                                              data-name="{{ $module->name }}"
                                              data-repo="{{ $module->github }}"
                                              data-current="{{ $module->version }}">
                                            ↑ обновление
                                        </span>
                                    @endif
                                </td>

                                <td class="align-middle text-center">
                                    <div class="form-check form-switch d-inline-block m-0">
                                        <input class="form-check-input toggle-module" type="checkbox"
                                               data-sys-name="{{ $module->sys_name }}"
                                               data-name="{{ $module->name }}"
                                               {{ $module->is_active ? 'checked' : '' }}
                                               title="{{ $module->is_active ? 'Выключить' : 'Включить' }}">
                                    </div>
                                </td>

                                <td class="align-middle text-end" style="white-space:nowrap">
                                    @if($module->has_admin_page)
                                        <a href="{{ route('admin.modules.dispatch', $module->sys_name) }}"
                                           class="btn btn-sm btn-outline-success" title="Открыть">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                        </a>
                                    @endif
                                    @if($module->has_settings)
                                        <a href="{{ route('admin.modules.dispatch', [$module->sys_name, 'settings']) }}"
                                           class="btn btn-sm btn-outline-success" title="Настройки">
                                            <i class="bi bi-gear"></i>
                                        </a>
                                    @endif
                                    <button class="btn btn-sm btn-outline-success btn-reinstall"
                                            data-sys-name="{{ $module->sys_name }}"
                                            data-name="{{ $module->name }}"
                                            data-version="{{ $module->version }}"
                                            title="Переустановить">
                                        <i class="bi bi-arrow-repeat"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger btn-delete"
                                            data-sys-name="{{ $module->sys_name }}"
                                            data-name="{{ $module->name }}"
                                            title="Удалить">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

    <div class="tab-pane fade" id="tabAvailable">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <p class="text-secondary mb-0" style="font-size:.875rem">
                Модули, найденные в папке <code>modules/</code> но ещё не установленные.
            </p>
            <button class="btn btn-primary btn-sm" id="btnUploadModule">
                <i class="bi bi-upload me-1"></i> Загрузить модуль
            </button>
        </div>

        @if(empty($available))
            <div class="text-center py-5 text-secondary">
                <i class="bi bi-inbox" style="font-size:3rem;opacity:.3"></i>
                <p class="mt-3 mb-0">Нет доступных модулей</p>
                <p style="font-size:.875rem">Загрузите архив модуля (.zip или .tar.gz) кнопкой выше</p>
            </div>
        @else
            <div class="card ztr-card">
                <div class="card-body p-0">
                    <table class="table table-hover mb-0" style="font-size:.875rem">
                        <thead>
                            <tr>
                                <th style="width:34px"></th>
                                <th>Название</th>
                                <th style="width:200px">Системный тег</th>
                                <th style="width:130px">Версия</th>
                                <th style="width:120px"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($available as $info)
                            <tr id="avail_{{ $info['sys_name'] }}">
                                <td class="text-center align-middle">
                                    <i class="bi bi-info-circle text-secondary"
                                       data-bs-toggle="tooltip"
                                       title="{{ $info['description'] ?? $info['name'] }}"
                                       style="cursor:default"></i>
                                </td>
                                <td class="align-middle" style="color:var(--ztr-purple-400)">{{ $info['name'] }}</td>
                                <td class="align-middle">
                                    @if(!empty($info['tag']))
                                        <code class="text-warning" style="font-size:.8rem">{{ $info['tag'] }}</code>
                                    @else
                                        <span class="text-secondary">—</span>
                                    @endif
                                </td>
                                <td class="align-middle text-secondary">v{{ $info['version'] }}</td>
                                <td class="align-middle text-end">
                                    <button class="btn btn-sm btn-primary btn-install"
                                            data-sys-name="{{ $info['sys_name'] }}"
                                            data-name="{{ $info['name'] }}">
                                        <i class="bi bi-plus-lg me-1"></i> Установить
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>

</div>

<div class="modal fade" id="modalUpload" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Загрузить модуль</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="ztr-alert-info mb-3">
                    <i class="bi bi-info-circle-fill ztr-alert-icon"></i>
                    <div>Поддерживаются форматы: <strong>.zip</strong> и <strong>.tar.gz</strong>. Архив должен содержать файл <code>module.json</code>.</div>
                </div>

                <div id="uploadDropZone" class="border border-secondary rounded p-4 text-center"
                     style="border-style:dashed!important;cursor:pointer;transition:background .2s">
                    <i class="bi bi-cloud-upload" style="font-size:2.5rem;opacity:.5"></i>
                    <p class="mt-2 mb-1 text-secondary">Перетащите архив сюда</p>
                    <p class="text-secondary mb-3" style="font-size:.8rem">или</p>
                    <label class="btn btn-sm btn-outline-secondary" for="uploadFileInput">Выбрать файл</label>
                    <input type="file" id="uploadFileInput" accept=".zip,.tar.gz,.gz" class="d-none">
                    <p class="text-secondary mt-2 mb-0" id="uploadFileName" style="font-size:.8rem"></p>
                </div>

                <div id="uploadProgress" class="mt-3 d-none">
                    <div class="progress" style="height:6px">
                        <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
                    </div>
                    <p class="text-secondary mt-2 mb-0 text-center" style="font-size:.8rem">Загрузка и распаковка...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnUploadConfirm" disabled>
                    <i class="bi bi-upload me-1"></i> Загрузить
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalInstallAfterUpload" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="installAfterUploadTitle">Установить модуль</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="installAfterUploadText" style="font-size:.875rem"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnInstallAfterUpload">Установить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalReinstall" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-repeat me-2"></i>Переустановить</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="reinstallText" style="font-size:.875rem"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnReinstallConfirm">Переустановить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDelete" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Удалить модуль</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="deleteText" style="font-size:.875rem"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteConfirm">Удалить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalUpdate" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-arrow-up-circle me-2"></i>Доступно обновление</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p id="updateText" style="font-size:.875rem"></p>
                <div id="updateChangelog" class="ztr-alert-info d-none">
                    <i class="bi bi-journal-text ztr-alert-icon"></i>
                    <div id="updateChangelogText" style="font-size:.8125rem;white-space:pre-wrap"></div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-warning btn-sm" id="btnUpdateConfirm">
                    <i class="bi bi-arrow-up-circle me-1"></i> Обновить
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
window.ZentraConfig = {
    routes: {
        toggle: '{{ route('admin.modules.toggle') }}',
        install: '{{ route('admin.modules.install') }}',
        reinstall: '{{ route('admin.modules.reinstall') }}',
        uninstall: '{{ route('admin.modules.uninstall') }}',
        upload: '{{ route('admin.modules.upload') }}',
        update: '{{ route('admin.modules.update') }}',
        checkUpdates: '{{ route('admin.modules.check-updates') }}'
    }
};
</script>
<script src="{{ route('admin.asset', 'js/modules.js') }}"></script>
@endpush
