@extends('admin.layout')

@section('title', 'Редактирование запроса - ' . $docsRequest->title)

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/requests-edit.css') }}">
@endpush

@section('content')
<div class="ztr-page-title"><i class="bi bi-inbox me-2"></i>Редактирование запроса</div>

<div class="d-flex gap-2 mb-3">
    <a href="{{ route('admin.requests.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>К списку
    </a>
    @if($canEdit)
    <div class="ms-auto d-flex align-items-center gap-2">
        <span id="saveStatus" style="font-size:.75rem;color:var(--ztr-text-muted)"></span>
        <button type="button" class="btn btn-sm btn-primary" id="btnSave">
            <i class="bi bi-floppy me-1"></i>Сохранить
        </button>
    </div>
    @else
    <div class="ms-auto alert alert-warning py-1 mb-0 small">
        <i class="bi bi-eye me-1"></i>Режим только просмотр
    </div>
    @endif
</div>

<form id="editForm">
<fieldset @disabled(!$canEdit)>
<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tabParams">
                    <i class="bi bi-sliders me-1"></i>Параметры
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tabMainTpl">
                    <i class="bi bi-layout-text-window me-1"></i>Основной шаблон
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tabItemTpl">
                    <i class="bi bi-file-earmark-code me-1"></i>Шаблон элемента
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body tab-content">

        <div class="tab-pane fade show active" id="tabParams">
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Название <span class="text-danger">*</span></label>
                    <input type="text" name="title" class="form-control" value="{{ $docsRequest->title }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Алиас <span class="text-danger">*</span></label>
                    <div class="d-flex align-items-center gap-2">
                        <input type="text" name="alias" class="form-control" value="{{ $docsRequest->alias }}" required>
                        <div class="d-flex align-items-center gap-1">
                            <code class="text-warning" style="font-size:.8rem;white-space:nowrap">{{ $docsRequest->tag() }}</code>
                            <button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-copy-tag"
                                data-tag="{{ $docsRequest->tag() }}" title="Скопировать тег">
                                <i class="bi bi-copy" style="font-size:.8rem"></i>
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-12">
                    <label class="form-label mb-1">Рубрики</label>
                    <span class="form-text ms-2">Пусто = все рубрики</span>
                    @php $selectedIds = $docsRequest->rubric_ids ?? []; @endphp
                    <div id="rubricCheckboxes" class="d-flex flex-wrap gap-2" style="padding:.3rem 0">
                        @foreach($rubrics as $rubric)
                        <label class="rubric-chip" for="rubric_{{ $rubric->id }}">
                            <input class="form-check-input rubric-check" type="checkbox"
                                value="{{ $rubric->id }}" id="rubric_{{ $rubric->id }}"
                                {{ in_array($rubric->id, $selectedIds) ? 'checked' : '' }}>
                            @if($rubric->color)
                            <span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:{{ $rubric->color }}"></span>
                            @endif
                            {{ $rubric->title }}
                        </label>
                        @endforeach
                    </div>
                </div>

                <div class="col-12">
                    <label class="form-label">Описание</label>
                    <input type="text" name="description" class="form-control"
                        value="{{ $docsRequest->description }}" placeholder="Для внутреннего использования">
                </div>

                <div class="col-12"><hr class="my-1" style="border-color:var(--ztr-border)"></div>

                <div class="col-md-4">
                    <label class="form-label">Сортировать по полю рубрики</label>
                    @php $uniqueFields = $fields->unique('alias'); @endphp
                    <select name="sort_field" class="form-select" id="sortFieldSelect" data-current="{{ $docsRequest->sort_field }}">
                        <option value="">— Не задано —</option>
                        @foreach($uniqueFields as $field)
                        <option value="{{ $field->alias }}" {{ $docsRequest->sort_field === $field->alias ? 'selected' : '' }}>
                            {{ $field->title }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Или по системному параметру</label>
                    <select name="sort_system" class="form-select">
                        <option value="">— Не задано —</option>
                        @foreach(['id' => 'ID', 'created_at' => 'Дата создания', 'updated_at' => 'Дата изменения', 'views' => 'Просмотры'] as $val => $label)
                        <option value="{{ $val }}" {{ $docsRequest->sort_system === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Порядок</label>
                    <select name="sort_order" class="form-select">
                        <option value="desc" {{ $docsRequest->sort_order === 'desc' ? 'selected' : '' }}>По убыванию</option>
                        <option value="asc"  {{ $docsRequest->sort_order === 'asc'  ? 'selected' : '' }}>По возрастанию</option>
                    </select>
                </div>

                <div class="col-12"><hr class="my-1" style="border-color:var(--ztr-border)"></div>

                <div class="col-md-3">
                    <label class="form-label">Режим выборки</label>
                    <div class="d-flex flex-column gap-1">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="fetch_mode" id="fetchGlobal"
                                value="global" {{ ($docsRequest->fetch_mode ?? 'global') === 'global' ? 'checked' : '' }}>
                            <label class="form-check-label" for="fetchGlobal" style="font-size:.85rem">Общая выборка</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="fetch_mode" id="fetchDistributed"
                                value="distributed" {{ ($docsRequest->fetch_mode ?? 'global') === 'distributed' ? 'checked' : '' }}>
                            <label class="form-check-label" for="fetchDistributed" style="font-size:.85rem">По рубрикам</label>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label" id="limitLabel">
                        {{ ($docsRequest->fetch_mode ?? 'global') === 'distributed' ? 'Лимит на рубрику' : 'Лимит документов' }}
                    </label>
                    <input type="number" name="limit" class="form-control" min="1"
                        value="{{ $docsRequest->limit }}" placeholder="Все">
                </div>
                <div class="col-md-2">
                    <label class="form-label">На страницу</label>
                    <input type="number" name="per_page" class="form-control" min="1"
                        value="{{ $docsRequest->per_page }}" placeholder="—" id="perPageInput">
                </div>
                <div class="col-md-3 d-flex flex-column justify-content-end pb-1 gap-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="show_pagination" id="chkPagination"
                            value="1" {{ $docsRequest->show_pagination ? 'checked' : '' }}>
                        <label class="form-check-label" for="chkPagination" style="font-size:.875rem">
                            Показывать пагинацию
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="exclude_current" id="chkExclude"
                            value="1" {{ $docsRequest->exclude_current ? 'checked' : '' }}>
                        <label class="form-check-label" for="chkExclude" style="font-size:.875rem">
                            Не показывать текущий документ
                        </label>
                    </div>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Кеш (мин.)</label>
                    <input type="number" name="cache_time" class="form-control" min="0"
                        value="{{ $docsRequest->cache_time }}" placeholder="Без кеша">
                </div>

                <div class="col-12"><hr class="my-1" style="border-color:var(--ztr-border)"></div>

                <div class="col-12">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <label class="form-label mb-0">Условия выборки</label>
                        <span class="badge bg-secondary" id="condCount" style="font-size:.7rem">
                            {{ count($docsRequest->conditions ?? []) }}
                        </span>
                        <button type="button" class="btn btn-sm btn-secondary ms-auto" id="btnConditions">
                            <i class="bi bi-funnel me-1"></i>Настроить условия
                        </button>
                    </div>

                    <div id="condSummary" style="font-size:.8rem;color:var(--ztr-text-muted)">
                        @php $conds = $docsRequest->conditions ?? []; @endphp
                        @if(empty($conds))
                        <span>Условия не заданы - выбираются все документы рубрики</span>
                        @else
                        @foreach($conds as $c)
                        @if($c['active'] ?? true)
                        <span class="badge bg-secondary me-1" style="font-size:.72rem">
                            {{ $c['field'] }} {{ $c['operator'] }} {{ $c['value'] }}
                        </span>
                        @endif
                        @endforeach
                        @endif
                    </div>
                </div>

            </div>
        </div>

        <div class="tab-pane fade" id="tabMainTpl">
            <label class="form-label" style="font-size:.8rem;font-weight:600">
                Шаблон обёртки
                <span class="text-muted fw-normal">— используй <code style="font-size:.75rem">[content]</code> для вывода содержимого</span>
            </label>

            @php
            $mainTagsMap = [
                ['val' => '[content]', 'tip' => 'Место вывода всех элементов запроса'],
                ['val' => '[pages:links]', 'tip' => 'Пагинация - ссылки навигации по страницам'],
                ['val' => '[pages:current]', 'tip' => 'Номер текущей страницы'],
                ['val' => '[pages:total]', 'tip' => 'Всего страниц'],
                ['val' => '[docs:total]', 'tip' => 'Общее кол-во документов в выборке'],
                ['val' => '[docs:page]', 'tip' => 'Кол-во документов на текущей странице'],
                ['val' => '[if_empty]...[/if_empty]', 'tip' => 'Блок отображается, если документов нет'],
                ['val' => '[if_notempty]...[/if_notempty]', 'tip' => 'Блок отображается, если документы есть'],
            ];
            @endphp

            <div class="mb-2">
                <button type="button" class="btn-tags-toggle" data-bs-toggle="collapse" data-bs-target="#mainTplTags">
                    <i class="bi bi-tags me-1" style="font-size:.7rem"></i>Теги
                    <i class="bi bi-chevron-down toggle-icon" style="font-size:.55rem"></i>
                </button>
                <div class="collapse" id="mainTplTags">
                    <div class="tpl-tags mt-1">
                        @foreach($mainTagsMap as $tag)
                        <span class="tpl-tag" data-target="mainTpl" data-val="{{ $tag['val'] }}"
                            data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $tag['tip'] }}">{{ $tag['val'] }}</span>
                        @endforeach
                    </div>
                </div>
            </div>

            <div id="mainTpl" class="ace-req-editor"></div>
        </div>

        <div class="tab-pane fade" id="tabItemTpl">
            <div class="row g-3">
                <div class="col-md-8">
                    <label class="form-label" style="font-size:.8rem;font-weight:600">Шаблон одного элемента</label>
                    @php
                    $itemTagsMap = [
                        ['val' => '[doc:id]', 'tip' => 'ID документа в базе данных'],
                        ['val' => '[doc:num]', 'tip' => 'Порядковый номер элемента в списке'],
                        ['val' => '[doc:title]', 'tip' => 'Заголовок документа'],
                        ['val' => '[doc:url]', 'tip' => 'Ссылка на документ'],
                        ['val' => '[doc:date]', 'tip' => 'Дата создания документа'],
                        ['val' => '[doc:time]', 'tip' => 'Время создания документа'],
                        ['val' => '[doc:author]', 'tip' => 'Автор документа'],
                        ['val' => '[doc:views]', 'tip' => 'Количество просмотров'],
                        ['val' => '[doc:rubric]', 'tip' => 'Название рубрики документа'],
                        ['val' => '[doc:rubric_alias]', 'tip' => 'Алиас рубрики'],
                        ['val' => '[doc:rubric_url]', 'tip' => 'Ссылка на рубрику'],
                        ['val' => '[doc:rubric_color]', 'tip' => 'Цвет рубрики (HEX)'],
                        ['val' => '[if_first]...[/if_first]', 'tip' => 'Блок только для первого элемента'],
                        ['val' => '[if_last]...[/if_last]', 'tip' => 'Блок только для последнего элемента'],
                        ['val' => '[if_not_first]...[/if_not_first]', 'tip' => 'Скрыть для первого элемента'],
                        ['val' => '[if_not_last]...[/if_not_last]', 'tip' => 'Скрыть для последнего элемента'],
                        ['val' => '[if_every:N]...[/if_every:N]', 'tip' => 'Блок для каждого N-го элемента'],
                        ['val' => '[if_notempty:field:alias]...[/if_notempty:field:alias]', 'tip' => 'Блок если поле непустое. Замени alias на псевдоним поля'],
                        ['val' => '[if_empty:field:alias]...[/if_empty:field:alias]', 'tip' => 'Блок если поле пустое. Замени alias на псевдоним поля'],
                        ['val' => '[if_field:alias=value]...[/if_field:alias=value]', 'tip' => 'Блок если поле равно значению. Замени alias и value на нужные'],
                        ['val' => '[if_not_field:alias=value]...[/if_not_field:alias=value]', 'tip' => 'Блок если поле НЕ равно значению. Замени alias и value на нужные'],
                    ];
                    @endphp
                    <div class="mb-2">
                        <button type="button" class="btn-tags-toggle" data-bs-toggle="collapse" data-bs-target="#itemTplTags">
                            <i class="bi bi-tags me-1" style="font-size:.7rem"></i>Теги
                            <i class="bi bi-chevron-down toggle-icon" style="font-size:.55rem"></i>
                        </button>
                        <div class="collapse" id="itemTplTags">
                            <div class="tpl-tags mt-1">
                                @foreach($itemTagsMap as $tag)
                                <span class="tpl-tag" data-target="itemTpl" data-val="{{ $tag['val'] }}"
                                    data-bs-toggle="tooltip" data-bs-placement="top" title="{{ $tag['tip'] }}">{{ $tag['val'] }}</span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div id="itemTpl" class="ace-req-editor"></div>
                </div>
                <div class="col-md-4">
                    <label class="form-label" style="font-size:.8rem;font-weight:600">Поля рубрики</label>
                    <table class="table table-sm" style="font-size:.75rem">
                        <thead><tr><th>Название</th><th>Тег</th></tr></thead>
                        <tbody id="fieldsTableBody">
                            @if($uniqueFields->isNotEmpty())
                                @foreach($uniqueFields as $field)
                                <tr>
                                    <td>{{ $field->title }}</td>
                                    <td>
                                        <code class="text-warning tpl-tag-field" data-target="itemTpl"
                                            data-val="[field:{{ $field->alias }}]"
                                            style="font-size:.78rem;cursor:pointer">[field:{{ $field->alias }}]</code>
                                    </td>
                                </tr>
                                @endforeach
                            @else
                            <tr><td colspan="2" class="text-muted">Выберите рубрику на вкладке «Параметры»</td></tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
</fieldset>

@if($canEdit)
<div class="d-flex justify-content-end mt-3">
    <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('btnSave').click()">
        <i class="bi bi-floppy me-1"></i>Сохранить
    </button>
</div>
@endif
</form>

<div class="modal fade" id="conditionsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Условия выборки</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div class="card mb-3">
                    <div class="card-body">
                        <h6 class="card-title mb-3" style="font-size:.82rem">Добавить условие</h6>
                        <div class="row g-2 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label mb-1" style="font-size:.78rem">Поле</label>
                                <select class="form-select form-select-sm" id="condField">
                                    <option value="">— Выберите поле —</option>
                                    @foreach($uniqueFields as $field)
                                    <option value="{{ $field->alias }}">{{ $field->title }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1" style="font-size:.78rem">Оператор</label>
                                <select class="form-select form-select-sm" id="condOperator">
                                    <option value="=">=  (равно)</option>
                                    <option value="!=">!= (не равно)</option>
                                    <option value="like">содержит</option>
                                    <option value="not like">не содержит</option>
                                    <option value=">">&gt; (больше)</option>
                                    <option value=">=">&gt;= (больше или равно)</option>
                                    <option value="<">&lt; (меньше)</option>
                                    <option value="<=">&lt;= (меньше или равно)</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1" style="font-size:.78rem">Значение</label>
                                <input type="text" class="form-control form-control-sm" id="condValue" placeholder="Значение">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1" style="font-size:.78rem">Логика</label>
                                <select class="form-select form-select-sm" id="condLogic">
                                    <option value="AND">И (AND)</option>
                                    <option value="OR">ИЛИ (OR)</option>
                                </select>
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-primary btn-sm w-100" id="btnAddCond">
                                    <i class="bi bi-plus-lg"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="condList">
                    <p class="text-muted text-center py-2 mb-0" id="condEmpty" style="font-size:.82rem">
                        Условий пока нет - будут выбраны все документы рубрики
                    </p>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Закрыть</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnSaveConds">
                    <i class="bi bi-check-lg me-1"></i>Применить
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.32.8/src-min-noconflict/ace.js"></script>
<script>
window.ZentraConfig = {
    saveUrl: '{{ route('admin.requests.update', $docsRequest) }}',
    fieldsUrl: '{{ route('admin.documents.rubricFields') }}',
    conditions: @json($docsRequest->conditions ?? []),
    canEdit: {{ $canEdit ? 'true' : 'false' }},
    templates: {
        mainTpl: @json($docsRequest->template_main ?? ''),
        itemTpl: @json($docsRequest->template_item ?? ''),
    },
};
</script>
<script src="{{ route('admin.asset', 'js/requests-edit.js') }}"></script>
@endpush
