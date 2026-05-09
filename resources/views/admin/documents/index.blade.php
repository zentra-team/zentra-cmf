@extends('admin.layout')

@section('title', 'Документы')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/documents-index.css') }}">
@endpush

@section('content')
<div class="ztr-page-title"><i class="bi bi-file-text me-2"></i>Документы</div>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tabList">
                    <i class="bi bi-file-earmark-text me-1"></i>Список документов
                </a>
            </li>
            @if($rubricsForCreate->isNotEmpty())
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tabCreate" id="tabCreateLink">
                    <i class="bi bi-plus-circle me-1"></i>Создать документ
                </a>
            </li>
            @endif
        </ul>
    </div>
    <div class="card-body tab-content p-0">

        <div class="tab-pane fade show active" id="tabList">

            <div class="ztr-doc-toolbar d-flex flex-wrap gap-2 align-items-center p-3 border-bottom">

                @if($rubricsForCreate->isNotEmpty())
                <div class="d-flex align-items-center gap-2">
                    <select id="addRubricSelect" class="form-select form-select-sm ztr-doc-select-md">
                        <option value="">— Рубрика —</option>
                        @foreach($rubricsForCreate as $r)
                        <option value="{{ $r->id }}">{{ $r->title }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="btn btn-sm btn-primary" id="btnAddDoc">
                        <i class="bi bi-plus-circle me-1"></i>Добавить
                    </button>
                </div>

                <div class="vr d-none d-md-block ztr-doc-vr-dim"></div>
                @endif

                <form method="GET" action="{{ route('admin.documents.index') }}" class="d-flex align-items-center gap-2">
                    <select name="rubric_id" class="form-select form-select-sm ztr-doc-select-md">
                        <option value="">— Все рубрики —</option>
                        @foreach($rubrics as $r)
                        <option value="{{ $r->id }}" {{ request('rubric_id') == $r->id ? 'selected' : '' }}>{{ $r->title }}</option>
                        @endforeach
                    </select>
                    @foreach(request()->except('rubric_id') as $k => $v)
                    <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                    @endforeach
                    <button type="submit" class="btn btn-sm btn-secondary">
                        <i class="bi bi-filter me-1"></i>Отобрать
                    </button>
                    @if(request()->hasAny(['rubric_id','search','doc_id','status','date_from','date_to']))
                    <a href="{{ route('admin.documents.index') }}" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-x-lg"></i>
                    </a>
                    @endif
                </form>

                <div class="ms-auto">
                    <button class="btn btn-sm btn-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#searchPanel">
                        <i class="bi bi-search me-1"></i>Поиск
                    </button>
                </div>
            </div>

            <div class="collapse {{ request()->hasAny(['search','doc_id','status','date_from','date_to','field_alias']) ? 'show' : '' }}" id="searchPanel">
                <form method="GET" action="{{ route('admin.documents.index') }}" class="ztr-doc-search-form p-3 border-bottom">
                    <div class="row g-2">
                        <div class="col-md-2">
                            <label class="form-label mb-1 ztr-doc-label-xs">Начало публикации</label>
                            <input type="date" name="date_from" class="form-control form-control-sm" value="{{ request('date_from') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1 ztr-doc-label-xs">Конец публикации</label>
                            <input type="date" name="date_to" class="form-control form-control-sm" value="{{ request('date_to') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label mb-1 ztr-doc-label-xs">Название документа</label>
                            <input type="text" name="search" class="form-control form-control-sm" value="{{ request('search') }}" placeholder="Поиск по названию">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1 ztr-doc-label-xs">В рубрике</label>
                            <select name="rubric_id" class="form-select form-select-sm">
                                <option value="">— Все —</option>
                                @foreach($rubrics as $r)
                                <option value="{{ $r->id }}" {{ request('rubric_id') == $r->id ? 'selected' : '' }}>{{ $r->title }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-1">
                            <label class="form-label mb-1 ztr-doc-label-xs">ID</label>
                            <input type="number" name="doc_id" class="form-control form-control-sm" value="{{ request('doc_id') }}" min="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1 ztr-doc-label-xs">Статус</label>
                            <select name="status" class="form-select form-select-sm">
                                <option value="">— Все —</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Опубликован</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Черновик</option>
                                <option value="2" {{ request('status') === '2' ? 'selected' : '' }}>На модерации</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label mb-1 ztr-doc-label-xs">На странице</label>
                            <select name="per_page" class="form-select form-select-sm">
                                @foreach([10,25,50,100] as $pp)
                                <option value="{{ $pp }}" {{ request('per_page', 25) == $pp ? 'selected' : '' }}>{{ $pp }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label mb-1 ztr-doc-label-xs">Параметр документа</label>
                            <div class="d-flex gap-2 flex-wrap align-items-center">
                                <select name="field_alias" id="filterFieldAlias" class="form-select form-select-sm ztr-doc-select-md">
                                    <option value="">— Поле —</option>
                                    @foreach($rubricFields as $rf)
                                    <option value="{{ $rf->alias }}" {{ request('field_alias') === $rf->alias ? 'selected' : '' }}>
                                        {{ $rf->title }} ({{ $rf->alias }})
                                    </option>
                                    @endforeach
                                </select>
                                <select name="field_operator" id="filterFieldOp" class="form-select form-select-sm ztr-doc-select-sm">
                                    <option value="="   {{ request('field_operator','=') === '='        ? 'selected' : '' }}>=  (равно)</option>
                                    <option value="!="  {{ request('field_operator') === '!='           ? 'selected' : '' }}>!= (не равно)</option>
                                    <option value="like"{{ request('field_operator') === 'like'         ? 'selected' : '' }}>содержит</option>
                                    <option value="not_like" {{ request('field_operator') === 'not_like'? 'selected' : '' }}>не содержит</option>
                                    <option value=">"   {{ request('field_operator') === '>'            ? 'selected' : '' }}>&gt; (больше)</option>
                                    <option value=">="  {{ request('field_operator') === '>='           ? 'selected' : '' }}>&gt;= (≥)</option>
                                    <option value="<"   {{ request('field_operator') === '<'            ? 'selected' : '' }}>&lt; (меньше)</option>
                                    <option value="<="  {{ request('field_operator') === '<='           ? 'selected' : '' }}>&lt;= (≤)</option>
                                </select>
                                <input type="text" name="field_value" id="filterFieldValue"
                                    class="form-control form-control-sm ztr-doc-select-md"
                                    placeholder="Значение" value="{{ request('field_value') }}">
                                @if(!$rubricFields->count() && !request('field_alias'))
                                <span class="text-muted ztr-doc-label-xs">
                                    <i class="bi bi-info-circle me-1"></i>Выберите рубрику для фильтра по полю
                                </span>
                                @endif
                            </div>
                        </div>

                        @foreach(request()->except(['search','doc_id','status','date_from','date_to','rubric_id','per_page','page','field_alias','field_operator','field_value']) as $k => $v)
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}">
                        @endforeach
                        <div class="col-12">
                            <button type="submit" class="btn btn-sm btn-primary">
                                <i class="bi bi-search me-1"></i>Поиск
                            </button>
                            <a href="{{ route('admin.documents.index') }}" class="btn btn-sm btn-secondary ms-1">Сбросить</a>
                        </div>
                    </div>
                </form>
            </div>

            @if($documents->isEmpty())
            <p class="text-muted text-center py-4 mb-0">Документов не найдено.</p>
            @else

            @php
            $sortCols = [
                'id'            => 'ID',
                'title'         => 'Название',
                'published_at'  => 'Опубликован',
                'updated_at'    => 'Отредактирован',
            ];
            $curSort = request('sort', 'id');
            $curDir  = request('dir', 'desc');
            @endphp
            <div class="ztr-doc-sort-bar px-3 pt-2 pb-2 d-flex align-items-center gap-1 flex-wrap">
                <span class="ztr-doc-sort-label text-muted me-2">Сортировка:</span>
                @foreach($sortCols as $col => $label)
                @php
                    $newDir = ($curSort === $col && $curDir === 'asc') ? 'desc' : 'asc';
                    $url    = request()->url() . '?' . http_build_query(array_merge(request()->query(), ['sort' => $col, 'dir' => $newDir]));
                    $active = $curSort === $col;
                @endphp
                <a href="{{ $url }}" class="ztr-doc-sort-link {{ $active ? 'is-active' : '' }}">
                    {{ $label }}
                    @if($active)
                    <i class="bi bi-caret-{{ $curDir === 'asc' ? 'up' : 'down' }}-fill ztr-doc-sort-arrow"></i>
                    @endif
                </a>
                @endforeach
            </div>

            @php $canBulk = $canEditGlobal || $canDeleteGlobal; @endphp
            <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        @if($canBulk)
                        <th class="ztr-doc-col-check">
                            <input type="checkbox" class="form-check-input" id="checkAll">
                        </th>
                        @endif
                        <th class="ztr-doc-col-id">ID</th>
                        <th>Название</th>
                        <th class="ztr-doc-col-rubric">Рубрика / Автор</th>
                        <th class="ztr-doc-col-status">Статус</th>
                        <th class="ztr-doc-col-dates">Опубликован / Изменён</th>
                        <th class="ztr-doc-col-actions">Действия</th>
                    </tr>
                </thead>
                <tbody id="docsTableBody">
                    @php $urlSuffix = \App\Models\Setting::getValue('url_suffix', ''); @endphp
                    @foreach($documents as $doc)
                    <tr data-id="{{ $doc->id }}">
                        @if($canBulk)
                        <td>
                            @if($doc->perm_edit || $doc->perm_delete)
                            <input type="checkbox" class="form-check-input doc-check" value="{{ $doc->id }}">
                            @endif
                        </td>
                        @endif
                        <td class="text-muted">{{ $doc->id }}</td>
                        <td>
                            <a href="{{ route('admin.documents.edit', $doc) }}" class="d-block">
                                {{ $doc->title }}
                            </a>
                            @if($doc->views)
                            <span class="ztr-doc-views-tag"><i class="bi bi-eye ztr-doc-views-icon"></i> {{ $doc->views }}</span>
                            @endif
                        </td>
                        <td class="ztr-doc-rubric-cell">
                            <div>{{ $doc->rubric?->title ?? '—' }}</div>
                            <div class="ztr-doc-rubric-author">{{ $doc->author?->name ?? '—' }}</div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-{{ $doc->statusClass() }} ztr-doc-status-badge">
                                {{ $doc->statusLabel() }}
                            </span>
                        </td>
                        <td class="ztr-doc-dates-cell">
                            <div>{{ $doc->published_at?->format($dateFormat.' '.$timeFormat) ?? '—' }}</div>
                            <div>{{ $doc->updated_at->format($dateFormat.' '.$timeFormat) }}</div>
                        </td>
                        <td class="text-nowrap">
                            @php
                                if ($doc->alias === 'index') {
                                    $docUrl = '/';
                                } elseif ($doc->alias === null || $doc->alias === '') {
                                    $docUrl = $doc->rubric?->alias ? '/' . $doc->rubric->alias . $urlSuffix : '/';
                                } else {
                                    $docUrl = '/' . ($doc->rubric?->alias ? $doc->rubric->alias . '/' : '') . $doc->alias . $urlSuffix;
                                }
                            @endphp
                            @if($doc->status === \App\Models\Document::STATUS_ACTIVE)
                            <a href="{{ $docUrl }}"
                               class="btn btn-sm btn-success" title="Просмотр на сайте" target="_blank">
                                <i class="bi bi-box-arrow-up-right"></i>
                            </a>
                            @else
                            <a href="{{ route('admin.documents.preview', $doc) }}"
                               class="btn btn-sm btn-{{ $doc->statusClass() }}" title="Превью (документ не опубликован)" target="_blank">
                                <i class="bi bi-eye"></i>
                            </a>
                            @endif
                            <a href="{{ route('admin.documents.edit', $doc) }}"
                               class="btn btn-sm btn-outline-success" title="{{ $doc->perm_edit ? 'Редактировать' : 'Просмотр (только чтение)' }}">
                                <i class="bi bi-{{ $doc->perm_edit ? 'pencil' : 'eye' }}"></i>
                            </a>
                            @if($doc->perm_copy)
                            <button type="button" class="btn btn-sm btn-outline-success btn-doc-copy"
                                data-id="{{ $doc->id }}" title="Копировать">
                                <i class="bi bi-copy"></i>
                            </button>
                            @endif
                            @if($doc->perm_delete)
                            <button type="button" class="btn btn-sm btn-outline-danger btn-doc-delete"
                                data-id="{{ $doc->id }}" data-title="{{ $doc->title }}" title="Удалить">
                                <i class="bi bi-trash"></i>
                            </button>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            <div class="ztr-doc-bulk-bar d-flex align-items-center gap-2 p-3 border-top">
                @if($canBulk)
                <select id="bulkAction" class="form-select form-select-sm ztr-doc-select-lg">
                    <option value="">— Массовое действие —</option>
                    @if($canEditGlobal)
                    <option value="activate">Опубликовать выбранные</option>
                    <option value="draft">Перевести в черновики</option>
                    @endif
                    @if($canDeleteGlobal)
                    <option value="delete">Удалить выбранные</option>
                    @endif
                </select>
                <button type="button" class="btn btn-sm btn-secondary" id="btnBulkApply">Применить</button>
                <span id="bulkCount" class="text-muted ms-1 ztr-doc-hint-sm"></span>
                @endif
                <div class="ms-auto">
                    {{ $documents->links() }}
                </div>
            </div>

            @endif
        </div>

        @if($rubricsForCreate->isNotEmpty())
        <div class="tab-pane fade p-3" id="tabCreate">
            <form method="POST" action="{{ route('admin.documents.store') }}" class="ztr-doc-create-form" id="createForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-7">
                        <label class="form-label">Название <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="createTitle"
                            class="form-control @error('title') is-invalid @enderror"
                            value="{{ old('title') }}" required autofocus>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Алиас (URL)
                            <i class="bi bi-info-circle text-muted ms-1 ztr-doc-info-icon"
                                data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-html="true"
                                data-bs-title="Алиас документа"
                                data-bs-content="Только латинские буквы, цифры и дефисы.<br><br>&#x2728; <strong>Особое значение:</strong><br>Алиас <code>index</code> - главная страница сайта.<br>Пустой алиас - главная страница рубрики.<br><br>Все остальные алиасы формируют URL вида <code>/префикс-рубрики/алиас</code>"></i>
                        </label>
                        <input type="text" name="alias" id="createAlias"
                            class="form-control @error('alias') is-invalid @enderror"
                            value="{{ old('alias') }}">
                        @error('alias')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Рубрика <span class="text-danger">*</span></label>
                        <select name="rubric_id" id="createRubric"
                            class="form-select @error('rubric_id') is-invalid @enderror" required>
                            <option value="">— Выберите рубрику —</option>
                            @foreach($rubricsForCreate as $r)
                            <option value="{{ $r->id }}" {{ old('rubric_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->title }}
                            </option>
                            @endforeach
                        </select>
                        @error('rubric_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Статус</label>
                        <select name="status" id="createStatus" class="form-select">
                            <option value="0" {{ old('status') == '0' ? 'selected' : '' }}>Черновик</option>
                            <option value="1" data-publish-option {{ old('status') == '1' ? 'selected' : '' }}>Опубликован</option>
                            <option value="2" {{ old('status') == '2' ? 'selected' : '' }}>На модерации</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm" id="btnCreateSubmit">
                            <i class="bi bi-plus-circle me-1"></i>Создать документ
                        </button>
                        <span class="text-muted ms-2 ztr-doc-hint-sm">После создания откроется форма заполнения полей</span>
                    </div>
                </div>
            </form>
        </div>
        @endif

    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Удаление документа</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Удалить документ <strong id="deleteTitle"></strong>?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteConfirm">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="bulkDeleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Массовое удаление</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Удалить выбранные документы (<strong id="bulkDeleteCount"></strong> шт.)?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnBulkDeleteConfirm">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.ZentraConfig = {
    aliasManualInit: {{ old('alias') ? 'true' : 'false' }},
    rubricFieldsUrl: '{{ route('admin.documents.rubricFields') }}',
    urlSuffix: '{{ \App\Models\Setting::getValue('url_suffix', '') }}',
    hasErrors: {{ $errors->any() ? 'true' : 'false' }},
    errorsText: '{{ implode(' ', $errors->all()) }}',
    rubricsPublishMap: @json($rubricsPublishMap)
};
</script>
<script src="{{ route('admin.asset', 'js/documents-index.js') }}"></script>
@endpush
