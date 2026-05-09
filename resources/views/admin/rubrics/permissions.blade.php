@extends('admin.layout')

@section('title', 'Права доступа - ' . $rubric->title)

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/rubrics-permissions.css') }}">
@endpush

@section('content')
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <a href="{{ route('admin.rubrics.fields', $rubric) }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>Поля
    </a>
    <div class="ztr-page-title mb-0"><i class="bi bi-folder2 me-2"></i>{{ $rubric->title }} - права доступа</div>
</div>

<div class="card">
    <div class="card-header">Права доступа к документам рубрики</div>
    <div class="card-body p-0">
        <form method="POST" action="{{ route('admin.rubrics.permissions.update', $rubric) }}">
            @csrf
            @method('PUT')

            @if($groups->isEmpty())
                <p class="text-muted p-3 mb-0 ztr-rubrics-perms-empty">Группы пользователей не найдены.</p>
            @else
            <div class="table-responsive">
            <table class="table table-hover mb-0 ztr-rubrics-perms-table">
                <thead>
                    <tr>
                        <th class="ztr-rubrics-perms-col-group">Группа</th>
                        <th class="text-center ztr-rubrics-perms-col-checkbox">Просмотр</th>
                        <th class="text-center ztr-rubrics-perms-col-checkbox">Все права</th>
                        <th class="text-center ztr-rubrics-perms-col-create">Создавать с проверкой</th>
                        <th class="text-center ztr-rubrics-perms-col-create">Создавать без проверки</th>
                        <th class="text-center ztr-rubrics-perms-col-edit">Редакт. свои</th>
                        <th class="text-center ztr-rubrics-perms-col-edit">Редакт. все</th>
                        <th class="text-center ztr-rubrics-perms-col-revisions">Ревизии</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groups as $group)
                    @php $perm = $permissions->get($group->id) @endphp
                    <tr>
                        <td class="align-middle">
                            <div class="ztr-rubrics-perms-group-name">{{ $group->name }}</div>
                        </td>
                        @foreach(['can_view', 'can_all', 'can_create_moderated', 'can_create', 'can_edit_own', 'can_edit_all', 'can_revisions'] as $permKey)
                        <td class="text-center align-middle">
                            <input type="checkbox"
                                class="form-check-input perm-check"
                                name="perms[{{ $group->id }}][{{ $permKey }}]"
                                value="1"
                                {{ $perm?->$permKey ? 'checked' : '' }}
                                @if(!($canEdit ?? false)) disabled @endif>
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                </tbody>
            </table>
            </div>

            @if($canEdit ?? false)
            <div class="p-3 border-top">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="bi bi-floppy me-1"></i>Сохранить права
                </button>
            </div>
            @endif
            @endif
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ route('admin.asset', 'js/rubrics-permissions.js') }}"></script>
@endpush
