@extends('install.layout')
@php $stepIcon = 'bi-person-circle'; @endphp

@section('card-title', 'Учётная запись администратора')

@section('card-body')
<p class="text-muted mb-4" style="font-size:.875rem">
    Создайте учётную запись суперадминистратора. Email будет использоваться для входа.
</p>

<form method="POST" action="/install/step/4" id="adminForm">
    @csrf

    <div class="row g-3">

        <div class="col-12">
            <label class="form-label">Имя пользователя</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                   value="{{ old('name', 'Администратор') }}" placeholder="Администратор">
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-12">
            <label class="form-label">Email <small class="text-muted">(используется для входа)</small></label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                   value="{{ old('email') }}" placeholder="admin@example.com" autocomplete="email">
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-6">
            <label class="form-label">Пароль</label>
            <input type="password" name="password" id="password"
                   class="form-control @error('password') is-invalid @enderror"
                   placeholder="Минимум 8 символов" autocomplete="new-password">
            @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>

        <div class="col-6">
            <label class="form-label">Подтверждение пароля</label>
            <input type="password" name="password_confirmation" id="passwordConfirm"
                   class="form-control" placeholder="Повторите пароль" autocomplete="new-password">
            <div class="invalid-feedback" id="confirmError" style="display:none">Пароли не совпадают</div>
        </div>

    </div>
</form>
@endsection

@section('footer-left')
    <span class="text-muted" style="font-size:.8rem">Шаг 4 из {{ $totalSteps - 1 }}</span>
@endsection

@section('footer-right')
    <button type="submit" form="adminForm" class="btn btn-sm btn-primary" id="btnSubmit">
        Завершить установку <i class="bi bi-check-lg ms-1"></i>
    </button>
@endsection

