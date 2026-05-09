@extends('admin.layout')

@section('title', 'Права группы - ' . $userGroup->name)

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/user-groups-edit.css') }}">
<link rel="stylesheet" href="{{ route('admin.asset', 'css/user-groups.css') }}">
@endpush

@section('content')
<div class="ztr-page-title"><i class="bi bi-shield-check me-2"></i>Права группы - {{ $userGroup->name }}</div>

<div class="d-flex gap-2 mb-3">
    <a href="{{ route('admin.user-groups.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>К списку групп
    </a>
    @if(! $userGroup->is_system && $canEdit)
    <div class="ms-auto d-flex align-items-center gap-2">
        <span id="saveStatus" class="ztr-ge-desc"></span>
        <button type="button" class="btn btn-sm btn-primary" id="btnSave" disabled>
            <i class="bi bi-floppy me-1"></i>Сохранить основные
        </button>
    </div>
    @endif
</div>

@if($userGroup->is_system)
<div class="ztr-alert-info mb-3">
    <i class="bi bi-shield-lock-fill ztr-alert-icon"></i>
    <div>
        <div class="fw-semibold mb-1">Это системная группа</div>
        Группа <strong>{{ $userGroup->name }}</strong> создаётся при установке системы и имеет полный доступ ко всем разделам панели управления. Её название и права изменить нельзя - это защита от случайной потери доступа администратора. Все пользователи этой группы автоматически получают абсолютно все права - текущие и те, которые появятся в системе в будущем.
    </div>
</div>
@endif

<form id="groupForm">

<div class="card mb-3">
    <div class="card-body">
        <h6 class="card-title mb-3">Основные настройки</h6>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Название <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ $userGroup->name }}" required
                    {{ ($userGroup->is_system || ! $canEdit) ? 'readonly' : '' }}>
            </div>
            <div class="col-md-6">
                <label class="form-label">Описание</label>
                <input type="text" name="description" class="form-control" maxlength="500"
                    value="{{ $userGroup->description }}"
                    placeholder="Например: модераторы новостей и блога"
                    {{ ($userGroup->is_system || ! $canEdit) ? 'readonly' : '' }}>
            </div>
            @if(! $userGroup->is_system)
            <div class="col-12">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="isDefaultToggle" name="is_default" value="1"
                        {{ $userGroup->is_default ? 'checked' : '' }}
                        {{ $canEdit ? '' : 'disabled' }}>
                    <label class="form-check-label" for="isDefaultToggle">
                        Группа по умолчанию для новых пользователей
                    </label>
                    <div class="form-text">
                        При создании нового администратора эта группа будет предложена в выпадающем списке. Только одна группа может быть «по умолчанию».
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<div class="d-flex align-items-center justify-content-between mb-2">
    <h6 class="mb-0 ztr-ge-perm-title">Права доступа</h6>
    <div class="d-flex gap-1">
        <button type="button" class="btn btn-sm btn-outline-secondary ztr-ge-perm-badge" id="btnExpandAll">
            <i class="bi bi-chevron-expand me-1"></i>Развернуть все
        </button>
        <button type="button" class="btn btn-sm btn-outline-secondary ztr-ge-perm-badge" id="btnCollapseAll">
            <i class="bi bi-chevron-contract me-1"></i>Свернуть все
        </button>
    </div>
</div>

@php $currentPerms = $userGroup->permissions ?? []; @endphp

@foreach($sections as $sectionKey => $section)
<div class="perm-section">
    <div class="perm-section-header collapsed" data-bs-toggle="collapse" data-bs-target="#perm-{{ $sectionKey }}">
        <i class="bi bi-chevron-down ztr-ge-perm-chevron"></i>
        {{ $section['label'] }}
        @php
            $sectionPermKeys = array_keys($section['permissions']);
            $checkedCount    = $userGroup->is_system ? count($sectionPermKeys) : count(array_intersect($currentPerms, $sectionPermKeys));
        @endphp
        @if($checkedCount > 0)
        <span class="badge bg-purple ms-1 ztr-ge-perm-badge-active">
            {{ $checkedCount }}/{{ count($sectionPermKeys) }}
        </span>
        @endif
    </div>
    <div class="collapse perm-section-body" id="perm-{{ $sectionKey }}">
        @foreach($section['permissions'] as $permKey => $permLabel)
        <label class="perm-check">
            <input type="checkbox" class="form-check-input perm-checkbox"
                name="permissions[]" value="{{ $permKey }}"
                {{ ($userGroup->is_system || in_array($permKey, $currentPerms)) ? 'checked' : '' }}
                {{ ($userGroup->is_system || ! $canEdit) ? 'disabled' : '' }}>
            {{ $permLabel }}
        </label>
        @endforeach
    </div>
</div>
@endforeach

@if(! $userGroup->is_system && $canEdit)
<div class="d-flex align-items-center justify-content-end gap-2 mt-3">
    <span id="savePermsStatus" class="ztr-ge-perm-desc"></span>
    <button type="button" class="btn btn-sm btn-primary" id="btnSavePerms" disabled>
        <i class="bi bi-floppy me-1"></i>Сохранить права
    </button>
</div>
@endif
</form>
@endsection

@push('scripts')
<script>
window.ZentraConfig = {
    saveInfoUrl:  '{{ route('admin.user-groups.update-info', $userGroup) }}',
    savePermsUrl: '{{ route('admin.user-groups.update-permissions', $userGroup) }}',
    isSystem:     {{ $userGroup->is_system ? 'true' : 'false' }}
};
</script>
<script src="{{ route('admin.asset', 'js/user-groups-edit.js') }}"></script>
@endpush
