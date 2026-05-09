@extends('admin.layout')

@section('title', 'Поля рубрики - ' . $rubric->title)

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/rubrics-fields.css') }}">

@endpush

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('admin.rubrics.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Рубрики
    </a>
    <div class="ztr-page-title mb-0"><i class="bi bi-folder2 me-2"></i>{{ $rubric->title }}</div>
    <div class="ms-auto d-flex gap-2">
        <a href="{{ route('admin.rubrics.template', $rubric) }}" class="btn btn-sm btn-success">
            <i class="bi bi-file-code me-1"></i>Шаблон
        </a>
        @if($canPermissions ?? false)
        <a href="{{ route('admin.rubrics.permissions', $rubric) }}" class="btn btn-sm btn-success">
            <i class="bi bi-shield-check me-1"></i>Права
        </a>
        @endif
    </div>
</div>

<div class="d-flex align-items-center gap-2 mb-3">
    <label for="rubricDescription" class="form-label mb-0 text-muted ztr-rubrics-fields-small flex-shrink-0">Описание:</label>
    <input type="text" id="rubricDescription" class="form-control form-control-sm"
        placeholder="Внутренняя заметка (не отображается на сайте)"
        value="{{ $rubric->description }}"
        @if(!($canEdit ?? false)) readonly @endif>
    @if($canEdit ?? false)
    <button type="button" class="btn btn-primary btn-sm flex-shrink-0" id="btnSaveDescription">
        <i class="bi bi-floppy me-1"></i>Сохранить
    </button>
    @endif
</div>

@if(!($canEdit ?? false))
<div class="alert alert-danger py-2 mb-3 ztr-readonly-banner">
    <i class="bi bi-eye me-1"></i>Режим только просмотр - у вас нет прав на редактирование рубрик
</div>
@endif

@if($canEdit ?? false)
<div class="card mb-3">
    <div class="card-header">Добавить поле</div>
    <div class="card-body">
        <div class="d-flex align-items-center gap-4 ztr-rubrics-fields-add-row">
            <input type="text" id="newFieldTitle" class="form-control flex-grow-1 ztr-rubrics-fields-add-title" placeholder="Название поля">
            <select id="newFieldType" class="form-select flex-shrink-0 ztr-rubrics-fields-type-select">
                @foreach($fieldTypes as $groupKey => $group)
                    <optgroup label="{{ $group['label'] }}">
                        @foreach($group['types'] as $typeKey => $typeInfo)
                            <option value="{{ $typeKey }}">{{ $typeInfo['name'] }}</option>
                        @endforeach
                    </optgroup>
                @endforeach
            </select>
            <button type="button" class="btn btn-sm btn-primary flex-shrink-0" id="btnAddField">
                <i class="bi bi-plus-lg me-1"></i>Добавить
            </button>
        </div>
        <div id="addFieldError" class="text-danger mt-2 ztr-rubrics-fields-small" style="display:none"></div>
    </div>
</div>
@endif

<div class="card mb-3">
    <div class="card-header">Поля рубрики</div>
    <div class="card-body p-0">
        @if($fields->isEmpty())
            <p class="text-muted p-3 mb-0 ztr-rubrics-fields-smaller">Полей пока нет. Добавьте первое в блоке ниже.</p>
        @else
        <div class="table-responsive">
        <table class="table table-hover mb-0 ztr-rubrics-fields-table" id="fieldsTable">
            <thead>
                <tr>
                    <th class="ztr-rubrics-fields-col-drag"></th>
                    <th class="ztr-rubrics-fields-col-id">#</th>
                    <th class="ztr-rubrics-fields-col-alias">Алиас</th>
                    <th class="ztr-rubrics-fields-col-title">Название</th>
                    <th class="ztr-rubrics-fields-col-type">Тип поля</th>
                    <th class="ztr-rubrics-fields-col-inapi text-center" title="Поле попадает в JSON API"><i class="bi bi-braces"></i> API</th>
                    <th class="ztr-rubrics-fields-col-actions text-center">Действия</th>
                </tr>
            </thead>
            <tbody id="fieldsSortable">
                @foreach($fields as $field)
                <tr id="frow-{{ $field->id }}" data-id="{{ $field->id }}">
                    <td class="align-middle">
                        <span class="field-drag-handle"><i class="bi bi-grip-vertical"></i></span>
                    </td>
                    <td class="text-muted align-middle ztr-rubrics-fields-small">{{ $field->position }}</td>
                    <td class="align-middle">
                        <div class="field-alias-cell">
                            <span class="field-alias-text" data-id="{{ $field->id }}">{{ $field->alias }}</span>
                            <button type="button" class="btn btn-sm btn-link p-0 btn-change-alias ztr-rubrics-fields-xs"
                                data-id="{{ $field->id }}"
                                data-alias="{{ $field->alias }}"
                                title="Изменить алиас">...</button>
                        </div>
                    </td>
                    <td class="align-middle">
                        <input type="text" class="tbl-input field-title" data-id="{{ $field->id }}"
                            value="{{ $field->title }}" @if(!($canEdit ?? false)) readonly @endif>
                    </td>
                    <td class="align-middle ztr-rubrics-fields-col-type">
                        <div class="d-flex align-items-center gap-1 overflow-hidden">
                            <span class="field-type-link" data-id="{{ $field->id }}"
                                id="type-label-{{ $field->id }}">{{ $field->typeName() }}</span>
                            <select class="form-select form-select-sm field-type-select field-type-select-auto" data-id="{{ $field->id }}"
                                id="type-select-{{ $field->id }}">
                                @foreach($fieldTypes as $groupKey => $group)
                                    <optgroup label="{{ $group['label'] }}">
                                        @foreach($group['types'] as $typeKey => $typeInfo)
                                            <option value="{{ $typeKey }}" {{ $field->type === $typeKey ? 'selected' : '' }}>
                                                {{ $typeInfo['name'] }}
                                            </option>
                                        @endforeach
                                    </optgroup>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-primary field-type-ok field-type-ok-compact"
                                data-id="{{ $field->id }}"
                                id="type-ok-{{ $field->id }}"
                                style="display:none">OK</button>
                        </div>
                    </td>
                    <td class="text-center align-middle">
                        <input type="checkbox" class="form-check-input field-in-api" data-id="{{ $field->id }}"
                               {{ $field->in_api ? 'checked' : '' }}
                               @if(!($canEdit ?? false)) disabled @endif
                               title="Включить значение поля в JSON API">
                    </td>
                    <td class="text-center align-middle">
                        <div class="d-flex justify-content-center gap-1">
                            @if($canEdit ?? false)
                            <button type="button" class="btn btn-sm btn-outline-success btn-field-config"
                                data-id="{{ $field->id }}"
                                data-type="{{ $field->type }}"
                                data-default="{{ e($field->default_value ?? '') }}"
                                data-desc="{{ e($field->description ?? '') }}"
                                data-options="{{ e(implode("\n", $field->config['options'] ?? [])) }}"
                                data-cfg-min="{{ e($field->config['min'] ?? '') }}"
                                data-cfg-max="{{ e($field->config['max'] ?? '') }}"
                                data-cfg-step="{{ e($field->config['step'] ?? '') }}"
                                data-cfg-suffix="{{ e($field->config['suffix'] ?? '') }}"
                                data-cfg-rubric-ids="{{ e(implode(',', (array) ($field->config['rubric_ids'] ?? []))) }}"
                                data-cfg-rubric-id="{{ e($field->config['rubric_id'] ?? '') }}"
                                data-cfg-max-items="{{ e($field->config['max_items'] ?? '') }}"
                                data-cfg-maxlength="{{ e($field->config['maxlength'] ?? '') }}"
                                data-cfg-rows="{{ e($field->config['rows'] ?? '') }}"
                                data-cfg-accepted-extensions="{{ e($field->config['accepted_extensions'] ?? '') }}"
                                data-cfg-max-size-kb="{{ e($field->config['max_size_kb'] ?? '') }}"
                                data-cfg-display-format="{{ e($field->config['display_format'] ?? '') }}"
                                data-cfg-mode="{{ e($field->config['mode'] ?? '') }}"
                                data-cfg-default-currency="{{ e($field->config['default_currency'] ?? '') }}"
                                data-cfg-category-filter="{{ e($field->config['category_filter'] ?? '') }}"
                                data-cfg-format="{{ e($field->config['format'] ?? '') }}"
                                title="Параметры поля">
                                <i class="bi bi-gear"></i>
                            </button>
                            @endif
                            @if($canDelete ?? false)
                            <button type="button" class="btn btn-sm btn-outline-danger btn-delete-field"
                                data-id="{{ $field->id }}"
                                data-title="{{ $field->title }}"
                                title="Удалить поле">
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
            <button type="button" class="btn btn-primary btn-sm" id="btnSaveFields">
                <i class="bi bi-floppy me-1"></i>Сохранить изменения
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
                    Смена алиаса сломает все упоминания <code>[field:старый_алиас]</code> в шаблоне рубрики.
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

<div class="modal fade" id="modalFieldConfig" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered ztr-rubrics-fields-modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-gear me-2"></i>Параметры поля</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">

                <div id="configOptionsBlock" class="mb-3" style="display:none">
                    <label class="form-label">Варианты списка</label>
                    <textarea id="configOptions" class="form-control font-monospace ztr-rubrics-fields-options-textarea" rows="6"
                        placeholder="Один вариант на каждой строке:&#10;Вариант 1&#10;Вариант 2&#10;Вариант 3"></textarea>
                    <div class="form-text">По одному варианту на строке.</div>
                </div>

                <div id="configSliderBlock" class="mb-3" style="display:none">
                    <label class="form-label">Параметры ползунка</label>
                    <div class="row g-2">
                        <div class="col-3">
                            <input type="number" id="configSliderMin" class="form-control form-control-sm" placeholder="Мин." step="any">
                            <div class="form-text">Минимум</div>
                        </div>
                        <div class="col-3">
                            <input type="number" id="configSliderMax" class="form-control form-control-sm" placeholder="Макс." step="any">
                            <div class="form-text">Максимум</div>
                        </div>
                        <div class="col-3">
                            <input type="number" id="configSliderStep" class="form-control form-control-sm" placeholder="Шаг" step="any" min="0">
                            <div class="form-text">Шаг</div>
                        </div>
                        <div class="col-3">
                            <input type="text" id="configSliderSuffix" class="form-control form-control-sm" placeholder="%">
                            <div class="form-text">Единица (%, °C…)</div>
                        </div>
                    </div>
                </div>

                <div id="configRelationBlock" class="mb-3" style="display:none">
                    <label class="form-label">Фильтр по рубрикам (необязательно)</label>
                    <select id="configRelationRubrics" class="form-select" multiple size="6">
                        @foreach($allRubrics ?? [] as $r)
                            <option value="{{ $r->id }}">{{ $r->title }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Если ничего не выбрано - поиск идёт по всем рубрикам. Зажмите Ctrl/⌘ для выбора нескольких.</div>

                    <label class="form-label mt-3">Лимит выбранных (0 = без лимита)</label>
                    <input type="number" id="configRelationMaxItems" class="form-control form-control-sm" min="0" step="1" placeholder="0">
                </div>

                <div id="configRepeaterBlock" class="mb-3" style="display:none">
                    <div class="alert alert-info py-2 mb-2 small">
                        <i class="bi bi-info-circle me-1"></i>
                        Подполя репитера настраиваются на отдельной странице.
                    </div>
                    <a href="#" id="configRepeaterSubfieldsLink" class="btn btn-success btn-sm">
                        <i class="bi bi-list-ul me-1"></i>Настроить подполя →
                    </a>

                    <label class="form-label mt-3">Лимит групп (0 = без лимита)</label>
                    <input type="number" id="configRepeaterMaxItems" class="form-control form-control-sm" min="0" step="1" placeholder="0">
                </div>

                
                <div id="configMaxlengthBlock" class="mb-3" style="display:none">
                    <label class="form-label">Максимальная длина (символов)</label>
                    <input type="number" id="configMaxlength" class="form-control form-control-sm" min="0" step="1" placeholder="0 = без ограничения">
                    <div class="form-text">Браузер не даст ввести больше этого числа символов.</div>
                </div>

                
                <div id="configRowsBlock" class="mb-3" style="display:none">
                    <label class="form-label">Высота поля (строк)</label>
                    <input type="number" id="configRows" class="form-control form-control-sm" min="1" max="40" step="1" placeholder="5">
                    <div class="form-text">По умолчанию 5. Влияет только на видимую высоту, не на лимит ввода.</div>
                </div>

                
                <div id="configNumberBlock" class="mb-3" style="display:none">
                    <label class="form-label">Параметры числа</label>
                    <div class="row g-2">
                        <div class="col-4">
                            <input type="number" id="configNumberMin" class="form-control form-control-sm" placeholder="Мин." step="any">
                            <div class="form-text">Минимум</div>
                        </div>
                        <div class="col-4">
                            <input type="number" id="configNumberMax" class="form-control form-control-sm" placeholder="Макс." step="any">
                            <div class="form-text">Максимум</div>
                        </div>
                        <div class="col-4">
                            <input type="text" id="configNumberStep" class="form-control form-control-sm" placeholder="any">
                            <div class="form-text">Шаг (any / 1 / 0.01)</div>
                        </div>
                    </div>
                </div>

                
                <div id="configMaxItemsBlock" class="mb-3" style="display:none">
                    <label class="form-label" id="configMaxItemsLabel">Максимальное количество элементов</label>
                    <input type="number" id="configMaxItems" class="form-control form-control-sm" min="0" step="1" placeholder="0 = без лимита">
                </div>

                
                <div id="configFileBlock" class="mb-3" style="display:none">
                    <label class="form-label">Допустимые расширения</label>
                    <input type="text" id="configFileExtensions" class="form-control form-control-sm" placeholder="pdf, docx, zip">
                    <div class="form-text mb-2">Через запятую, без точек. Пусто - любое расширение.</div>

                    <label class="form-label">Максимальный размер (КБ)</label>
                    <input type="number" id="configFileMaxSize" class="form-control form-control-sm" min="0" step="1" placeholder="0 = без лимита">
                    <div class="form-text">Клиентская проверка перед загрузкой. Серверный лимит задаётся в php.ini.</div>
                </div>

                
                <div id="configImageBlock" class="mb-3" style="display:none">
                    <label class="form-label">Максимальный размер изображения (КБ)</label>
                    <input type="number" id="configImageMaxSize" class="form-control form-control-sm" min="0" step="1" placeholder="0 = без лимита">
                    <div class="form-text">Клиентская проверка перед загрузкой.</div>
                </div>

                
                <div id="configGalleryBlock" class="mb-3" style="display:none">
                    <label class="form-label">Максимум изображений в галерее</label>
                    <input type="number" id="configGalleryMaxItems" class="form-control form-control-sm" min="0" step="1" placeholder="0 = без лимита">
                    <div class="form-text mb-2">После достижения лимита кнопка «Добавить» блокируется.</div>

                    <label class="form-label">Максимальный размер каждого изображения (КБ)</label>
                    <input type="number" id="configGalleryMaxSize" class="form-control form-control-sm" min="0" step="1" placeholder="0 = без лимита">
                </div>

                
                <div id="configDocLinkBlock" class="mb-3" style="display:none">
                    <label class="form-label">Искать только в рубрике (опционально)</label>
                    <select id="configDocLinkRubric" class="form-select form-select-sm">
                        <option value="">— все рубрики —</option>
                        @foreach($allRubrics ?? [] as $r)
                            <option value="{{ $r->id }}">{{ $r->title }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Если задано, поиск документа в редакторе будет ограничен одной рубрикой.</div>
                </div>

                
                <div id="configDateFormatBlock" class="mb-3" style="display:none">
                    <label class="form-label">Формат вывода <code class="ms-1">[value]</code></label>
                    <input type="text" id="configDisplayFormat" class="form-control form-control-sm" placeholder="d.m.Y">
                    <div class="form-text">
                        Формат PHP-функции <code>date()</code>. Примеры:
                        <code>d.m.Y</code> = 19.04.2026,
                        <code>d F Y</code> = 19 апреля 2026,
                        <code>l, j F</code> = воскресенье, 19 апреля.
                        Пусто = формат по умолчанию для типа.
                    </div>
                </div>

                
                <div id="configCodeModeBlock" class="mb-3" style="display:none">
                    <label class="form-label">Язык подсветки</label>
                    <select id="configCodeMode" class="form-select form-select-sm">
                        <option value="text">Без подсветки (text)</option>
                        <option value="html">HTML</option>
                        <option value="css">CSS</option>
                        <option value="scss">SCSS</option>
                        <option value="less">LESS</option>
                        <option value="javascript">JavaScript</option>
                        <option value="json">JSON</option>
                        <option value="php">PHP</option>
                        <option value="python">Python</option>
                        <option value="sql">SQL</option>
                        <option value="yaml">YAML</option>
                        <option value="markdown">Markdown</option>
                        <option value="xml">XML</option>
                        <option value="twig">Twig / Blade</option>
                    </select>
                    <div class="form-text">Применяется в Ace Editor на странице документа (если редактор активен).</div>
                </div>

                
                <div id="configPriceBlock" class="mb-3" style="display:none">
                    <label class="form-label">Валюта по умолчанию</label>
                    <select id="configPriceDefaultCurrency" class="form-select form-select-sm">
                        <option value="RUB">RUB (₽) - Российский рубль</option>
                        <option value="USD">USD ($) - Доллар США</option>
                        <option value="EUR">EUR (€) - Евро</option>
                        <option value="GBP">GBP (£) - Фунт стерлингов</option>
                        <option value="KZT">KZT (₸) - Казахстанский тенге</option>
                        <option value="BYN">BYN (Br) - Белорусский рубль</option>
                        <option value="UAH">UAH (₴) - Украинская гривна</option>
                        <option value="CNY">CNY (¥) - Китайский юань</option>
                    </select>
                    <div class="form-text">При создании нового документа поле будет предзаполнено этой валютой.</div>
                </div>

                
                <div id="configIconBlock" class="mb-3" style="display:none">
                    <label class="form-label">Фильтр иконок по префиксу</label>
                    <input type="text" id="configIconCategoryFilter" class="form-control form-control-sm" placeholder="arrow, chevron, caret">
                    <div class="form-text">
                        CSV-список префиксов имён иконок (без <code>bi-</code>). Например <code>arrow, chevron, caret</code>
                        - picker покажет только стрелки. Пусто = все иконки.
                    </div>
                </div>

                
                <div id="configColorBlock" class="mb-3" style="display:none">
                    <label class="form-label">Формат значения</label>
                    <select id="configColorFormat" class="form-select form-select-sm">
                        <option value="hex">HEX (#7c3aed) - нативный picker</option>
                        <option value="rgb">RGB (rgb(124, 58, 237)) - текстовый ввод</option>
                        <option value="rgba">RGBA (rgba(124, 58, 237, 0.85)) - с прозрачностью</option>
                    </select>
                    <div class="form-text">HEX-формат использует встроенный color-picker браузера. RGB и RGBA вводятся как текст.</div>
                </div>

                <div class="mb-3" id="configDefaultBlock">
                    <label class="form-label">Значение по умолчанию</label>
                    <input type="text" id="configDefault" class="form-control"
                        placeholder="Предзаполняется при создании документа">
                    <div class="form-text" id="configDefaultHint" style="display:none">
                        Должно совпадать с одним из вариантов списка.
                    </div>
                </div>

                <div class="mb-3" id="configDefaultCheckboxBlock" style="display:none">
                    <div class="form-check">
                        <input type="checkbox" id="configDefaultCheckbox" class="form-check-input" value="1">
                        <label class="form-check-label" for="configDefaultCheckbox">Отмечен по умолчанию</label>
                    </div>
                    <div class="form-text">При создании документа чекбокс будет включён.</div>
                </div>

                <div>
                    <label class="form-label">Описание поля</label>
                    <textarea id="configDesc" class="form-control" rows="2"
                        placeholder="Подсказка для редактора документа"></textarea>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnConfigSave">
                    <i class="bi bi-floppy me-1"></i>Сохранить
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDeleteField" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Удалить поле</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 ztr-rubrics-fields-smaller">
                    Удалить поле <strong id="deleteFieldTitle"></strong>?<br>
                    <span class="text-warning ztr-rubrics-fields-small" id="deleteFieldWarning"></span>
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteFieldConfirm">Удалить</button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
window.ZentraConfig = {
    rubricId: {{ $rubric->id }},
    canEdit:  {{ ($canEdit ?? false) ? 'true' : 'false' }},
    canDelete:{{ ($canDelete ?? false) ? 'true' : 'false' }},
    inApiUrlTpl: '{{ url('admin/rubrics/' . $rubric->id . '/fields/0/in-api') }}',
};
</script>
<script src="{{ route('admin.asset', 'js/rubrics-fields.js') }}"></script>
@endpush
