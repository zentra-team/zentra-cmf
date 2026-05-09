@extends('admin.layout')

@section('title', 'Навигация')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/navigations-index.css') }}">
@endpush

@section('content')
<div class="ztr-page-title"><i class="bi bi-list-nested me-2"></i>Навигация</div>

<div class="card">
    <div class="card-header ztr-nav-tab-header">
        <ul class="nav nav-tabs" role="tablist">
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabList" type="button">
                    <i class="bi bi-list-ul me-1"></i>Список меню
                </button>
            </li>
            @if($canCreate)
            <li class="nav-item">
                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabCreate" type="button">
                    <i class="bi bi-plus-circle me-1"></i>Создать меню
                </button>
            </li>
            @endif
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">

            <div class="tab-pane fade show active" id="tabList">
                @if($navigations->isEmpty())
                    <p class="text-muted ztr-nav-empty-text">Меню пока нет. Создайте первое во вкладке «Создать меню».</p>
                @else
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th class="ztr-nav-col-id">ID</th>
                                <th>Название</th>
                                <th class="ztr-nav-col-tag">Тег</th>
                                <th class="ztr-nav-col-actions"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($navigations as $nav)
                            <tr>
                                <td class="text-muted align-middle">{{ $nav->id }}</td>
                                <td class="align-middle">
                                    <a href="{{ route('admin.navigations.items', $nav) }}">
                                        {{ $nav->title }}
                                    </a>
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center gap-1">
                                        <code class="text-warning ztr-nav-tag-code">{{ $nav->tag() }}</code>
                                        <button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-copy-tag"
                                            data-tag="{{ $nav->tag() }}" title="Скопировать тег">
                                            <i class="bi bi-copy ztr-nav-copy-icon"></i>
                                        </button>
                                    </div>
                                </td>
                                <td class="align-middle text-end ztr-nav-actions-cell">
                                    <a href="{{ route('admin.navigations.template', $nav) }}"
                                        class="btn btn-sm btn-outline-success" title="Редактировать шаблон">
                                        <i class="bi bi-file-code"></i>
                                    </a>
                                    <a href="{{ route('admin.navigations.items', $nav) }}"
                                        class="btn btn-sm btn-outline-success" title="Редактировать пункты">
                                        <i class="bi bi-list-nested"></i>
                                    </a>
                                    @if($canCreate)
                                    <button type="button" class="btn btn-sm btn-outline-success btn-nav-copy"
                                        data-nav-id="{{ $nav->id }}" data-nav-title="{{ $nav->title }}"
                                        title="Копировать меню">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                    @endif
                                    @if($canDelete)
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-nav-delete"
                                        data-nav-id="{{ $nav->id }}" data-nav-title="{{ $nav->title }}"
                                        title="Удалить меню">
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

            @if($canCreate)
            <div class="tab-pane fade" id="tabCreate">
                <div class="ztr-nav-create-form">
                    <form method="POST" action="{{ route('admin.navigations.store') }}">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label">Название меню <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="navTitle" class="form-control" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label">Алиас <span class="text-danger">*</span></label>
                            <input type="text" name="alias" id="navAlias" class="form-control" required>
                            <div class="form-text">Только латиница, цифры и символ подчёркивания</div>
                            <div class="form-text mt-2">Будет сформирован тег: <code id="navTagPreview" class="text-warning ztr-nav-tag-code">[nav:]</code></div>
                        </div>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Создать меню
                        </button>
                    </form>
                </div>
            </div>
            @endif

        </div>
    </div>
</div>

@if($canCreate)
<div class="modal fade" id="copyNavModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Копирование меню</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3 ztr-nav-modal-hint">
                    Копируется только шаблон меню. Пункты не копируются - создаётся пустое меню.
                </p>
                <div class="mb-3">
                    <label class="form-label">Название <span class="text-danger">*</span></label>
                    <input type="text" id="copyNavTitle" class="form-control">
                </div>
                <div class="mb-0">
                    <label class="form-label">Алиас <span class="text-danger">*</span></label>
                    <input type="text" id="copyNavAlias" class="form-control" placeholder="new_alias">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnCopyNavConfirm" disabled>
                    <i class="bi bi-copy me-1"></i>Создать
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($canDelete)
<div class="modal fade" id="deleteNavModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Удаление меню</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Удалить меню <strong id="deleteNavName"></strong>?</p>
                <p class="mb-0 text-muted ztr-nav-modal-hint">Все пункты меню будут удалены вместе с ним.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteNavConfirm">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
    window.ZentraConfig = {
        csrf: document.querySelector('meta[name="csrf-token"]').content
    };
</script>
<script src="{{ route('admin.asset', 'js/navigations-index.js') }}"></script>
@endpush
