@extends('admin.layout')

@section('title', $block ? 'Редактирование блока - ' . $block->title : 'Создать блок')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/blocks-edit.css') }}">

@endpush

@section('content')
@php
    $isEdit = (bool) $block;
    $wysiwyg = (bool) old('is_wysiwyg', $block?->is_wysiwyg);
    $canSave = $isEdit ? ($canEdit ?? false) : ($canCreate ?? false);
@endphp
<form id="blockForm">
@csrf
<div class="block-edit-wrap">

    <div class="block-edit-topbar">
        <a href="{{ route('admin.blocks.index') }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Блоки
        </a>
        <div class="block-edit-topbar-title">
            <i class="bi bi-puzzle me-1"></i>
            <strong>{{ $block ? $block->title : 'Новый блок' }}</strong>
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span id="saveStatus" class="block-edit-save-status"></span>
            @if($canSave)
            <button type="button" class="btn btn-sm btn-primary" id="btnSave">
                <i class="bi bi-floppy me-1"></i>{{ $isEdit ? 'Сохранить' : 'Создать блок' }}
            </button>
            @endif
        </div>
    </div>

    @if($isEdit && !($canEdit ?? false))
    <div class="alert alert-danger py-2 mb-0 ztr-readonly-banner">
        <i class="bi bi-eye me-1"></i>Режим только просмотр - у вас нет прав на редактирование блоков
    </div>
    @endif

    <div class="block-edit-settings">
        <div>
            <div class="form-label">Название <span class="text-danger">*</span></div>
            <input type="text" name="title" class="form-control form-control-sm block-edit-input-title"
                value="{{ old('title', $block?->title) }}" required>
        </div>
        <div>
            <div class="form-label">Алиас <span class="text-danger">*</span></div>
            <div class="d-flex align-items-center gap-1">
                <input type="text" name="alias" id="aliasInput" class="form-control form-control-sm block-edit-input-alias"
                    value="{{ old('alias', $block?->alias) }}"
                    @if($block) readonly @endif required>
                <code id="tagPreview" class="text-nowrap text-warning block-edit-tag-preview">[block:{{ $block?->alias ?? '' }}]</code>
            </div>
        </div>
        <div>
            <div class="form-label">Описание</div>
            <input type="text" name="description" class="form-control form-control-sm block-edit-input-desc"
                value="{{ old('description', $block?->description) }}">
        </div>
        <div>
            <div class="form-label">Группа</div>
            <select name="group_id" class="form-select form-select-sm block-edit-input-group">
                <option value="">— Без группы —</option>
                @foreach($groups as $g)
                    <option value="{{ $g->id }}" {{ (old('group_id', $block?->group_id) == $g->id) ? 'selected' : '' }}>
                        {{ $g->title }}
                    </option>
                @endforeach
            </select>
        </div>
        <div class="block-edit-type-badge">
            <div class="form-label">Тип</div>
            @if($wysiwyg)
            <span class="badge bg-success-subtle text-success border border-success-subtle"><i class="bi bi-type-bold me-1"></i>WYSIWYG</span>
            @else
            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle"><i class="bi bi-code-slash me-1"></i>Код</span>
            @endif
        </div>
    </div>

    <div class="block-edit-body">

        <div class="block-tags-panel">

            @if(!empty($tags['system']))
            <div class="block-tags-group-header" data-bs-toggle="collapse" data-bs-target="#tgSystem">
                Системные <i class="bi bi-chevron-down"></i>
            </div>
            <div class="block-tags-list collapse show" id="tgSystem">
                @foreach($tags['system'] as $item)
                <div class="block-tag-item" data-tag="{{ $item['tag'] }}">
                    <span class="text-warning">{{ $item['tag'] }}</span>
                    @if(!empty($item['hint']))
                    <i class="bi bi-info-circle block-tag-info" data-tag-key="{{ $item['tag'] }}"></i>
                    @endif
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($tags['blockTags']))
            <div class="block-tags-group-header" data-bs-toggle="collapse" data-bs-target="#tgBlocks">
                Блоки <i class="bi bi-chevron-down"></i>
            </div>
            <div class="block-tags-list collapse show" id="tgBlocks">
                @foreach($tags['blockTags'] as $item)
                <div class="block-tag-item" data-tag="{{ $item['tag'] }}">
                    <span class="text-warning">{{ $item['tag'] }}</span>
                    <i class="bi bi-info-circle block-tag-info" data-tag-key="{{ $item['tag'] }}"></i>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($tags['navigation']))
            <div class="block-tags-group-header" data-bs-toggle="collapse" data-bs-target="#tgNav">
                Навигация <i class="bi bi-chevron-down"></i>
            </div>
            <div class="block-tags-list collapse show" id="tgNav">
                @foreach($tags['navigation'] as $item)
                <div class="block-tag-item" data-tag="{{ $item['tag'] }}">
                    <span class="text-warning">{{ $item['tag'] }}</span>
                    <i class="bi bi-info-circle block-tag-info" data-tag-key="{{ $item['tag'] }}"></i>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($tags['moduleTags']))
            <div class="block-tags-group-header" data-bs-toggle="collapse" data-bs-target="#tgModules">
                Модули <i class="bi bi-chevron-down"></i>
            </div>
            <div class="block-tags-list collapse show" id="tgModules">
                @foreach($tags['moduleTags'] as $item)
                <div class="block-tag-item" data-tag="{{ $item['tag'] }}">
                    <span class="text-warning">{{ $item['tag'] }}</span>
                    @if(!empty($item['hint']) || !empty($item['title']))
                    <i class="bi bi-info-circle block-tag-info" data-tag-key="{{ $item['tag'] }}"></i>
                    @endif
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="block-editor-wrap" id="editorWrap">

            @if($wysiwyg)
            <div id="wysiwygWrap">
                <textarea id="tinymceEditor"></textarea>
            </div>
            @else
            <div id="aceEditorWrap" class="block-ace-wrap">
                <div id="aceEditor"></div>
            </div>

            <div class="block-html-tags" id="htmlTagsBar">
                @foreach(['<b>', '<i>', '<u>', '<s>', '<a href="">', '<p>', '<br>', '<ul>', '<li>', '<ol>', '<h2>', '<h3>', '<img src="">', '<div>', '<span>'] as $ht)
                <button type="button" class="btn-tag" data-html="{{ $ht }}">{{ $ht }}</button>
                @endforeach
            </div>
            @endif
        </div>

    </div>

</div>
<input type="hidden" name="content" id="contentInput" value="">
</form>
@endsection

@push('scripts')
@if($wysiwyg)
<script src="https://cdn.jsdelivr.net/npm/tinymce@7/tinymce.min.js" referrerpolicy="origin"></script>
@else
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.32.8/src-min-noconflict/ace.js"></script>
@endif
<script>
window.ZentraConfig = {
    isEdit:    {{ $isEdit ? 'true' : 'false' }},
    canSave:   {{ $canSave ? 'true' : 'false' }},
    updateUrl: '{{ $block ? route('admin.blocks.update', $block) : '' }}',
    storeUrl:  '{{ route('admin.blocks.store') }}',
    uploadUrl: '{{ route('admin.upload.image') }}',
    content:   @json($block?->content ?? ''),
    isWysiwyg: {{ $wysiwyg ? 'true' : 'false' }},
    tagHints:  @json(
        collect($tags)->flatten(1)->mapWithKeys(fn($item) => [
            $item['tag'] => isset($item['title']) && isset($item['hint'])
                ? '<b>' . e($item['title']) . '</b><br><br>' . $item['hint']
                : ($item['hint'] ?? ($item['title'] ?? null))
        ])->filter()->all()
    ),
};
</script>
<script src="{{ route('admin.asset', 'js/blocks-edit.js') }}"></script>
@endpush
