@extends('admin.layout')

@section('title', 'Шаблон рубрики - ' . $rubric->title)

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/rubrics-template.css') }}">

@endpush

@section('content')
<div class="rubric-tpl-wrap">

    <div class="rubric-tpl-topbar">
        <a href="{{ route('admin.rubrics.fields', $rubric) }}" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-left me-1"></i>Поля
        </a>
        <div class="rubric-tpl-topbar-title">
            <i class="bi bi-file-code me-1"></i><strong>{{ $rubric->title }}</strong> - шаблон рубрики
        </div>
        <div class="ms-auto d-flex align-items-center gap-2">
            <span id="saveStatus" class="rubric-tpl-save-status"></span>
            @if($canEdit ?? false)
            <button type="button" class="btn btn-sm btn-primary" id="btnSave">
                <i class="bi bi-floppy me-1"></i>Сохранить шаблон
            </button>
            @endif
        </div>
    </div>

    @if(!($canEdit ?? false))
    <div class="alert alert-danger py-2 mb-0 ztr-readonly-banner">
        <i class="bi bi-eye me-1"></i>Режим только просмотр - у вас нет прав на редактирование шаблона
    </div>
    @endif

    <div class="rubric-tpl-label">HTML код шаблона рубрики</div>

    <div class="rubric-tpl-body">

        <div class="rubric-tags-panel" id="tagsPanel">

            @if(!empty($tags['system']))
            <div class="rubric-tags-group-header collapsed" data-bs-toggle="collapse" data-bs-target="#tgSystem">
                Системные <i class="bi bi-chevron-down"></i>
            </div>
            <div class="rubric-tags-list collapse" id="tgSystem">
                @foreach($tags['system'] as $tag)
                <span class="rubric-tag-item" data-tag="{{ $tag }}">{{ $tag }}</span>
                @endforeach
            </div>
            @endif

            @if($fields->isNotEmpty())
            <div class="rubric-tags-group-header" data-bs-toggle="collapse" data-bs-target="#tgFields">
                Поля рубрики <i class="bi bi-chevron-down"></i>
            </div>
            <div class="rubric-tags-list collapse show" id="tgFields">
                @foreach($fields as $field)
                @php $tplInfo = $field->fieldInstance()?->getTemplateInfo(); @endphp
                <div class="rubric-tag-row">
                    <span class="rubric-tag-item flex-grow-1" data-tag="[field:{{ $field->alias }}]">
                        [field:{{ $field->alias }}]<small>{{ $field->title }} · {{ $field->typeName() }}</small>
                    </span>
                    @if($tplInfo)
                    <button type="button" class="rubric-tag-template"
                        data-tag="{{ str_replace('{alias}', $field->alias, $tplInfo['default']) }}"
                        title="{!! $tplInfo['hint'] !!}"
                        data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="left">
                        <i class="bi bi-code-slash"></i>
                    </button>
                    @endif
                    <button type="button" class="rubric-tag-by-id"
                        data-tag="[field:{{ $field->id }}]"
                        title="Тег по ID: [field:{{ $field->id }}] - нажмите, чтобы вставить. Переживёт переименование алиаса."
                        data-bs-toggle="tooltip" data-bs-placement="right">
                        <i class="bi bi-hash"></i>
                    </button>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($tags['navigation']))
            <div class="rubric-tags-group-header collapsed" data-bs-toggle="collapse" data-bs-target="#tgNav">
                Навигация <i class="bi bi-chevron-down"></i>
            </div>
            <div class="rubric-tags-list collapse" id="tgNav">
                @foreach($tags['navigation'] as $item)
                <div class="rubric-tag-row">
                    <span class="rubric-tag-item flex-grow-1" data-tag="{{ $item['tag'] }}">{{ $item['tag'] }}</span>
                    <button type="button" class="rubric-tag-info" title="{{ $item['title'] }}" data-bs-toggle="tooltip" data-bs-placement="left"><i class="bi bi-question-circle"></i></button>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($tags['blocks']))
            <div class="rubric-tags-group-header collapsed" data-bs-toggle="collapse" data-bs-target="#tgBlocks">
                Блоки <i class="bi bi-chevron-down"></i>
            </div>
            <div class="rubric-tags-list collapse" id="tgBlocks">
                @foreach($tags['blocks'] as $item)
                <div class="rubric-tag-row">
                    <span class="rubric-tag-item flex-grow-1" data-tag="{{ $item['tag'] }}">{{ $item['tag'] }}</span>
                    <button type="button" class="rubric-tag-info" title="{{ $item['title'] }}" data-bs-toggle="tooltip" data-bs-placement="left"><i class="bi bi-question-circle"></i></button>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($tags['children']))
            <div class="rubric-tags-group-header collapsed" data-bs-toggle="collapse" data-bs-target="#tgChildren">
                <span><i class="bi bi-diagram-3 me-1"></i>Дочерние документы</span>
                <i class="bi bi-chevron-down"></i>
            </div>
            <div class="rubric-tags-list collapse" id="tgChildren">
                @foreach($tags['children'] as $item)
                @php $isInner = str_starts_with($item['title'], '↳'); @endphp
                <div class="rubric-tag-row{{ $isInner ? ' rubric-tag-row-inner' : '' }}">
                    <span class="rubric-tag-item flex-grow-1" data-tag="{{ $item['tag'] }}">{{ $item['tag'] }}</span>
                    <button type="button" class="rubric-tag-info" title="{{ $item['title'] }}" data-bs-toggle="tooltip" data-bs-placement="left"><i class="bi bi-question-circle"></i></button>
                </div>
                @endforeach
            </div>
            @endif

        </div>

        <div class="rubric-ace-wrap">
            <div id="aceEditor"></div>
        </div>

    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.32.8/src-min-noconflict/ace.js"></script>
<script>
window.ZentraConfig = {
    updateUrl: '{{ route('admin.rubrics.template.update', $rubric) }}',
    template:  @json($rubric->template),
    canEdit:   {{ ($canEdit ?? false) ? 'true' : 'false' }},
};
</script>
<script src="{{ route('admin.asset', 'js/rubrics-template.js') }}"></script>
@endpush
