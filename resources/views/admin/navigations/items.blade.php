@extends('admin.layout')

@section('title', 'Пункты меню - ' . $navigation->title)

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/navigations-items.css') }}">

@endpush

@section('content')
<div class="ztr-page-title"><i class="bi bi-list-nested me-2"></i>Пункты меню - {{ $navigation->title }}</div>

<div class="d-flex align-items-center gap-2 mb-3">
    <a href="{{ route('admin.navigations.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>К списку
    </a>
    @if($canEdit)
    <button type="button" class="btn btn-sm btn-primary ms-auto" id="btnAddItem">
        <i class="bi bi-plus-circle me-1"></i>Добавить пункт меню
    </button>
    @else
    <span class="ms-auto"></span>
    @endif
    <a href="{{ route('admin.navigations.template', $navigation) }}" class="btn btn-sm btn-success">
        <i class="bi bi-file-code me-1"></i>Редактировать шаблон
    </a>
    <button type="button" class="btn btn-sm btn-secondary" id="btnExpandAll">Раскрыть все</button>
    <button type="button" class="btn btn-sm btn-secondary" id="btnCollapseAll">Свернуть все</button>
</div>

<div class="ztr-alert-info mb-3 d-flex align-items-center gap-3 flex-wrap ztr-nav-info-alert">
    <span class="d-flex align-items-center gap-1 ztr-nav-info-alert-label">
        <i class="bi bi-info-circle-fill ztr-alert-icon"></i>
        Максимум 3 уровня меню:
    </span>
    <span class="d-flex align-items-center gap-2 ztr-nav-info-alert-label">
        <span class="badge ztr-nav-level-badge">L1</span> Корневой
        <span class="ztr-nav-level-sep">→</span>
        <span class="badge ztr-nav-level-badge ztr-nav-level-badge-l2">L2</span> Подменю
        <span class="ztr-nav-level-sep">→</span>
        <span class="badge ztr-nav-level-badge ztr-nav-level-badge-l3">L3</span> Подподменю
    </span>
    <span class="d-none d-md-block ztr-nav-level-sep">·</span>
    <span class="ztr-nav-info-hint">Перетаскивание глубже блокируется автоматически</span>
</div>

<div class="card">
    <div class="card-body">
        @if($items->isEmpty())
        <div class="text-center py-4 text-muted" id="emptyState">
            <i class="bi bi-list-nested ztr-nav-empty-icon"></i>
            Нет ни одного пункта меню.
            <a href="#" id="addFirstItem">Добавить первый</a>
        </div>
        @endif

        <ul class="nav-tree-list" id="navTreeRoot" data-parent-id="">
            @foreach($items as $item)
                @include('admin.navigations._item', ['item' => $item])
            @endforeach
        </ul>
    </div>
</div>

<div class="modal fade" id="itemModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title" id="itemModalTitle">Добавить пункт меню</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="itemId" value="">
                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Название <span class="text-danger">*</span></label>
                        <input type="text" id="itemTitle" class="form-control" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Родительский пункт</label>
                        <select id="itemParentId" class="form-select">
                            <option value="">— Корневой уровень —</option>

                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Открывать</label>
                        <select id="itemTarget" class="form-select">
                            <option value="_self">В текущем окне</option>
                            <option value="_blank">В новом окне</option>
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Ссылка</label>
                        <div class="position-relative">
                            <input type="text" id="itemUrl" class="form-control"
                                placeholder="https://example.com/page или введите название документа для поиска">
                            <div id="docSearchDropdown" class="doc-search-dropdown" style="display:none"></div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">CSS класс</label>
                        <input type="text" id="itemClass" class="form-control" placeholder="active">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">CSS ID</label>
                        <input type="text" id="itemCssId" class="form-control" placeholder="menu-item-1">
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Inline стили</label>
                        <input type="text" id="itemStyle" class="form-control" placeholder="color:red">
                    </div>
                    <div class="col-12">
                        <label class="form-label">Иконка (HTML)</label>
                        <input type="text" id="itemIcon" class="form-control" placeholder='<i class="fas fa-home me-2"></i>'>
                        <div class="form-text">HTML-код иконки. Используйте тег <code>[link:icon]</code> в шаблоне меню для вывода.</div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Произвольный HTML <span class="text-muted ztr-nav-label-hint">[link:html]</span></label>
                        <textarea id="itemExtraHtml" class="form-control font-monospace ztr-nav-extra-html-input" rows="2"
                                  placeholder='<hr class="my-divider">'></textarea>
                        <div class="form-text">Выводится в шаблоне через тег <code>[link:html]</code>. Только для этого пункта.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Описание</label>
                        <textarea id="itemDescription" class="form-control" rows="2"></textarea>
                        <div class="form-text">Описание может быть добавлено в атрибут <code>title</code> у ссылки через тег <code>[link:title]</code></div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Изображение</label>
                        <div class="d-flex gap-2 align-items-start">
                            <div class="flex-grow-1">
                                <input type="text" id="itemImage" class="form-control" placeholder="/uploads/media/...">
                            </div>
                            <div class="d-flex flex-column align-items-center gap-1">
                                <button type="button" class="btn btn-sm btn-secondary ztr-nav-upload-btn" id="btnImageUpload"
                                    title="Загрузить изображение с компьютера">
                                    <i class="bi bi-upload me-1"></i>Загрузить
                                </button>
                                <input type="file" id="imageFileInput" accept="image/jpeg,image/png,image/gif,image/webp,image/svg+xml" class="d-none">
                            </div>
                            <div id="imagePreviewWrap" class="ztr-nav-img-preview-wrap">
                                <img id="imagePreview" src="" alt="preview" class="ztr-nav-img-preview">
                                <button type="button" id="btnImageClear" class="ztr-nav-img-clear" title="Удалить изображение">×</button>
                            </div>
                        </div>
                        <div id="imageUploadStatus" class="form-text mt-1"></div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-primary btn-sm" id="btnItemSave">
                    <i class="bi bi-floppy me-1"></i>Сохранить
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="deleteItemModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Удаление пункта</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Удалить пункт <strong id="deleteItemName"></strong>?</p>
                <p class="mb-0 text-muted ztr-nav-modal-hint">
                    Дочерние пункты станут корневыми (не удаляются).
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteItemConfirm">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script>
    window.ZentraConfig = {
        csrf: document.querySelector('meta[name="csrf-token"]').content,
        navId: {{ $navigation->id }},
        imageUploadUrl: '{{ route('admin.upload.image') }}',
        imageDestroyUrl: '{{ route('admin.upload.image.destroy') }}',
        docSearchUrl: '{{ route('admin.navigations.doc-search') }}',
        canEdit: {{ $canEdit ? 'true' : 'false' }},
        canDelete: {{ $canDelete ? 'true' : 'false' }}
    };
</script>
<script src="{{ route('admin.asset', 'js/navigations-items.js') }}"></script>
@endpush
