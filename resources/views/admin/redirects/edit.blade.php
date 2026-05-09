@extends('admin.layout')

@section('title', $redirect ? 'Редирект: ' . $redirect->from_url : 'Создать редирект')

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/redirects.css') }}">
@endpush

@section('content')
<div class="ztr-page-title">
    <a href="{{ route('admin.redirects.index') }}" class="btn btn-sm btn-secondary me-2">
        <i class="bi bi-arrow-left"></i>
    </a>
    <i class="bi bi-signpost-split me-2"></i>{{ $redirect ? 'Редактирование редиректа' : 'Новый редирект' }}
</div>

@php
    $canEdit   = $canEdit   ?? true;
    $canDelete = $canDelete ?? false;
    $readonly  = $redirect && ! $canEdit;
@endphp

@if($readonly)
<div class="alert alert-warning py-2 mb-3 small">
    <i class="bi bi-eye me-1"></i>Режим только просмотр - у вас нет прав на редактирование редиректов.
</div>
@endif

<form id="redirectForm"
    data-store-url="{{ route('admin.redirects.store') }}"
    data-update-url="{{ $redirect ? route('admin.redirects.update', $redirect) : '' }}"
    data-index-url="{{ route('admin.redirects.index') }}"
    data-method="{{ $redirect ? 'PUT' : 'POST' }}">
    @csrf

    <fieldset @disabled($readonly) class="border-0 p-0 m-0">

        <div class="card mb-3">
            <div class="card-body d-flex align-items-center gap-4 flex-wrap">
                <div class="form-check form-switch mb-0">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActive"
                        value="1" @checked($defaults['is_active'])>
                    <label class="form-check-label" for="isActive">Активный</label>
                </div>
                @if($redirect)
                <div class="ztr-redirect-stats d-flex gap-3 text-muted small mb-0">
                    <span>Срабатываний: <strong class="text-body">{{ number_format($redirect->hits, 0, '.', ' ') }}</strong></span>
                    <span>Последнее: <strong class="text-body">{{ $redirect->last_hit_at ? $redirect->last_hit_at->format('d.m.Y H:i') : '—' }}</strong></span>
                    <span>
                        @if($redirect->is_wildcard)
                            <span class="badge bg-info">wildcard</span>
                        @else
                            <span class="badge bg-secondary">точный</span>
                        @endif
                    </span>
                </div>
                @endif
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Адреса</div>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Исходный URL <span class="text-danger">*</span></label>
                    <input type="text" name="from_url" id="fromUrl" class="form-control"
                        value="{{ $defaults['from_url'] }}"
                        placeholder="/old-page" required maxlength="500">
                    <div class="form-text">
                        Относительный путь от корня сайта. Поддерживаются wildcards со звёздочкой <code>*</code>.<br>
                        Примеры: <code>/old-news/article-1</code>, <code>/catalog/*</code>, <code>/news/*/old-suffix</code>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Целевой URL <span class="text-danger">*</span></label>
                    <input type="text" name="to_url" id="toUrl" class="form-control"
                        value="{{ $defaults['to_url'] }}"
                        placeholder="/new-page" required maxlength="500">
                    <div class="form-text">
                        Внутренний путь (<code>/new-page</code>) или абсолютный URL (<code>https://example.com/x</code>).
                        В wildcards - используйте подстановки <code>$1</code>, <code>$2</code>, … для захваченных групп.
                    </div>
                </div>

                <div class="alert alert-warning py-2 mb-0 ztr-redirect-cycle-alert d-none" id="cycleAlert">
                    <i class="bi bi-exclamation-triangle me-1"></i>
                    <span id="cycleAlertText"></span>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Параметры</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Тип редиректа</label>
                        <select name="type" class="form-select">
                            @foreach([
                                301 => '301 Moved Permanently (постоянный)',
                                302 => '302 Found (временный)',
                            ] as $val => $label)
                            <option value="{{ $val }}" @selected((int)$defaults['type'] === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="form-text">Для SEO-оптимизации, как правило, используется 301 редирект.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Приоритет</label>
                        <input type="number" name="priority" class="form-control"
                            value="{{ (int) $defaults['priority'] }}" min="-1000" max="1000">
                        <div class="form-text">
                            Используется при wildcards: чем больше число, тем раньше будет проверен.
                            Точные совпадения всегда имеют преимущество.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Дата истечения</label>
                        <input type="datetime-local" name="expires_at" class="form-control"
                            value="{{ $defaults['expires_at'] ? \Carbon\Carbon::parse($defaults['expires_at'])->format('Y-m-d\TH:i') : '' }}">
                        <div class="form-text">Если задана, то после этой даты редирект автоматически перестанет работать. При пустом значении - без срока действия.</div>
                    </div>

                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="preserve_query_string" id="preserveQs"
                                value="1" @checked($defaults['preserve_query_string'])>
                            <label class="form-check-label" for="preserveQs">
                                Передавать query-параметры
                            </label>
                            <div class="form-text">
                                Если у пользователя был <code>?utm_source=foo</code> - он добавится к новому URL.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header">Заметка</div>
            <div class="card-body">
                <textarea name="note" class="form-control" rows="3" maxlength="2000"
                    placeholder="Например: «Старая структура каталога после редизайна апрель 2025»">{{ $defaults['note'] }}</textarea>
                <div class="form-text">Только для внутреннего использования. На сайте нигде не отображается.</div>
            </div>
        </div>

    </fieldset>

    
    <div class="ztr-redirect-form-actions">
        @if($canEdit)
        <button type="submit" class="btn btn-sm btn-primary" id="btnSave">
            <i class="bi bi-check-circle me-1"></i>{{ $redirect ? 'Сохранить' : 'Создать' }}
        </button>
        @endif
        <a href="{{ route('admin.redirects.index') }}" class="btn btn-sm btn-secondary">{{ $canEdit ? 'Отмена' : 'К списку' }}</a>

        @if($redirect && $canDelete)
        <button type="button" class="btn btn-sm btn-outline-danger" id="btnDelete"
            data-url="{{ route('admin.redirects.destroy', $redirect) }}">
            <i class="bi bi-trash me-1"></i>Удалить редирект
        </button>
        @endif
    </div>
</form>

@if($redirect && $canDelete)
<div class="modal fade" id="deleteRedirectModal" tabindex="-1">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title"><i class="bi bi-trash me-2"></i>Удаление редиректа</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-1">Удалить редирект <code>{{ $redirect->from_url }}</code> → <code>{{ $redirect->to_url }}</code>?</p>
                <p class="mb-0 text-muted small">Это действие необратимо.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteConfirm">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script src="{{ route('admin.asset', 'js/redirects-edit.js') }}"></script>
@endpush
