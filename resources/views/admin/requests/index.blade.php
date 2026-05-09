@extends('admin.layout')

@section('title', 'Запросы')

@section('content')
<div class="ztr-page-title"><i class="bi bi-inbox me-2"></i>Запросы</div>

<div class="card">
    <div class="card-header">
        <ul class="nav nav-tabs card-header-tabs">
            <li class="nav-item">
                <a class="nav-link active" data-bs-toggle="tab" href="#tabList">
                    <i class="bi bi-list-ul me-1"></i>Список запросов
                </a>
            </li>
            @if($canCreate)
            <li class="nav-item">
                <a class="nav-link" data-bs-toggle="tab" href="#tabCreate" id="tabCreateLink">
                    <i class="bi bi-plus-circle me-1"></i>Создать запрос
                </a>
            </li>
            @endif
        </ul>
    </div>
    <div class="card-body tab-content">

        <div class="tab-pane fade show active" id="tabList">
            @if(! $canList)
            <div class="alert alert-warning py-2 mb-0 small">
                <i class="bi bi-eye-slash me-1"></i>У вас нет прав на просмотр списка запросов.
            </div>
            @elseif($requests->isEmpty())
            <p class="text-muted text-center py-3 mb-0">Запросов пока нет.</p>
            @else
            @php $hasActions = $canEdit || $canCreate || $canDelete; @endphp
            <table class="table table-sm align-middle mb-0">
                <thead>
                    <tr>
                        <th style="width:60px">ID</th>
                        <th>Название</th>
                        <th style="width:180px">Рубрика</th>
                        <th style="width:220px">Тег</th>
                        @if($hasActions)
                        <th style="width:120px;white-space:nowrap">Действия</th>
                        @endif
                    </tr>
                </thead>
                <tbody id="requestsTableBody">
                    @foreach($requests as $req)
                    <tr data-id="{{ $req->id }}">
                        <td class="text-muted">{{ $req->id }}</td>
                        <td>
                            @if($canEdit)
                            <a href="{{ route('admin.requests.edit', $req) }}">{{ $req->title }}</a>
                            @else
                            {{ $req->title }}
                            @endif
                            @if($req->description)
                            <div class="text-muted" style="font-size:.75rem">{{ Str::limit($req->description, 60) }}</div>
                            @endif
                        </td>
                        <td style="font-size:.82rem;color:var(--ztr-text-muted)">
                            {{ $req->rubrics()->pluck('title')->join(', ') ?: '—' }}
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                <code class="text-warning" style="font-size:.8rem">{{ $req->tag() }}</code>
                                <button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-copy-tag"
                                    data-tag="{{ $req->tag() }}" title="Скопировать тег">
                                    <i class="bi bi-copy" style="font-size:.8rem"></i>
                                </button>
                            </div>
                        </td>
                        @if($hasActions)
                        <td style="white-space:nowrap">
                            @if($canEdit)
                            <a href="{{ route('admin.requests.edit', $req) }}"
                               class="btn btn-sm btn-outline-success" title="Редактировать">
                                <i class="bi bi-pencil"></i>
                            </a>
                            @endif
                            @if($canCreate)
                            <button type="button" class="btn btn-sm btn-outline-success btn-req-copy"
                                data-id="{{ $req->id }}" title="Копировать">
                                <i class="bi bi-copy"></i>
                            </button>
                            @endif
                            @if($canDelete)
                            <button type="button" class="btn btn-sm btn-outline-danger btn-req-delete"
                                data-id="{{ $req->id }}" data-title="{{ $req->title }}" title="Удалить">
                                <i class="bi bi-trash"></i>
                            </button>
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
            @if($rubrics->isEmpty())
            <div class="ztr-alert-info d-flex align-items-start gap-2" style="max-width:540px">
                <i class="bi bi-exclamation-triangle-fill flex-shrink-0 mt-1"></i>
                <div>
                    Создание запроса невозможно без рубрики. Сначала
                    <a href="{{ route('admin.rubrics.index') }}" class="fw-semibold">создайте хотя бы одну рубрику</a>,
                    затем вернитесь сюда.
                </div>
            </div>
            @else
            <form method="POST" action="{{ route('admin.requests.store') }}" style="max-width:640px" id="createForm">
                @csrf
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Название <span class="text-danger">*</span></label>
                        <input type="text" name="title" id="createTitle"
                            class="form-control @error('title') is-invalid @enderror"
                            value="{{ old('title') }}" required autofocus>
                        @error('title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Алиас <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" name="alias" id="createAlias"
                                class="form-control @error('alias') is-invalid @enderror"
                                value="{{ old('alias') }}" required>
                            <code style="font-size:.72rem;color:var(--ztr-purple-400);white-space:nowrap" id="aliasPreview"></code>
                        </div>
                        @error('alias')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Рубрика <span class="text-danger">*</span></label>
                        <select name="rubric_id" id="createRubric" class="form-select" required>
                            <option value="">— Не выбрана —</option>
                            @foreach($rubrics as $rubric)
                            <option value="{{ $rubric->id }}" {{ old('rubric_id') == $rubric->id ? 'selected' : '' }}>
                                {{ $rubric->title }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Описание</label>
                        <input type="text" name="description" class="form-control"
                            value="{{ old('description') }}" placeholder="Для внутреннего использования">
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-sm" id="btnCreateSubmit"
                            {{ old('rubric_id') ? '' : 'disabled' }}>
                            <i class="bi bi-plus-circle me-1"></i>Создать запрос
                        </button>
                    </div>
                </div>
            </form>
            @endif
        </div>
        @endif

    </div>
</div>

<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h6 class="modal-title">Удаление запроса</h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">Удалить запрос <strong id="deleteTitle"></strong>?</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger btn-sm" id="btnDeleteConfirm">
                    <i class="bi bi-trash me-1"></i>Удалить
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
window.ZentraConfig = {
    hasErrors: {{ $errors->any() ? 'true' : 'false' }},
    errorsText: '{{ implode(' ', $errors->all()) }}'
};
</script>
<script src="{{ route('admin.asset', 'js/requests-index.js') }}"></script>
@endpush
