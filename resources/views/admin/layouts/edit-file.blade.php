@extends('admin.layout')

@section('title', 'Редактирование файла')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/layouts-edit-file.css') }}">
@endpush

@section('content')
<div class="file-editor-wrap">

    <div class="file-editor-topbar">
        <a href="{{ route('admin.layouts.index', ['tab' => $type]) }}"
            class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Макеты
        </a>
        <span class="text-muted ztr-lef-path">
            <i class="bi bi-filetype-{{ $type }} me-1"></i>
            public/assets/{{ $type }}/{{ $relPath }}
        </span>
        <div class="ms-auto d-flex gap-2">
            @if($canFiles)
            @if($type === 'css')
            <button type="button" class="btn btn-sm btn-secondary" id="btnFormat" title="Форматировать">
                <i class="bi bi-magic"></i>
            </button>
            @endif
            <button type="button" class="btn btn-primary btn-sm" id="btnSave">
                <i class="bi bi-floppy me-1"></i>Сохранить
            </button>
            @else
            <span class="badge bg-secondary">Только просмотр</span>
            @endif
        </div>
    </div>

    <div class="file-editor-label">{{ strtoupper($type) }} файл</div>

    <div id="aceFileEditor"></div>

    @if($canFiles)
    <form id="saveForm" method="POST"
        action="{{ route('admin.layouts.asset.update', [$type, $relPath]) }}"
        class="ztr-lef-save-form">
        @csrf
        <textarea name="content" id="hiddenContent"></textarea>
    </form>
    @endif

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.32.8/src-min-noconflict/ace.js"></script>
<script>
    window.ZentraConfig = {
        fileType: @json($type),
        content: @json($content),
        canFiles: {{ $canFiles ? 'true' : 'false' }},
    };
</script>
<script src="{{ route('admin.asset', 'js/layouts-edit-file.js') }}"></script>
@endpush
