@extends('admin.layout')

@section('title', 'Подполя репитера - ' . $field->title)

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/rubrics-fields.css') }}">
@endpush

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('admin.rubrics.fields', $rubric) }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Поля рубрики
    </a>
    <div class="ztr-page-title mb-0"><i class="bi bi-collection me-2"></i>Подполя «{{ $field->title }}»</div>
</div>

<div class="alert alert-info py-2 mb-3 small">
    <i class="bi bi-info-circle me-1"></i>
    Подполя - это структура одной группы репитера. В документе пользователь добавляет столько групп, сколько нужно, и заполняет в каждой эти подполя.
    Алиас подполя используется в шаблоне рубрики: <code>[value:АЛИАС]</code>.
</div>

@if(!($canEdit ?? false))
<div class="alert alert-danger py-2 mb-3 ztr-readonly-banner">
    <i class="bi bi-eye me-1"></i>Режим только просмотр - у вас нет прав на редактирование рубрик
</div>
@endif

@if($canEdit ?? false)
<div class="card">
    <div class="card-header">Добавить подполе</div>
    <div class="card-body">
        <div class="d-flex align-items-center gap-4 ztr-rubrics-fields-add-row">
            <input type="text" id="newSubfieldTitle" class="form-control flex-grow-1 ztr-rubrics-fields-add-title" placeholder="Название подполя">
            <select id="newSubfieldType" class="form-select flex-shrink-0 ztr-rubrics-fields-type-select">
                @foreach($allTypes as $typeKey => $typeInfo)
                    <option value="{{ $typeKey }}">{{ $typeInfo['name'] }}</option>
                @endforeach
            </select>
            <button type="button" class="btn btn-sm btn-primary flex-shrink-0" id="btnAddSubfield">
                <i class="bi bi-plus-lg me-1"></i>Добавить
            </button>
        </div>
        <div id="addSubfieldError" class="text-danger mt-2 ztr-rubrics-fields-small" style="display:none"></div>
    </div>
</div>
@endif

<div class="card mb-3">
    <div class="card-header">Подполя</div>
    <div class="card-body p-0">
        @if(empty($subfields))
            <p class="text-muted p-3 mb-0 ztr-rubrics-fields-smaller">Подполей пока нет. Добавьте первое в блоке выше.</p>
        @else
        <div class="table-responsive">
        <table class="table table-hover mb-0 ztr-rubrics-fields-table" id="subfieldsTable">
            <thead>
                <tr>
                    <th class="ztr-rubrics-fields-col-drag"></th>
                    <th class="ztr-rubrics-fields-col-id">#</th>
                    <th class="ztr-rubrics-fields-col-alias">Алиас</th>
                    <th class="ztr-rubrics-fields-col-title">Название</th>
                    <th class="ztr-rubrics-fields-col-type">Тип</th>
                    <th class="ztr-rubrics-fields-col-actions text-center">Действия</th>
                </tr>
            </thead>
            <tbody id="subfieldsSortable">
                @foreach($subfields as $idx => $sf)
                <tr id="srow-{{ $idx }}" data-idx="{{ $idx }}">
                    <td class="align-middle">
                        <span class="field-drag-handle"><i class="bi bi-grip-vertical"></i></span>
                    </td>
                    <td class="text-muted align-middle ztr-rubrics-fields-small">{{ $idx + 1 }}</td>
                    <td class="align-middle">
                        <div class="field-alias-cell">
                            <span class="field-alias-text" data-idx="{{ $idx }}">{{ $sf['alias'] ?? '' }}</span>
                            @if($canEdit ?? false)
                            <button type="button" class="btn btn-sm btn-link p-0 btn-change-alias ztr-rubrics-fields-xs"
                                data-idx="{{ $idx }}"
                                data-alias="{{ $sf['alias'] ?? '' }}"
                                title="Изменить алиас">...</button>
                            @endif
                        </div>
                    </td>
                    <td class="align-middle">
                        <input type="text" class="tbl-input field-title" data-idx="{{ $idx }}"
                            value="{{ $sf['label'] ?? '' }}" @if(!($canEdit ?? false)) readonly @endif>
                    </td>
                    <td class="align-middle">
                        <div class="d-flex align-items-center gap-1">
                            <span class="field-type-link ztr-nowrap" data-idx="{{ $idx }}"
                                id="stype-label-{{ $idx }}">{{ $allTypes[$sf['type'] ?? 'text']['name'] ?? ($sf['type'] ?? '?') }}</span>
                            @if($canEdit ?? false)
                            <select class="form-select form-select-sm field-type-select field-type-select-auto" data-idx="{{ $idx }}"
                                id="stype-select-{{ $idx }}">
                                @foreach($allTypes as $typeKey => $typeInfo)
                                    <option value="{{ $typeKey }}" {{ ($sf['type'] ?? 'text') === $typeKey ? 'selected' : '' }}>
                                        {{ $typeInfo['name'] }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-primary field-type-ok field-type-ok-compact"
                                data-idx="{{ $idx }}"
                                id="stype-ok-{{ $idx }}"
                                style="display:none">OK</button>
                            @endif
                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <div class="d-flex justify-content-center gap-1">
                            @if($canDelete ?? false)
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-subfield"
                                data-idx="{{ $idx }}"
                                data-title="{{ $sf['label'] ?? '' }}"
                                title="Удалить подполе">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endif
                        </div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>

        @if($canEdit ?? false)
        <div class="p-3 border-top">
            <button type="button" class="btn btn-primary btn-sm" id="btnSaveSubfields">
                <i class="bi bi-floppy me-1"></i>Сохранить названия
            </button>
        </div>
        @endif
        @endif
    </div>
</div>

<div class="modal fade" id="modalAlias" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Изменить алиас</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-warning mb-2 ztr-rubrics-fields-small">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    Смена алиаса сломает упоминания <code>[value:старый_алиас]</code> в шаблоне рубрики и потеряет данные документов под этим подполем.
                </p>
                <input type="text" id="newAliasInput" class="form-control" placeholder="latin_alias">
                <div id="aliasError" class="text-danger mt-1 ztr-rubrics-fields-small" style="display:none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-warning btn-sm" id="btnAliasConfirm">Изменить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDeleteSubfield" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Удалить подполе</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 ztr-rubrics-fields-smaller">
                    Удалить подполе <strong id="deleteSubfieldTitle"></strong>?<br>
                    <span class="text-warning ztr-rubrics-fields-small">Данные этого подполя во всех существующих документах будут потеряны.</span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteSubfieldConfirm">Удалить</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
window.ZentraConfig = {
    baseUrl:    '{{ route('admin.rubrics.subfields', [$rubric, $field]) }}',
    canEdit:    {{ ($canEdit ?? false) ? 'true' : 'false' }},
    canDelete:  {{ ($canDelete ?? false) ? 'true' : 'false' }},
};
</script>
<script src="{{ route('admin.asset', 'js/rubrics-subfields.js') }}"></script>
@endpush
