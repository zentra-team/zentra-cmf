@extends('admin.layout')

@section('title', 'Рубрики')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/rubrics-index.css') }}">

@endpush

@section('content')
@php
    // Курируемая палитра - приятные насыщенные цвета под тёмную тему
    $rubricColorPalette = [
        '#7c3aed', // violet
        '#3b82f6', // blue
        '#06b6d4', // cyan
        '#14b8a6', // teal
        '#10b981', // emerald
        '#84cc16', // lime
        '#f59e0b', // amber
        '#f97316', // orange
        '#ef4444', // red
        '#ec4899', // pink
        '#8b5cf6', // purple-400
        '#6366f1', // indigo
    ];
    $randomRubricColor = $rubricColorPalette[array_rand($rubricColorPalette)];
@endphp
<div class="ztr-page-title"><i class="bi bi-folder2 me-2"></i>Рубрики</div>

<div class="card">
    <div class="card-header ztr-rubrics-card-header">
        <ul class="nav nav-tabs" id="rubricTabs" role="tablist">
            @if($canList ?? false)
            <li class="nav-item">
                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabRubrics" type="button">
                    <i class="bi bi-folder2 me-1"></i>Рубрики
                </button>
            </li>
            @endif
            @if($canCreate ?? false)
            <li class="nav-item">
                <button class="nav-link {{ ($canList ?? false) ? '' : 'active' }}" data-bs-toggle="tab" data-bs-target="#tabCreate" type="button">
                    <i class="bi bi-plus-circle me-1"></i>Создать
                </button>
            </li>
            @endif
        </ul>
    </div>

    <div class="card-body">
        <div class="tab-content">

            @if($canList ?? false)
            <div class="tab-pane fade show active" id="tabRubrics">
                @if($rubrics->isEmpty())
                    <p class="text-muted ztr-rubrics-smaller">Рубрик пока нет. Создайте первую во вкладке «Создать».</p>
                @else
                    <div class="table-responsive">
                    <table class="table table-hover mb-2 ztr-rubrics-table" id="rubricsTable">
                        <thead>
                            <tr>
                                <th class="ztr-rubrics-col-id">#</th>
                                <th class="ztr-rubrics-col-drag"></th>
                                <th class="ztr-rubrics-col-title">Название</th>
                                <th class="ztr-rubrics-col-alias">Префикс</th>
                                <th class="ztr-rubrics-col-color text-center">Цвет</th>
                                <th class="ztr-rubrics-col-layout">Макет</th>
                                <th class="ztr-rubrics-col-docs text-center">Документы</th>
                                <th class="ztr-rubrics-col-fields text-center">Поля</th>
                                <th class="ztr-rubrics-col-actions"></th>
                            </tr>
                        </thead>
                        <tbody id="rubricsSortable">
                            @foreach($rubrics as $rubric)
                            <tr id="row-{{ $rubric->id }}" data-id="{{ $rubric->id }}">
                                <td class="text-muted align-middle ztr-rubrics-small">{{ $rubric->id }}</td>
                                <td class="align-middle">
                                    <span class="rubric-drag-handle" title="Перетащить"><i class="bi bi-grip-vertical"></i></span>
                                </td>
                                <td class="align-middle">
                                    <input type="text" class="tbl-input rubric-title" data-id="{{ $rubric->id }}"
                                        value="{{ $rubric->title }}" @if(!($canEdit ?? false)) readonly @endif>
                                </td>
                                <td class="align-middle">
                                    <input type="text" class="tbl-input rubric-alias" data-id="{{ $rubric->id }}"
                                        value="{{ $rubric->alias }}" @if(!($canEdit ?? false)) readonly @endif>
                                </td>
                                <td class="text-center align-middle">
                                    <input type="color" class="form-control form-control-color rubric-color ztr-rubrics-color-input"
                                        data-id="{{ $rubric->id }}"
                                        value="{{ $rubric->color ?? '#6f42c1' }}"
                                        title="Цвет рубрики" @if(!($canEdit ?? false)) disabled @endif>
                                </td>
                                <td class="align-middle">
                                    <select class="tbl-select rubric-layout" data-id="{{ $rubric->id }}" @if(!($canEdit ?? false)) disabled @endif required>
                                        @foreach($layouts as $layout)
                                            <option value="{{ $layout->id }}" {{ $rubric->layout_id == $layout->id ? 'selected' : '' }}>
                                                {{ $layout->title }}
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="text-center align-middle" id="docs-cell-{{ $rubric->id }}">
                                    <a href="#" class="link-secondary btn-docs-count ztr-rubrics-small"
                                        data-id="{{ $rubric->id }}">
                                        Показать
                                    </a>
                                </td>
                                <td class="text-center align-middle">
                                    <span class="text-muted ztr-rubrics-small">{{ $rubric->fields_count }}</span>
                                </td>
                                <td class="align-middle">
                                    <div class="ztr-rubric-actions-grid">
                                    <a href="{{ route('admin.rubrics.fields', $rubric) }}"
                                        class="btn btn-sm btn-outline-success" title="{{ ($canEdit ?? false) ? 'Поля рубрики' : 'Просмотр полей' }}">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                    <a href="{{ route('admin.rubrics.template', $rubric) }}"
                                        class="btn btn-sm btn-outline-success" title="Шаблон рубрики">
                                        <i class="bi bi-file-code"></i>
                                    </a>
                                    @if($canPermissions ?? false)
                                    <a href="{{ route('admin.rubrics.permissions', $rubric) }}"
                                        class="btn btn-sm btn-outline-success" title="Права доступа">
                                        <i class="bi bi-shield-check"></i>
                                    </a>
                                    @endif
                                    @if($canCreate ?? false)
                                    <button type="button" class="btn btn-sm btn-outline-success btn-copy-rubric"
                                        data-id="{{ $rubric->id }}"
                                        data-title="{{ $rubric->title }}"
                                        data-alias="{{ $rubric->alias }}"
                                        title="Копировать">
                                        <i class="bi bi-copy"></i>
                                    </button>
                                    @endif
                                    @if($canEdit ?? false)
                                    <button type="button" class="btn btn-sm btn-outline-success btn-rubric-seo"
                                        data-id="{{ $rubric->id }}"
                                        data-title="{{ $rubric->title }}"
                                        data-include="{{ $rubric->sitemap_include ? '1' : '0' }}"
                                        data-changefreq="{{ $rubric->sitemap_changefreq ?? '' }}"
                                        data-priority="{{ $rubric->sitemap_priority ?? '' }}"
                                        data-index-changefreq="{{ $rubric->sitemap_index_changefreq ?? '' }}"
                                        data-index-priority="{{ $rubric->sitemap_index_priority ?? '' }}"
                                        data-cache-disabled="{{ $rubric->public_cache_disabled ? '1' : '0' }}"
                                        data-cache-ttl="{{ $rubric->public_cache_ttl ?? '' }}"
                                        title="SEO / Sitemap / Кэш">
                                        <i class="bi bi-diagram-3"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success btn-rubric-rss"
                                        data-id="{{ $rubric->id }}"
                                        data-alias="{{ $rubric->alias }}"
                                        data-title="{{ $rubric->title }}"
                                        data-rss-enabled="{{ $rubric->rss_enabled ? '1' : '0' }}"
                                        data-rss-title="{{ $rubric->rss_title ?? '' }}"
                                        data-rss-description="{{ $rubric->rss_description ?? '' }}"
                                        data-rss-limit="{{ $rubric->rss_limit ?? '' }}"
                                        data-rss-desc-field="{{ $rubric->rss_description_field_id ?? '' }}"
                                        data-rss-image-field="{{ $rubric->rss_image_field_id ?? '' }}"
                                        data-rss-category-field="{{ $rubric->rss_category_field_id ?? '' }}"
                                        title="RSS-фид рубрики">
                                        <i class="bi bi-rss"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-success btn-rubric-api"
                                        data-id="{{ $rubric->id }}"
                                        data-alias="{{ $rubric->alias }}"
                                        data-title="{{ $rubric->title }}"
                                        data-api-enabled="{{ $rubric->api_enabled ? '1' : '0' }}"
                                        data-api-default-limit="{{ $rubric->api_default_limit ?? '' }}"
                                        data-api-max-limit="{{ $rubric->api_max_limit ?? '' }}"
                                        title="JSON API рубрики">
                                        <i class="bi bi-braces"></i>
                                    </button>
                                    @endif
                                    @if($canDelete ?? false)
                                        @if($rubric->hasDocuments())
                                        <span class="d-inline-block" tabindex="0"
                                            data-bs-toggle="tooltip" data-bs-placement="top"
                                            title="Невозможно удалить - в рубрике есть документы">
                                            <button type="button" class="btn btn-sm btn-outline-danger ztr-rubrics-no-pointer" disabled>
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </span>
                                        @else
                                        <button type="button" class="btn btn-sm btn-outline-danger btn-delete-rubric"
                                            data-id="{{ $rubric->id }}"
                                            data-title="{{ $rubric->title }}"
                                            title="Удалить">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                        @endif
                                    @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>

                    @if($canEdit ?? false)
                    <div class="d-flex gap-2 mt-1">
                        <button type="button" class="btn btn-primary btn-sm" id="btnSaveAll">
                            <i class="bi bi-floppy me-1"></i>Сохранить изменения
                        </button>
                    </div>
                    @endif
                @endif
            </div>
            @endif

            @if($canCreate ?? false)
            <div class="tab-pane fade {{ ($canList ?? false) ? '' : 'show active' }}" id="tabCreate">
                @if($layouts->isEmpty())
                    <div class="ztr-alert-info d-flex align-items-start gap-2 ztr-rubrics-form-narrow">
                        <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1 ztr-rubrics-alert-icon"></i>
                        <div>
                            Создание рубрики невозможно без макета. Сначала
                            <a href="{{ route('admin.layouts.index') }}" class="fw-semibold">создайте хотя бы один макет</a>,
                            затем вернитесь сюда.
                        </div>
                    </div>
                @else
                <form method="POST" action="{{ route('admin.rubrics.store') }}" class="ztr-rubrics-form-narrow">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Название рубрики <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="rubricTitle" class="form-control"
                            autofocus placeholder="Например: Новости">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Префикс URL</label>
                        <input type="text" name="alias" id="rubricAlias" class="form-control"
                            placeholder="например: news">
                        <div class="form-text">Необязательно. Только латиница, цифры, дефис и подчёркивание.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Связать с макетом</label>
                        <select name="layout_id" class="form-select">
                            @foreach($layouts as $layout)
                            <option value="{{ $layout->id }}" {{ old('layout_id', $layouts->first()->id) == $layout->id ? 'selected' : '' }}>
                                {{ $layout->title }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Цвет</label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="color" name="color" class="form-control form-control-color ztr-rubrics-color-input-lg"
                                value="{{ old('color', $randomRubricColor) }}">
                            <span class="text-muted ztr-rubrics-small">Используется в тегах шаблона</span>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="bi bi-plus-lg me-1"></i>Создать
                    </button>
                </form>
                @endif
            </div>
            @endif

        </div>
    </div>
</div>

<div class="modal fade" id="modalCopyRubric" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered ztr-rubrics-modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-copy me-2"></i>Копировать рубрику</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted mb-3 ztr-rubrics-smaller">
                    Копия рубрики <strong id="copyRubricSource"></strong>
                </p>
                <div class="mb-2">
                    <label class="form-label">Название новой рубрики</label>
                    <input type="text" id="copyRubricTitle" class="form-control" placeholder="Уникальное название">
                </div>
                <div>
                    <label class="form-label">Префикс для ссылок</label>
                    <input type="text" id="copyRubricAlias" class="form-control" placeholder="latin-alias">
                    <div class="form-text">Только латинские буквы, цифры, тире и подчёркивание.</div>
                </div>
                <div id="copyRubricError" class="text-danger mt-2 ztr-rubrics-small" style="display:none"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnCopyRubricConfirm">
                    <i class="bi bi-copy me-1"></i>Копировать
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalDeleteRubric" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-trash me-2"></i>Удалить рубрику</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0 ztr-rubrics-smaller">
                    Удалить рубрику <strong id="deleteRubricTitle"></strong>?<br>
                    Все поля и права доступа будут удалены.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteRubricConfirm">Удалить</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRubricSeo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-diagram-3 me-2"></i>SEO / Sitemap</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-text mb-3">
                    Эти параметры применяются к рубрике и её документам в карте сайта <code>/sitemap.xml</code>.
                    Если поле оставить пустым - будет взят глобальный дефолт из
                    <a href="{{ route('admin.settings') }}#tabSeo">Настроек → SEO → Sitemap</a>.
                    Документ может переопределить значения через свои поля.
                </div>

                <input type="hidden" id="rubricSeoId">

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="rubricSeoInclude" value="1">
                    <label class="form-check-label" for="rubricSeoInclude">
                        Включать рубрику и её документы в sitemap
                    </label>
                </div>

                <hr class="my-3">
                <div class="form-text mb-2 fw-semibold">Документы рубрики</div>

                <div class="row g-2 mb-3">
                    <div class="col-md-7">
                        <label class="form-label">Частота обновления</label>
                        <select id="rubricSeoChangefreq" class="form-select form-select-sm">
                            <option value="">— глобальный дефолт —</option>
                            <option value="always">always</option>
                            <option value="hourly">hourly</option>
                            <option value="daily">daily</option>
                            <option value="weekly">weekly</option>
                            <option value="monthly">monthly</option>
                            <option value="yearly">yearly</option>
                            <option value="never">never</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Приоритет</label>
                        <input type="number" id="rubricSeoPriority" class="form-control form-control-sm"
                            min="0" max="1" step="0.1" placeholder="по умолчанию">
                    </div>
                </div>

                <hr class="my-3">
                <div class="form-text mb-2 fw-semibold">Кэш публичных страниц</div>
                <div class="form-text mb-3">
                    Применяется ко всем документам рубрики (если не переопределено в самом документе).
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-md-6">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="rubricCacheDisabled" value="1">
                            <label class="form-check-label" for="rubricCacheDisabled">Не кешировать документы рубрики</label>
                        </div>
                        <div class="form-text">
                            Полезно для рубрик с динамическим контентом (акции, котировки, отчёты).
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">TTL для рубрики (секунды)</label>
                        <input type="number" id="rubricCacheTtl" class="form-control form-control-sm"
                            min="0" max="604800" placeholder="по умолчанию из глобальных настроек">
                        <div class="form-text">Пусто = глобальный default.</div>
                    </div>
                </div>

                <hr class="my-3">
                <div class="form-text mb-2 fw-semibold">Индексная страница рубрики (<code>/{alias}</code>)</div>
                <div class="form-text mb-2">Если у рубрики есть документ с пустым алиасом - его параметры можно отдельно настроить.</div>

                <div class="row g-2">
                    <div class="col-md-7">
                        <label class="form-label">Частота обновления для индекса</label>
                        <select id="rubricSeoIndexChangefreq" class="form-select form-select-sm">
                            <option value="">— как у документов рубрики —</option>
                            <option value="always">always</option>
                            <option value="hourly">hourly</option>
                            <option value="daily">daily</option>
                            <option value="weekly">weekly</option>
                            <option value="monthly">monthly</option>
                            <option value="yearly">yearly</option>
                            <option value="never">never</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label">Приоритет для индекса</label>
                        <input type="number" id="rubricSeoIndexPriority" class="form-control form-control-sm"
                            min="0" max="1" step="0.1" placeholder="как у документов">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnRubricSeoSave">
                    <i class="bi bi-check-circle me-1"></i>Сохранить
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalRubricRss" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-rss me-2"></i>RSS-фид</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-text mb-3">
                    Когда включён - фид доступен на <code><span id="rubricRssUrl"></span></code>.
                    Для <code>&lt;description&gt;</code> / <code>&lt;enclosure&gt;</code> / <code>&lt;category&gt;</code>
                    нужно указать какое поле рубрики использовать. Если не указать - соответствующий тег в фиде не появится.
                </div>

                <input type="hidden" id="rubricRssId">

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="rubricRssEnabled" value="1">
                    <label class="form-check-label" for="rubricRssEnabled">RSS-фид включён</label>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-8">
                        <label class="form-label">Название фида</label>
                        <input type="text" id="rubricRssTitleInput" class="form-control form-control-sm"
                            maxlength="255" placeholder="По умолчанию - название рубрики">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Лимит документов</label>
                        <input type="number" id="rubricRssLimit" class="form-control form-control-sm"
                            min="1" max="500" placeholder="по умолчанию">
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Описание фида</label>
                    <textarea id="rubricRssDescription" class="form-control form-control-sm" rows="2"
                        maxlength="1000" placeholder="По умолчанию - описание рубрики"></textarea>
                </div>

                <hr class="my-3">
                <div class="form-text mb-3 fw-semibold">Маппинг полей рубрики на элементы фида</div>

                <div class="row g-2 mb-2">
                    <div class="col-md-12">
                        <label class="form-label">Поле для <code>&lt;description&gt;</code></label>
                        <select id="rubricRssDescField" class="form-select form-select-sm">
                            <option value="">— не указано (используется meta_description документа) —</option>
                        </select>
                        <div class="form-text">Подходящие типы: text, textarea, wysiwyg, markdown.</div>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-12">
                        <label class="form-label">Поле для <code>&lt;enclosure&gt;</code> (превью)</label>
                        <select id="rubricRssImageField" class="form-select form-select-sm">
                            <option value="">— не указано —</option>
                        </select>
                        <div class="form-text">Подходящие типы: image, gallery (берётся первое изображение).</div>
                    </div>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-12">
                        <label class="form-label">Поле для <code>&lt;category&gt;</code></label>
                        <select id="rubricRssCategoryField" class="form-select form-select-sm">
                            <option value="">— не указано —</option>
                        </select>
                        <div class="form-text">Подходящий тип: tags. Каждый тег документа = отдельный <code>&lt;category&gt;</code>.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnRubricRssSave">
                    <i class="bi bi-check-circle me-1"></i>Сохранить
                </button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalRubricApi" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="bi bi-braces me-2"></i>JSON API</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="form-text mb-3">
                    Когда API включён - рубрика и её документы доступны через
                    <code>GET /api/v1/rubrics/<span id="rubricApiAlias"></span>/documents</code>
                    (формат пути зависит от настройки <em>API → URL-префикс</em>).
                    В JSON попадают только поля с включённой опцией «API» на странице полей рубрики.
                </div>

                <input type="hidden" id="rubricApiId">

                <div class="form-check form-switch mb-3">
                    <input class="form-check-input" type="checkbox" id="rubricApiEnabled" value="1">
                    <label class="form-check-label" for="rubricApiEnabled">JSON API включён для этой рубрики</label>
                </div>

                <div class="row g-2 mb-2">
                    <div class="col-md-6">
                        <label class="form-label">Лимит по умолчанию</label>
                        <input type="number" id="rubricApiDefaultLimit" class="form-control form-control-sm"
                            min="1" max="1000" placeholder="из глобальных настроек">
                        <div class="form-text">Сколько документов на странице, если клиент не передал <code>per_page</code>.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Максимум на страницу</label>
                        <input type="number" id="rubricApiMaxLimit" class="form-control form-control-sm"
                            min="1" max="1000" placeholder="из глобальных настроек">
                        <div class="form-text">Жёсткий потолок: больше клиент не получит даже если попросит.</div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnRubricApiSave">
                    <i class="bi bi-check-circle me-1"></i>Сохранить
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.3/Sortable.min.js"></script>
<script>
window.ZentraConfig = {
    orderUrl:   '{{ route('admin.rubrics.order') }}',
    saveAllUrl: '{{ route('admin.rubrics.save-all') }}',
    baseUrl:    '{{ url('admin/rubrics') }}',
    canEdit:    {{ ($canEdit ?? false) ? 'true' : 'false' }},
    seoUrlTpl:  '{{ url('admin/rubrics/0/seo') }}',
    rssUrlTpl:  '{{ url('admin/rubrics/0/rss') }}',
    apiUrlTpl:  '{{ url('admin/rubrics/0/api') }}',
    fieldsMetaUrlTpl: '{{ url('admin/rubrics/0/fields-meta') }}',
    publicUrl:  '{{ rtrim(config('app.url'), '/') }}',
};
</script>
<script src="{{ route('admin.asset', 'js/rubrics-index.js') }}"></script>
@endpush
