@extends('admin.layout')

@section('title', 'Редактирование - ' . $document->title)

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/documents-edit.css') }}">
@endpush

@section('content')
<div class="ztr-page-title"><i class="bi bi-file-text me-2"></i>Редактирование документа</div>

<div class="d-flex gap-2 mb-3 align-items-center flex-wrap">
    <a href="{{ route('admin.documents.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>К списку
    </a>
    <span class="badge bg-{{ $document->statusClass() }}">{{ $document->statusLabel() }}</span>
    <span class="text-muted ztr-doc-header-note">
        Рубрика: <strong>{{ $document->rubric?->title ?? '—' }}</strong>
    </span>
    <div class="ms-auto d-flex align-items-center gap-2">
        @if($document->status === \App\Models\Document::STATUS_ACTIVE)
        @php
            $suffix = \App\Models\Setting::getValue('url_suffix', '');
            if ($document->alias === 'index') {
                $publicUrl = '/';
            } elseif (!$document->alias) {
                $publicUrl = $document->rubric?->alias ? '/' . $document->rubric->alias . $suffix : '/';
            } else {
                $publicUrl = '/' . ($document->rubric?->alias ? $document->rubric->alias . '/' : '') . $document->alias . $suffix;
            }
        @endphp
        <a href="{{ $publicUrl }}" class="btn btn-sm btn-success" target="_blank" title="Просмотр на сайте">
            <i class="bi bi-box-arrow-up-right me-1"></i>На сайте
        </a>
        @else
        <a href="{{ route('admin.documents.preview', $document) }}" class="btn btn-sm btn-{{ $document->statusClass() }}" target="_blank" title="Превью (документ не опубликован)">
            <i class="bi bi-eye me-1"></i>Превью
        </a>
        @endif
        @if($canEdit)
        <span id="saveStatus" class="ztr-doc-save-status"></span>
        <button type="button" class="btn btn-sm btn-primary" id="btnSave">
            <i class="bi bi-floppy me-1"></i>Сохранить
        </button>
        @else
        <span class="ztr-alert-info d-inline-flex align-items-center gap-2 ztr-doc-view-only">
            <i class="bi bi-eye"></i>Режим просмотра - у вас нет прав на редактирование этого документа
        </span>
        @endif
    </div>
</div>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="docTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tabContent">
                    <i class="bi bi-file-text me-1"></i>Содержимое
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tabSeo">
                    <i class="bi bi-search me-1"></i>SEO и URL
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tabOther">
                    <i class="bi bi-sliders me-1"></i>Прочие параметры
                </a>
            </li>
        </ul>
    </div>
    <div class="card-body tab-content">

        
        <div class="tab-pane fade show active" id="tabContent">
        <fieldset class="ztr-doc-fieldset border-0 p-0 m-0" @if(!$canEdit) disabled @endif>

            <div class="mb-3">
                <label class="form-label">Название документа <span class="text-danger">*</span></label>
                <input type="text" name="title" class="form-control" value="{{ $document->title }}" required>
                <div class="form-text">Используется как заголовок страницы и в списках.</div>
            </div>

            @if($rubricFields->isNotEmpty())
            <hr class="ztr-doc-section-hr">
            <div class="mb-2 fw-semibold ztr-doc-fields-title">
                <i class="bi bi-layout-three-columns me-1"></i>Поля рубрики
                <span class="text-muted fw-normal">«{{ $document->rubric?->title }}»</span>
            </div>
            <div class="row g-3">
                @foreach($rubricFields as $field)
                @php
                    $instance = $field->fieldInstance();
                    $value    = $fieldValues->get($field->id)?->value;
                    $config   = [
                        'name'        => "fields[{$field->id}]",
                        'alias'       => $field->alias,
                        'label'       => $field->title,
                        'description' => $field->description,
                        'default'     => $field->default_value,
                        'config'      => $field->config ?? [],
                    ];
                @endphp
                @if($instance)
                <div class="col-12">
                    {!! $instance->renderEdit($value, $config) !!}
                </div>
                @endif
                @endforeach
            </div>
            @else
            <div class="text-muted ztr-doc-no-fields">
                <i class="bi bi-info-circle me-1"></i>
                У рубрики «{{ $document->rubric?->title }}» нет настроенных полей.
                <a href="{{ route('admin.rubrics.fields', $document->rubric_id) }}">Добавить поля рубрики</a>
            </div>
            @endif

        </fieldset>
        </div>

        
        <div class="tab-pane fade" id="tabSeo">
        <fieldset class="ztr-doc-fieldset border-0 p-0 m-0" @if(!$canEdit) disabled @endif>
            <div class="row g-3 mb-4 ztr-doc-seo-form">
                <div class="col-12">
                    <label class="form-label">Псевдоним (slug)
                        <i class="bi bi-info-circle text-muted ms-1 ztr-doc-info-icon"
                            data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-html="true"
                            data-bs-title="Алиас документа"
                            data-bs-content="Только латинские буквы, цифры и дефисы.<br><br>&#x2728; <strong>Особое значение:</strong><br>Алиас <code>index</code> - главная страница сайта (<code>/</code>).<br>Пустой алиас - главная страница рубрики (<code>/префикс-рубрики</code>).<br><br>Все остальные алиасы формируют URL вида <code>/префикс-рубрики/алиас</code>"></i>
                    </label>
                    <div class="input-group">
                        <span id="aliasPrefix" class="input-group-text ztr-doc-alias-prefix">
                            /{{ $document->rubric?->alias ? $document->rubric->alias . '/' : '' }}
                        </span>
                        <input type="text" name="alias" id="aliasInput" class="form-control"
                            value="{{ $document->alias }}">
                    </div>
                    <div class="form-text">Только латинские буквы, цифры и дефисы. Оставьте пустым для главной страницы рубрики.</div>
                </div>
                <div class="col-12">
                    <div class="p-2 rounded ztr-doc-url-preview">
                        @php $urlSuffix = \App\Models\Setting::getValue('url_suffix', ''); @endphp
                        Полный URL: <code id="urlPreview">
                            @if($document->alias === 'index')
                                /
                            @elseif($document->alias === null || $document->alias === '')
                                /{{ $document->rubric?->alias ?? '' }}
                            @else
                                /{{ $document->rubric?->alias ? $document->rubric->alias . '/' : '' }}{{ $document->alias }}{{ $urlSuffix }}
                            @endif
                        </code>
                    </div>
                </div>
            </div>

            <hr class="ztr-doc-section-hr">

            <div class="row g-3">
                <div class="col-12">
                    <label class="form-label">Заголовок страницы <small class="text-muted">(meta title)</small></label>
                    <input type="text" name="meta_title" class="form-control"
                        value="{{ $document->meta_title }}" placeholder="{{ $document->title }}">
                    <div class="form-text">HTML &lt;title&gt;. Если пусто - используется «Название документа».</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Ключевые слова <small class="text-muted">(meta keywords)</small></label>
                    <textarea name="meta_keywords" class="form-control" rows="3"
                        placeholder="слово1, слово2, слово3">{{ $document->meta_keywords }}</textarea>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Описание страницы <small class="text-muted">(meta description)</small></label>
                    <textarea name="meta_description" class="form-control" rows="3"
                        placeholder="Краткое описание для поисковиков">{{ $document->meta_description }}</textarea>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Тип индексирования</label>
                    <select name="meta_robots" class="form-select">
                        @foreach(['index,follow' => 'index, follow (по умолчанию)', 'noindex,follow' => 'noindex, follow', 'index,nofollow' => 'index, nofollow', 'noindex,nofollow' => 'noindex, nofollow'] as $val => $label)
                        <option value="{{ $val }}" {{ $document->meta_robots === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Частота обновления <small class="text-muted">(sitemap)</small></label>
                    <select name="sitemap_changefreq" class="form-select">
                        <option value="">— Не задано —</option>
                        @foreach(['always' => 'Всегда', 'hourly' => 'Каждый час', 'daily' => 'Ежедневно', 'weekly' => 'Еженедельно', 'monthly' => 'Ежемесячно', 'yearly' => 'Ежегодно', 'never' => 'Никогда'] as $val => $label)
                        <option value="{{ $val }}" {{ $document->sitemap_changefreq === $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Приоритет <small class="text-muted">(sitemap)</small></label>
                    <select name="sitemap_priority" class="form-select">
                        <option value="">— Не задано —</option>
                        @foreach(['0.0','0.1','0.2','0.3','0.4','0.5','0.6','0.7','0.8','0.9','1.0'] as $p)
                        <option value="{{ $p }}" {{ (string)$document->sitemap_priority === $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-12">
                    <hr class="my-2">
                </div>
                <div class="col-md-4">
                    <label class="form-label">HTTP-кэш страницы
                        <i class="bi bi-info-circle text-muted ms-1"
                           data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-html="true"
                           data-bs-content="Если включено - middleware HTTP-кеша не сохраняет ответ для этого документа. Полезно для динамических страниц (онлайн-формы, корзина, отчёты).<br><br>Глобально кеш настраивается в <strong>Настройки → Кэш и сессии → Кэш публичных страниц</strong>.">
                        </i>
                    </label>
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input" type="checkbox" name="public_cache_disabled" id="publicCacheDisabled"
                            value="1" @checked($document->public_cache_disabled)>
                        <label class="form-check-label" for="publicCacheDisabled">Не кешировать эту страницу</label>
                    </div>
                </div>
                <div class="col-md-4">
                    <label class="form-label">TTL для документа <small class="text-muted">(сек)</small>
                        <i class="bi bi-info-circle text-muted ms-1"
                           data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-html="true"
                           data-bs-content="Если задано - переопределяет TTL рубрики и глобальный default. Пусто = использовать рубричный или глобальный.<br><br>0 = эквивалент «не кешировать».">
                        </i>
                    </label>
                    <input type="number" name="public_cache_ttl" class="form-control"
                        value="{{ $document->public_cache_ttl ?? '' }}" min="0" max="604800"
                        placeholder="из настроек рубрики/сайта">
                </div>
            </div>
        </fieldset>
        </div>

        
        <div class="tab-pane fade" id="tabOther">
        <fieldset class="ztr-doc-fieldset border-0 p-0 m-0" @if(!$canEdit) disabled @endif>
            <div class="row g-3">
                <div class="col-md-3">
                    <label class="form-label">Статус документа
                        @if(!$canPublish)
                        <i class="bi bi-info-circle text-muted ms-1 ztr-doc-info-icon"
                            data-bs-toggle="popover" data-bs-trigger="hover focus" data-bs-html="true"
                            data-bs-title="Публикация недоступна"
                            data-bs-content="У вас нет прав публиковать документы в этой рубрике напрямую. Статус «Опубликован» скрыт - сохраняйте как «Черновик» или «На модерации»."></i>
                        @endif
                    </label>
                    <select name="status" class="form-select">
                        <option value="0" {{ $document->status === 0 ? 'selected' : '' }}>Черновик</option>
                        @if($canPublish)
                        <option value="1" {{ $document->status === 1 ? 'selected' : '' }}>Опубликован</option>
                        @endif
                        <option value="2" {{ $document->status === 2 ? 'selected' : '' }}>На модерации</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Позиция документа</label>
                    <input type="number" name="position" class="form-control"
                        value="{{ $document->position }}" min="0">
                    <div class="form-text">Для ручной сортировки в списках.</div>
                </div>

                <div class="col-12"><hr class="ztr-doc-section-hr"></div>

                <div class="col-md-6">
                    <label class="form-label">Начало публикации</label>
                    <input type="datetime-local" name="published_at" class="form-control"
                        value="{{ $document->published_at?->format('Y-m-d\TH:i') }}">
                    <div class="form-text">Когда документ станет виден на сайте. Если не задано - виден сразу (при статусе «Опубликован»).</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Окончание публикации</label>
                    <input type="datetime-local" name="unpublished_at" class="form-control"
                        value="{{ $document->unpublished_at?->format('Y-m-d\TH:i') }}">
                    <div class="form-text">Когда документ перестанет отображаться. Если не задано - без ограничения.</div>
                </div>

                <div class="col-12"><hr class="ztr-doc-section-hr"></div>

                <div class="col-md-5">
                    <label class="form-label">Связать с пунктом меню</label>
                    <select name="nav_item_id" class="form-select">
                        <option value="">— Не связан —</option>
                        @foreach($navigations as $nav)
                        @if($nav->items->isNotEmpty())
                        <optgroup label="{{ $nav->title }}">
                            @foreach($nav->items as $item)
                            <option value="{{ $item->id }}" {{ $document->nav_item_id == $item->id ? 'selected' : '' }}>
                                {{ $item->title }}{{ $item->url ? ' (' . $item->url . ')' : '' }}
                            </option>
                            @endforeach
                        </optgroup>
                        @endif
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Название для хлебных крошек</label>
                    <input type="text" name="breadcrumb_title" class="form-control"
                        value="{{ $document->breadcrumb_title }}"
                        placeholder="{{ $document->title }}">
                    <div class="form-text">Если пусто - используется название документа.</div>
                </div>

                <div class="col-md-5">
                    <label class="form-label">Родительский документ <small class="text-muted">(хлебные крошки)</small></label>
                    <select name="parent_doc_id" class="form-select">
                        <option value="">— Не задан —</option>
                        @foreach($parentDocs as $pd)
                        <option value="{{ $pd->id }}" {{ $document->parent_doc_id == $pd->id ? 'selected' : '' }}>
                            [{{ $pd->id }}] {{ $pd->title }}
                            @if($pd->rubric) ({{ $pd->rubric->title }}) @endif
                        </option>
                        @endforeach
                    </select>
                    <div class="form-text">Используется при формировании breadcrumbs на сайте.</div>
                </div>

            </div>
        </fieldset>
        </div>

    </div>
</div>

@if($canRevisions)

<div class="card mt-3 d-none" id="revisionsCard">
    <div class="card-header d-flex align-items-center justify-content-between">
        <span><i class="bi bi-clock-history me-2"></i>История изменений</span>
        @if($canEdit)
        <button type="button" class="btn btn-sm btn-outline-danger d-none" id="btnDeleteAllRevisions">
            <i class="bi bi-trash me-1"></i>Удалить все
        </button>
        @endif
    </div>
    <div class="card-body p-0" id="revisionsBody">
        <div class="text-center py-3 text-muted" id="revisionsLoading">
            <span class="spinner-border spinner-border-sm me-1"></span> Загрузка...
        </div>
    </div>
</div>

<div class="modal fade" id="revisionModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-clock-history me-2"></i>Просмотр ревизии</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="revisionModalBody">
                <div class="text-center py-3"><span class="spinner-border spinner-border-sm"></span></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Закрыть</button>
                @if($canEdit)
                <button type="button" class="btn btn-sm btn-warning" id="btnRestoreFromModal">
                    <i class="bi bi-arrow-counterclockwise me-1"></i>Восстановить
                </button>
                @endif
            </div>
        </div>
    </div>
</div>

@if($canEdit)
<div class="modal fade" id="confirmDeleteAllModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Удалить все ревизии?</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Все сохранённые версии документа будут удалены безвозвратно.</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-sm btn-danger" id="btnConfirmDeleteAll">Удалить все</button>
            </div>
        </div>
    </div>
</div>
@endif
@endif

<div class="modal fade" id="confirmDeleteMediaModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Удалить файл</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1 ztr-doc-media-msg">Удалить этот файл безвозвратно?</p>
                <p class="mb-0 text-warning ztr-doc-media-warn">
                    <i class="bi bi-exclamation-triangle me-1"></i>Файл будет удалён с сервера, восстановить его не получится.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnConfirmDeleteMedia">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
<script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.32.8/src-min-noconflict/ace.js"></script>
<script>
window.ZentraConfig = {
    saveUrl: '{{ route('admin.documents.update', $document) }}',
    rubricAlias: '{{ $document->rubric?->alias ?? '' }}',
    revisionsUrl: '{{ route('admin.documents.revisions', $document) }}',
    revisionBaseUrl: '{{ route('admin.documents.revisions.show', [$document, '__id__']) }}',
    deleteAllUrl: '{{ route('admin.documents.revisions.destroyAll', $document) }}',
    uploadUrl:     '{{ route('admin.upload.image') }}',
    uploadFileUrl: '{{ route('admin.upload.file') }}',
    docSearchUrl:  '{{ route('admin.documents.search') }}',
    documentId:    {{ $document->id }},
    urlSuffix: '{{ \App\Models\Setting::getValue('url_suffix', '') }}',
    canEdit:      {{ $canEdit ? 'true' : 'false' }},
    canRevisions: {{ $canRevisions ? 'true' : 'false' }},
    canPublish:   {{ $canPublish ? 'true' : 'false' }}
};
</script>
<script src="{{ route('admin.asset', 'js/bootstrap-icons-list.js') }}"></script>
<script src="{{ route('admin.asset', 'js/documents-edit.js') }}"></script>
@endpush
