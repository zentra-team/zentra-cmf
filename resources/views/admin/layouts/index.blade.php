@extends('admin.layout')

@section('title', 'Макеты сайта')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/layouts-index.css') }}">
<link rel="stylesheet" href="{{ route('admin.asset', 'css/layouts-files-table.css') }}">
@endpush

@section('content')
<div class="ztr-page-title"><i class="bi bi-layout-text-sidebar me-2"></i>Макеты сайта</div>

@if(!$canList && !$canCreate && !$canFiles)
<div class="ztr-alert-info">
    <i class="bi bi-shield-lock-fill ztr-alert-icon"></i>
    <div>У вашей группы нет прав на просмотр или управление макетами сайта.</div>
</div>
@else

<div class="card">
    <div class="card-header ztr-li-card-header">
        <ul class="nav nav-tabs" id="layoutTabs" role="tablist">
            @if($canList)
            <li class="nav-item">
                <button class="nav-link {{ $activeTab === 'layouts' ? 'active' : '' }}"
                    data-bs-toggle="tab" data-bs-target="#tabLayouts" type="button">
                    <i class="bi bi-layout-text-sidebar me-1"></i>Макеты
                </button>
            </li>
            @endif
            @if($canCreate)
            <li class="nav-item">
                <button class="nav-link {{ (!$canList && $activeTab === 'create') || ($canList && $activeTab === 'create') ? 'active' : '' }}"
                    data-bs-toggle="tab" data-bs-target="#tabCreate" type="button">
                    <i class="bi bi-plus-circle me-1"></i>Создать
                </button>
            </li>
            @endif
            @if($canList || $canFiles)
            <li class="nav-item">
                <button class="nav-link {{ $activeTab === 'css' ? 'active' : '' }}"
                    data-bs-toggle="tab" data-bs-target="#tabCss" type="button">
                    <i class="bi bi-filetype-css me-1"></i>CSS файлы
                </button>
            </li>
            <li class="nav-item">
                <button class="nav-link {{ $activeTab === 'js' ? 'active' : '' }}"
                    data-bs-toggle="tab" data-bs-target="#tabJs" type="button">
                    <i class="bi bi-filetype-js me-1"></i>JS файлы
                </button>
            </li>
            @endif
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">

            @if($canList)
            <div class="tab-pane fade {{ $activeTab === 'layouts' ? 'show active' : '' }}" id="tabLayouts">
                @if($layouts->isEmpty())
                    <p class="text-muted ztr-li-empty">Макетов пока нет.@if($canCreate) Создайте первый во вкладке «Создать».@endif</p>
                @else
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ztr-li-col-id">#</th>
                                <th>Название</th>
                                <th class="ztr-li-col-rubrics text-center">Рубрик</th>
                                <th class="ztr-li-col-actions"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($layouts as $layout)
                            <tr id="row-{{ $layout->id }}">
                                <td class="text-muted">{{ $layout->id }}</td>
                                <td>
                                    <a href="{{ route('admin.layouts.edit', $layout) }}" class="fw-500">
                                        {{ $layout->title }}
                                    </a>
                                </td>
                                <td class="text-center">
                                    @if($layout->rubrics_count > 0)
                                        <span class="badge bg-secondary">{{ $layout->rubrics_count }}</span>
                                    @else
                                        <span class="text-muted ztr-li-dash">—</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    @if($canEdit)
                                    <a href="{{ route('admin.layouts.edit', $layout) }}"
                                        class="btn btn-sm btn-outline-success" title="Редактировать">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    @else
                                    <a href="{{ route('admin.layouts.edit', $layout) }}"
                                        class="btn btn-sm btn-outline-secondary" title="Просмотр">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    @endif
                                    @if($canCreate)
                                    <button type="button" class="btn btn-sm btn-outline-success btn-copy"
                                        data-id="{{ $layout->id }}"
                                        data-title="{{ $layout->title }}"
                                        title="Копировать">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                    @endif
                                    @if($canDelete)
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-delete"
                                        data-id="{{ $layout->id }}"
                                        data-title="{{ $layout->title }}"
                                        title="Удалить">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>
            @endif

            @if($canCreate)
            <div class="tab-pane fade {{ $activeTab === 'create' ? 'show active' : '' }}" id="tabCreate">
                <form method="POST" action="{{ route('admin.layouts.store') }}" class="ztr-li-create-form">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Название макета</label>
                        <input type="text" name="title" class="form-control @error('title') is-invalid @enderror"
                            value="{{ old('title') }}" autofocus placeholder="Например: Основной">
                        @error('title')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Создать
                    </button>
                </form>
            </div>
            @endif

            @if($canList || $canFiles)
            <div class="tab-pane fade {{ $activeTab === 'css' ? 'show active' : '' }}" id="tabCss">
                @include('admin.layouts._files-table', ['files' => $cssFiles, 'type' => 'css', 'canFiles' => $canFiles])
            </div>

            <div class="tab-pane fade {{ $activeTab === 'js' ? 'show active' : '' }}" id="tabJs">
                @include('admin.layouts._files-table', ['files' => $jsFiles, 'type' => 'js', 'canFiles' => $canFiles])
            </div>
            @endif

        </div>
    </div>
</div>

@if($canCreate)
<div class="modal fade" id="modalCopy" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered ztr-li-modal-copy">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-copy me-2"></i>Копировать макет</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3 ztr-li-copy-source">
                    Копия макета <strong id="copySourceTitle"></strong>
                </p>
                <label class="form-label">Название новой копии</label>
                <input type="text" id="copyTitle" class="form-control" placeholder="Уникальное название">
                <div id="copyError" class="text-danger mt-1 ztr-li-copy-error"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnCopyConfirm">
                    <i class="bi bi-copy me-1"></i>Копировать
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($canDelete)
<div class="modal fade" id="modalDelete" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Удалить макет</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 ztr-li-delete-body">
                    Удалить макет <strong id="deleteTitle"></strong>? Это действие необратимо.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteConfirm">Удалить</button>
            </div>
        </div>
    </div>
</div>
@endif

@endif
@endsection

@push('scripts')
<script>
window.ZentraConfig = {
    canCreate: {{ $canCreate ? 'true' : 'false' }},
    canDelete: {{ $canDelete ? 'true' : 'false' }},
};
</script>
<script src="{{ route('admin.asset', 'js/layouts-index.js') }}"></script>
@endpush
