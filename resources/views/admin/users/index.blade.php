@extends('admin.layout')

@section('title', 'Пользователи')

@section('content')
<div class="ztr-page-title"><i class="bi bi-people me-2"></i>Пользователи</div>

@if(!$canList && !$canCreate)
<div class="ztr-alert-info">
    <i class="bi bi-shield-lock-fill ztr-alert-icon"></i>
    <div>У вашей группы нет прав на просмотр или управление пользователями.</div>
</div>
@else

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="userTabs">
            @if($canList)
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tabList">
                    <i class="bi bi-people me-1"></i>Пользователи
                </a>
            </li>
            @endif
            @if($canCreate)
            <li class="nav-item">
                <a class="nav-link {{ !$canList ? 'active' : '' }}" data-bs-toggle="tab" href="#tabCreate">
                    <i class="bi bi-plus-circle me-1"></i>Создать
                </a>
            </li>
            @endif
        </ul>
    </div>
    <div class="card-body tab-content p-0">

        @if($canList)
        <div class="tab-pane fade show active" id="tabList">

            <div class="border-bottom">
                <div class="d-flex align-items-center gap-2 px-3 py-2 ztr-search-toggle" id="searchToggle">
                    <i class="bi bi-search ztr-search-icon"></i>
                    <span class="ztr-search-label">Поиск и фильтры</span>
                    <i class="bi bi-chevron-down ms-auto toggle-icon"></i>
                </div>
                <div class="collapse {{ request()->hasAny(['search','group_id','status']) ? 'show' : '' }}" id="searchPanel">
                    <form method="GET" action="{{ route('admin.users.index') }}" class="px-3 pb-3">
                        <div class="row g-2 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label mb-1 ztr-search-label-sm">Поиск</label>
                                <input type="text" name="search" class="form-control form-control-sm"
                                    value="{{ request('search') }}" placeholder="Имя, Email, ID, домен...">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label mb-1 ztr-search-label-sm">Группа</label>
                                <select name="group_id" class="form-select form-select-sm">
                                    <option value="">— Все группы —</option>
                                    @foreach($groups as $group)
                                    <option value="{{ $group->id }}" {{ request('group_id') == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label mb-1 ztr-search-label-sm">Статус</label>
                                <select name="status" class="form-select form-select-sm">
                                    <option value="">— Все —</option>
                                    <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Активен</option>
                                    <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Заблокирован</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex gap-2">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <i class="bi bi-search me-1"></i>Найти
                                </button>
                                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary btn-sm">Сбросить</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="p-3">
                @if($users->isEmpty())
                <p class="text-muted text-center py-3 mb-0">Пользователи не найдены.</p>
                @else
                <form id="inlineGroupForm">
                <table class="table table-sm align-middle mb-2">
                    <thead>
                        <tr>
                            <th class="ztr-col-id">ID</th>
                            <th>Имя / Email</th>
                            <th class="ztr-col-group">Группа</th>
                            <th class="ztr-col-last-login">Последний вход</th>
                            <th class="ztr-col-created">Регистрация</th>
                            <th class="ztr-col-actions">Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                        <tr data-user-id="{{ $user->id }}">
                            <td class="text-muted">{{ $user->id }}</td>
                            <td>
                                <a href="{{ route('admin.users.edit', $user) }}" class="d-block">
                                    {{ $user->first_name || $user->last_name
                                        ? trim($user->first_name . ' ' . $user->last_name)
                                        : $user->name }}
                                </a>
                                <span class="ztr-cell-email">
                                    {{ $user->email }}
                                    @if($user->last_login_ip)
                                    · <span title="Последний IP">{{ $user->last_login_ip }}</span>
                                    @endif
                                </span>
                                @if(!$user->is_active)
                                <span class="badge bg-danger ms-1 ztr-badge-sm">заблокирован</span>
                                @endif
                            </td>
                            <td>
                                @if($canGroups)
                                <select class="form-select form-select-sm inline-group-select"
                                    data-user-id="{{ $user->id }}" data-original="{{ $user->group_id }}">
                                    @foreach($groups as $group)
                                    <option value="{{ $group->id }}" {{ $user->group_id == $group->id ? 'selected' : '' }}>
                                        {{ $group->name }}
                                    </option>
                                    @endforeach
                                </select>
                                @else
                                <span class="text-muted">{{ $user->group?->name ?? '—' }}</span>
                                @endif
                            </td>
                            <td class="ztr-cell-time">
                                {{ $user->last_login_at?->format($dateFormat.' '.$timeFormat) ?? '—' }}
                            </td>
                            <td class="ztr-cell-time">
                                {{ $user->created_at->format($dateFormat) }}
                            </td>
                            <td class="ztr-cell-nowrap">
                                @if($canEdit)
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="btn btn-sm btn-outline-success" title="Редактировать">
                                    <i class="bi bi-pencil"></i>
                                </a>
                                @else
                                <a href="{{ route('admin.users.edit', $user) }}"
                                   class="btn btn-sm btn-outline-secondary" title="Просмотр">
                                    <i class="bi bi-eye"></i>
                                </a>
                                @endif
                                @if($canDelete)
                                <button type="button" class="btn btn-sm btn-outline-danger btn-user-delete"
                                    data-user-id="{{ $user->id }}" data-user-name="{{ $user->name }}"
                                    title="Удалить">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                </form>
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        @if($canGroups)
                        <button type="button" class="btn btn-primary btn-sm" id="btnSaveGroups" style="display:none">
                            <i class="bi bi-floppy me-1"></i>Сохранить изменения групп
                        </button>
                        @endif
                    </div>
                    <div>{{ $users->links() }}</div>
                </div>
                @endif
            </div>
        </div>
        @endif

        @if($canCreate)
        <div class="tab-pane fade {{ !$canList ? 'show active' : '' }} p-3" id="tabCreate">
            <form method="POST" action="{{ route('admin.users.store') }}" class="ztr-create-form">
                @csrf
                <div class="mb-3">
                    <label class="form-label">E-mail <span class="text-danger">*</span></label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                        value="{{ old('email') }}" required autofocus placeholder="user@example.com">
                    <div class="form-text">Используется для входа в систему</div>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Добавить пользователя
                </button>
            </form>
        </div>
        @endif

    </div>
</div>

@if($canDelete)
<div class="modal fade" id="deleteUserModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Удаление пользователя</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Удалить пользователя <strong id="deleteUserName"></strong>?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteUserConfirm">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endif
@endsection

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/users.css') }}">
@endpush

@push('scripts')
<script>
window.ZentraConfig = {
    hasSearchParams: {{ request()->hasAny(['search','group_id','status']) ? 'true' : 'false' }},
    hasErrors: {{ $errors->any() ? 'true' : 'false' }},
    canList:   {{ $canList   ? 'true' : 'false' }},
    canCreate: {{ $canCreate ? 'true' : 'false' }},
    canDelete: {{ $canDelete ? 'true' : 'false' }},
    canGroups: {{ $canGroups ? 'true' : 'false' }},
};
</script>
<script src="{{ route('admin.asset', 'js/users-index.js') }}"></script>
@endpush
