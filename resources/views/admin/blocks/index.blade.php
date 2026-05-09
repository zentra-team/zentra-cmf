@extends('admin.layout')

@section('title', 'Блоки')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/blocks-index.css') }}">

@endpush

@section('content')
<div class="ztr-page-title"><i class="bi bi-puzzle me-2"></i>Блоки</div>

<div class="card">
    <div class="card-header ztr-blocks-card-header">
        <ul class="nav nav-tabs" id="blocksTabs" role="tablist">
            @if($canList)
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabList" type="button">
                    <i class="bi bi-list-ul me-1"></i>Блоки
                </button>
            </li>
            @endif
            @if($canCreate)
            <li class="nav-item">
                <button class="nav-link {{ $canList ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#tabCreate" type="button">
                    <i class="bi bi-plus-circle me-1"></i>Создать
                </button>
            </li>
            @endif
            @if($canGroups)
            <li class="nav-item">
                <button class="nav-link {{ (!$canList && !$canCreate) ? 'active' : '' }}" data-bs-toggle="tab" data-bs-target="#tabGroups" type="button">
                    <i class="bi bi-folder me-1"></i>Группы
                </button>
            </li>
            @endif
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">

    @if($canList)
    <div class="tab-pane fade show active" id="tabList">

        @php
            $allGroups = $groups->filter(fn($g) => $g->blocks->isNotEmpty());
            $hasUngrouped = $ungrouped->isNotEmpty();
        @endphp

        @if($allGroups->isEmpty() && !$hasUngrouped)
            <div class="text-center py-5 text-muted">
                <i class="bi bi-puzzle ztr-blocks-empty-icon"></i>
                Нет ни одного блока. @if($canCreate)<a href="#tabCreate" data-bs-toggle="tab">Создать первый</a>@endif
            </div>
        @else

            @foreach($allGroups as $group)
            <div class="block-group-section">
                <div class="block-group-header" data-bs-toggle="collapse" data-bs-target="#group-{{ $group->id }}">
                    <i class="bi bi-chevron-down"></i>
                    <span class="group-title">{{ $group->title }}</span>
                    <span class="group-count">({{ $group->blocks->count() }})</span>
                    @if($group->description)
                    <span class="group-desc">— {{ $group->description }}</span>
                    @endif
                    @if($canCreate)
                    <span class="ms-auto d-flex gap-1">
                        <button class="btn btn-xs btn-secondary py-0 px-1 btn-create-in-group"
                            data-group-id="{{ $group->id }}"
                            title="Создать блок в этой группе">
                            <i class="bi bi-plus"></i>
                        </button>
                    </span>
                    @endif
                </div>
                <div class="collapse show block-group-body" id="group-{{ $group->id }}">
                    @include('admin.blocks._table', ['blocks' => $group->blocks, 'canCreate' => $canCreate, 'canEdit' => $canEdit, 'canDelete' => $canDelete])
                </div>
            </div>
            @endforeach

            @if($hasUngrouped)
            <div class="block-group-section">
                <div class="block-group-header" data-bs-toggle="collapse" data-bs-target="#group-ungrouped">
                    <i class="bi bi-chevron-down"></i>
                    <span class="group-title">Без группы</span>
                    <span class="group-count">({{ $ungrouped->count() }})</span>
                </div>
                <div class="collapse show block-group-body" id="group-ungrouped">
                    @include('admin.blocks._table', ['blocks' => $ungrouped, 'canCreate' => $canCreate, 'canEdit' => $canEdit, 'canDelete' => $canDelete])
                </div>
            </div>
            @endif

        @endif
    </div>
    @endif

    @if($canCreate)
    <div class="tab-pane fade {{ $canList ? '' : 'show active' }}" id="tabCreate">
        <div class="p-4 ztr-blocks-create-form">
            <form method="POST" action="{{ route('admin.blocks.store') }}" id="createForm">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Название <span class="text-danger">*</span></label>
                    <input type="text" name="title" id="createTitle" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Алиас <span class="text-danger">*</span></label>
                    <input type="text" name="alias" id="createAlias" class="form-control" required>
                    <div class="form-text">Только латиница, цифры и символ подчёркивания</div>
                    <div class="form-text mt-2">Будет сформирован тег: <code id="createTagPreview" class="text-warning ztr-blocks-tag-preview">[block:]</code></div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Описание</label>
                    <input type="text" name="description" class="form-control">
                </div>
                <div class="mb-3">
                    <label class="form-label">Группа</label>
                    <select name="group_id" id="createGroupId" class="form-select">
                        <option value="">— Без группы —</option>
                        @foreach($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->title }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-4">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" name="is_wysiwyg" id="createWysiwyg" value="1">
                        <label class="form-check-label fw-semibold" for="createWysiwyg">
                            <span id="createWysiwygLabel">Обычное поле для HTML/CSS/JS-кода</span>
                        </label>
                    </div>
                    <div class="form-text ms-4 mb-2" id="createWysiwygHint">
                        Ace-редактор с подсветкой синтаксиса. Подходит для вёрстки и скриптов.
                    </div>
                    <div class="ztr-blocks-type-warning">
                        <i class="bi bi-exclamation-triangle-fill"></i>
                        <div>
                            <strong>Тип блока задаётся один раз - при создании.</strong>
                            Изменить его позже нельзя: чтобы перейти с кода на WYSIWYG (или обратно), придётся удалить блок и создать заново. Выбирайте внимательно.
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-circle me-1"></i>Создать и редактировать
                </button>
            </form>
        </div>
    </div>
    @endif

    @if($canGroups)
    <div class="tab-pane fade {{ (!$canList && !$canCreate) ? 'show active' : '' }}" id="tabGroups">
        <div class="p-3">

            <table class="table table-hover table-sm mb-4 ztr-blocks-groups-table">
                <thead>
                    <tr>
                        <th class="ztr-blocks-col-drag"></th>
                        <th class="ztr-blocks-col-id">ID</th>
                        <th>Название</th>
                        <th>Описание</th>
                        <th class="ztr-blocks-col-count">Блоков</th>
                        <th class="ztr-blocks-col-group-actions"></th>
                    </tr>
                </thead>
                <tbody id="groupsTableBody">
                    @forelse($groups as $group)
                    <tr data-group-id="{{ $group->id }}">
                        <td><i class="bi bi-grip-vertical group-drag-handle text-muted"></i></td>
                        <td class="text-muted">{{ $group->id }}</td>
                        <td class="ztr-nowrap">
                            <span class="group-title-text">{{ $group->title }}</span>
                            <input type="text" class="form-control form-control-sm group-title-input d-none ztr-blocks-group-title-input"
                                value="{{ $group->title }}">
                        </td>
                        <td class="ztr-blocks-col-desc">
                            <span class="group-desc-text text-muted ztr-blocks-desc">{{ $group->description }}</span>
                            <input type="text" class="form-control form-control-sm group-desc-input d-none ztr-blocks-group-desc-input"
                                value="{{ $group->description }}">
                        </td>
                        <td class="text-muted ztr-blocks-desc">{{ $group->blocks->count() }}</td>
                        <td class="text-end ztr-nowrap">
                            <button class="btn btn-sm btn-outline-success group-edit-btn" title="Редактировать">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button class="btn btn-sm btn-outline-danger group-delete-btn" title="Удалить"
                                data-group-id="{{ $group->id }}" data-group-title="{{ $group->title }}"
                                data-block-count="{{ $group->blocks->count() }}">
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    @empty
                    <tr id="noGroupsRow">
                        <td colspan="6" class="text-center text-muted py-3">Групп нет</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="card ztr-blocks-add-group-card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Добавить группу</h6>
                    <div class="d-flex gap-3 align-items-end flex-wrap">
                        <div>
                            <label class="form-label mb-1 ztr-blocks-small-label">Наименование <span class="text-danger">*</span></label>
                            <input type="text" id="newGroupTitle" class="form-control form-control-sm ztr-blocks-input-title" placeholder="Название группы">
                        </div>
                        <div>
                            <label class="form-label mb-1 ztr-blocks-small-label">Описание</label>
                            <input type="text" id="newGroupDesc" class="form-control form-control-sm ztr-blocks-input-desc" placeholder="Краткое описание">
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="btnAddGroup">
                            <i class="bi bi-plus-circle me-1"></i>Добавить группу
                        </button>
                    </div>
                </div>
            </div>

        </div>
    </div>
    @endif

        </div>
    </div>
</div>

@if($canCreate)
<div class="modal fade" id="copyModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Копирование блока</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Алиас <span class="text-danger">*</span></label>
                    <input type="text" id="copyAlias" class="form-control" placeholder="new_alias">
                </div>
                <div class="mb-0">
                    <label class="form-label">Группа</label>
                    <select id="copyGroupId" class="form-select">
                        <option value="">— Без группы —</option>
                        @foreach($groups as $g)
                        <option value="{{ $g->id }}">{{ $g->title }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnCopyConfirm" disabled>
                    <i class="bi bi-copy me-1"></i>Создать
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($canDelete)
<div class="modal fade" id="deleteBlockModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Удаление блока</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Удалить блок <strong id="deleteBlockName"></strong>?</p>
                <p class="mb-0 text-muted ztr-blocks-desc">Это действие необратимо. Места использования тега в шаблонах останутся как есть.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteBlockConfirm">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($canGroups)
<div class="modal fade" id="deleteGroupModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Удаление группы</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Удалить группу <strong id="deleteGroupName"></strong>?</p>
                <p class="mb-0 text-muted ztr-blocks-desc" id="deleteGroupNote"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteGroupConfirm">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="{{ route('admin.asset', 'js/blocks-index.js') }}"></script>
<script>
(function() {
    const p = new URLSearchParams(location.search);
    if (p.get('tab') === 'groups') {
        const btn = document.querySelector('[data-bs-target="#tabGroups"]');
        if (btn) new bootstrap.Tab(btn).show();
        history.replaceState(null, '', location.pathname);
    }
})();
</script>
@endpush
