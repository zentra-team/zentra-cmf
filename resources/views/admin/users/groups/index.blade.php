@extends('admin.layout')

@section('title', 'Группы пользователей')

@section('content')
<div class="ztr-page-title"><i class="bi bi-shield-check me-2"></i>Группы пользователей</div>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs" id="groupTabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tabList">
                    <i class="bi bi-people me-1"></i>Группы
                </a>
            </li>
            @if($canCreate)
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tabCreate">
                    <i class="bi bi-plus-circle me-1"></i>Создать группу
                </a>
            </li>
            @endif
        </ul>
    </div>
    <div class="card-body tab-content">

        <div class="tab-pane fade show active" id="tabList">
            @if(! $canList)
            <div class="alert alert-warning py-2 mb-0 small">
                <i class="bi bi-eye-slash me-1"></i>У вас нет прав на просмотр списка групп.
            </div>
            @elseif($groups->isEmpty())
            <p class="text-muted text-center py-3">Групп пока нет.</p>
            @else
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th class="ztr-gi-col-id">ID</th>
                        <th>Название</th>
                        <th class="ztr-gi-col-users">Пользователей</th>
                        @if($canEdit || $canCreate || $canDelete)
                        <th class="ztr-gi-col-actions">Действия</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                    <tr data-group-id="{{ $group->id }}">
                        <td class="text-muted">{{ $group->id }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2 flex-wrap">
                                @if($canEdit)
                                <a href="{{ route('admin.user-groups.edit', $group) }}">{{ $group->name }}</a>
                                @else
                                {{ $group->name }}
                                @endif
                                @if($group->is_default)
                                <span class="badge bg-info-subtle text-info" title="Группа по умолчанию для новых пользователей">
                                    <i class="bi bi-star-fill me-1"></i>По умолчанию
                                </span>
                                @endif
                            </div>
                            @if($group->description)
                            <div class="text-muted small mt-1">{{ $group->description }}</div>
                            @endif
                        </td>
                        <td class="ztr-gi-cell-center">{{ $group->users_count }}</td>
                        @if($canEdit || $canCreate || $canDelete)
                        <td class="ztr-gi-cell-actions">
                            @if($canEdit)
                            <a href="{{ route('admin.user-groups.edit', $group) }}"
                               class="btn btn-sm btn-outline-success" title="Редактировать">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @if($canCreate)
                            <button type="button" class="btn btn-sm btn-outline-success btn-group-duplicate"
                                data-group-id="{{ $group->id }}" data-group-name="{{ $group->name }}"
                                title="Дублировать группу">
                                <i class="bi bi-copy"></i>
                            </button>
                            @endif
                            @if($canDelete)
                                @if($group->is_system)
                                <span class="d-inline-block" tabindex="0"
                                      data-bs-toggle="tooltip" data-bs-placement="top"
                                      title="Системную группу нельзя удалить">
                                    <button type="button" class="btn btn-sm btn-outline-danger ztr-gi-btn-disabled" disabled>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </span>
                                @elseif($group->users_count > 0)
                                <span class="d-inline-block" tabindex="0"
                                      data-bs-toggle="tooltip" data-bs-placement="top"
                                      title="Нельзя удалить группу, пока в ней есть пользователи">
                                    <button type="button" class="btn btn-sm btn-outline-danger ztr-gi-btn-disabled" disabled>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </span>
                                @else
                                <button type="button" class="btn btn-sm btn-outline-danger btn-group-delete"
                                    data-group-id="{{ $group->id }}" data-group-name="{{ $group->name }}"
                                    title="Удалить группу">
                                    <i class="bi bi-trash"></i>
                                </button>
                                @endif
                            @endif
                        </td>
                        @endif
                    </tr>
                    @endforeach
                </tbody>
            </table>
            @endif
        </div>

        @if($canCreate)
        <div class="tab-pane fade" id="tabCreate">
            <form method="POST" action="{{ route('admin.user-groups.store') }}" class="ztr-gi-create-form">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Название группы <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                        value="{{ old('name') }}" required autofocus>
                    @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-plus-circle me-1"></i>Добавить
                </button>
            </form>
        </div>
        @endif

    </div>
</div>

<div class="modal fade" id="deleteGroupModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Удаление группы</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Удалить группу <strong id="deleteGroupName"></strong>?
                <p class="mb-0 mt-1 text-muted ztr-gi-modal-text">
                    Нельзя удалить, если в группе есть пользователи.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteGroupConfirm">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/user-groups.css') }}">
@endpush

@push('scripts')
<script>
window.ZentraConfig = {
    hasErrors: {{ $errors->any() ? 'true' : 'false' }}
};
</script>
<script src="{{ route('admin.asset', 'js/user-groups-index.js') }}"></script>
@endpush
