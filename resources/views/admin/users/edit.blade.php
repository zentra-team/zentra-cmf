@extends('admin.layout')

@section('title', 'Редактирование пользователя - ' . $user->email)

@section('content')
<div class="ztr-page-title"><i class="bi bi-people me-2"></i>{{ $isNewUser ? 'Новый пользователь' : 'Редактирование пользователя' }}</div>

<div class="d-flex gap-2 mb-3">
    <a href="{{ route('admin.users.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>К списку
    </a>
    @if($canEdit)
    <div class="ms-auto d-flex align-items-center gap-2">
        <span id="saveStatus" class="ztr-save-status"></span>
        <button type="button" class="btn btn-sm btn-primary" id="btnSave">
            <i class="bi bi-floppy me-1"></i>Сохранить
        </button>
    </div>
    @endif
</div>

@if($isNewUser && $canEdit)
<div class="ztr-alert-info mb-3">
    <i class="bi bi-exclamation-triangle-fill ztr-alert-icon"></i>
    <div>
        Пользователь создан. Укажите пароль вручную или нажмите <strong>«Выслать пароль»</strong> - система сгенерирует пароль и отправит его на <strong>{{ $user->email }}</strong>.
    </div>
</div>
@endif

@unless($canEdit)
<div class="ztr-alert-info mb-3">
    <i class="bi bi-eye ztr-alert-icon"></i>
    <div>Просмотр без права редактирования. Данные пользователя изменить нельзя.</div>
</div>
@endunless

@if($canEdit)
<form id="userForm">
<input type="hidden" name="is_new_user" value="{{ $isNewUser ? '1' : '0' }}">
@endif

<div class="row g-3">

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">Основные данные</h6>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Имя</label>
                        <input type="text" name="first_name" class="form-control" value="{{ $user->first_name }}"
                            {{ $canEdit ? '' : 'readonly' }}>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Фамилия</label>
                        <input type="text" name="last_name" class="form-control" value="{{ $user->last_name }}"
                            {{ $canEdit ? '' : 'readonly' }}>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">E-mail <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" value="{{ $user->email }}"
                            {{ $canEdit ? 'required' : 'readonly' }}>
                    </div>

                    @if($canEdit)
                    <div class="col-md-4">
                        <label class="form-label">
                            {{ $isNewUser ? 'Пароль' : 'Новый пароль' }}
                            @if($isNewUser)<span class="text-danger">*</span>@endif
                        </label>
                        <div class="input-group">
                            <input type="password" name="password" id="passwordField" class="form-control"
                                placeholder="{{ $isNewUser ? 'Минимум 8 символов' : 'Оставьте пустым - без изменений' }}"
                                autocomplete="new-password"
                                {{ $isNewUser ? 'required' : '' }}>
                            <button type="button" class="btn btn-sm btn-secondary" id="btnShowPassword" tabindex="-1">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                        <div class="form-text">Минимум 8 символов</div>
                    </div>

                    <div class="col-md-4" id="passwordConfirmWrap">
                        <label class="form-label">
                            Подтверждение пароля
                            @if($isNewUser)<span class="text-danger">*</span>@endif
                        </label>
                        <input type="password" name="password_confirmation" id="passwordConfirmField"
                            class="form-control" placeholder="Повторите пароль"
                            autocomplete="new-password"
                            {{ $isNewUser ? 'required' : '' }}>
                    </div>

                    <div class="col-md-4 d-flex flex-column">
                        <label class="form-label ztr-spacer-label">_</label>
                        <div class="d-flex align-items-center gap-3 ztr-spacer-row">
                        <button type="button" class="btn btn-sm btn-secondary" id="btnSendPassword"
                            title="Сгенерировать случайный пароль и отправить на email пользователя">
                            <i class="bi bi-envelope me-1"></i>Выслать пароль
                        </button>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_active" id="chkActive"
                                value="1" {{ $user->is_active ? 'checked' : '' }}>
                            <label class="form-check-label ztr-group-label" for="chkActive">Активен</label>
                        </div>
                        </div>
                    </div>
                    @else
                    <div class="col-md-4 d-flex flex-column">
                        <label class="form-label ztr-spacer-label">_</label>
                        <div class="d-flex align-items-center gap-3 ztr-spacer-row">
                            <span class="badge {{ $user->is_active ? 'bg-success' : 'bg-danger' }}">
                                {{ $user->is_active ? 'Активен' : 'Заблокирован' }}
                            </span>
                        </div>
                    </div>
                    @endif

                    @if($user->last_login_at || $user->last_login_ip)
                    <div class="col-12">
                        <p class="mb-0 text-muted ztr-last-login">
                            Последний вход: {{ $user->last_login_at?->format($dateFormat.' '.$timeFormat) ?? '—' }}
                            @if($user->last_login_ip)· IP: {{ $user->last_login_ip }}@endif
                            · Зарегистрирован: {{ $user->created_at->format($dateFormat) }}
                        </p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">Группы</h6>
                @if($canEdit || $canGroups)
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Основная группа <span class="text-danger">*</span></label>
                        <select name="group_id" class="form-select" {{ $canEdit ? 'required' : 'disabled' }}>
                            @foreach($groups as $group)
                            <option value="{{ $group->id }}" {{ $user->group_id == $group->id ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                            @endforeach
                        </select>
                        @unless($canEdit)
                        <div class="form-text">Только для просмотра</div>
                        @endunless
                    </div>
                    @if($canEdit)
                    <div class="col-md-8">
                        <label class="form-label">Дополнительные группы</label>
                        <div class="d-flex flex-wrap gap-3 pt-1">
                            @foreach($groups as $group)
                            <div class="form-check">
                                <input class="form-check-input additional-group-check" type="checkbox"
                                    name="additional_groups[]"
                                    id="addGroup{{ $group->id }}"
                                    value="{{ $group->id }}"
                                    {{ in_array($group->id, $user->additional_groups ?? []) ? 'checked' : '' }}>
                                <label class="form-check-label ztr-group-label" for="addGroup{{ $group->id }}">
                                    {{ $group->name }}
                                </label>
                            </div>
                            @endforeach
                        </div>
                        <div class="form-text">Пользователь получит права всех выбранных групп</div>
                    </div>
                    @endif
                </div>
                @else
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label">Основная группа</label>
                        <p class="form-control-plaintext">{{ $user->group?->name ?? '—' }}</p>
                    </div>
                    @if(!empty($user->additional_groups))
                    <div class="col-md-8">
                        <label class="form-label">Дополнительные группы</label>
                        <p class="form-control-plaintext">
                            @foreach($groups->whereIn('id', $user->additional_groups) as $g)
                                {{ $g->name }}@if(!$loop->last), @endif
                            @endforeach
                        </p>
                    </div>
                    @endif
                </div>
                @endif
            </div>
        </div>
    </div>

</div>

@if($canEdit)
<div class="d-flex justify-content-end mt-3">
    <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('btnSave').click()">
        <i class="bi bi-floppy me-1"></i>Сохранить
    </button>
</div>
</form>
@endif

@endsection

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/users.css') }}">
@endpush

@push('scripts')
<script>
window.ZentraConfig = {
    saveUrl:    '{{ route('admin.users.update', $user) }}',
    sendPwdUrl: '{{ route('admin.users.send-password', $user) }}',
    isNewUser:  {{ $isNewUser ? 'true' : 'false' }},
    canEdit:    {{ $canEdit   ? 'true' : 'false' }},
};
</script>
<script src="{{ route('admin.asset', 'js/users-edit.js') }}"></script>
@endpush
