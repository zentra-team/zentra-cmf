@extends('admin.layout')

@section('title', 'Редактирование макета')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/layouts-edit.css') }}">
@endpush

@section('content')
<div class="layout-editor-wrap">

    <div class="layout-editor-topbar">
        <a href="{{ route('admin.layouts.index') }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Макеты
        </a>
        @if($canEdit)
        <input type="text" id="layoutTitle" class="form-control form-control-sm layout-title-input"
            value="{{ $layout->title }}" placeholder="Название макета">
        @else
        <span class="text-muted layout-title-input">{{ $layout->title }}</span>
        @endif
        <div class="ms-auto d-flex align-items-center gap-2">
            @if($canEdit)
            <span id="saveStatus" class="ztr-le-save-status"></span>
            <button type="button" class="btn btn-sm btn-primary" id="btnSave">
                <i class="bi bi-floppy me-1"></i>Сохранить
            </button>
            @else
            <span class="badge bg-secondary">Только просмотр</span>
            @endif
        </div>
    </div>

    <div class="layout-editor-label">HTML код макета</div>

    <div class="layout-editor-body">

        <div class="layout-tags-panel" id="tagsPanel">
            <div class="ztr-le-tags-loading">
                <i class="bi bi-arrow-clockwise spin-on-load"></i> Загрузка тегов...
            </div>
        </div>

        <div class="layout-ace-wrap">
            <div id="aceEditor"></div>
        </div>

    </div>

    <div class="layout-html-bar">
        <span class="label">HTML:</span>
        @foreach(['OL','UL','LI','P','B','I','H1','H2','H3','H4','H5','DIV','SPAN','A','IMG','PRE','BR','TAB'] as $tag)
        <button type="button" class="btn btn-sm btn-secondary html-tag-btn" data-tag="{{ $tag }}">{{ $tag }}</button>
        @endforeach
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.32.8/src-min-noconflict/ace.js"></script>
<script>
    window.ZentraConfig = {
        layoutId: {{ $layout->id }},
        content: @json($layout->content),
        canEdit: {{ $canEdit ? 'true' : 'false' }},
    };
</script>
<script src="{{ route('admin.asset', 'js/layouts-edit.js') }}"></script>
@endpush
