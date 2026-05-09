@extends('admin.auth.layout')

@section('title', 'Новый пароль')
@section('icon', 'bi-key')
@section('card-title', 'Новый пароль')

@section('card-body')
<form method="POST" action="{{ route('admin.password.update') }}" novalidate>
    @csrf

    <input type="hidden" name="token" value="{{ $token }}">

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            class="form-control"
            value="{{ old('email', $email) }}"
            autocomplete="email"
            required
        >
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Новый пароль</label>
        <div class="input-group">
            <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                autocomplete="new-password"
                autofocus
                required
            >
            <button type="button" class="btn btn-sm btn-secondary" id="btnTogglePass" tabindex="-1" title="Показать/скрыть пароль">
                <i class="bi bi-eye" id="iconTogglePass"></i>
            </button>
        </div>
        <div class="form-text">Минимум 8 символов</div>
    </div>

    <div class="mb-4">
        <label for="password_confirmation" class="form-label">Подтверждение пароля</label>
        <input
            type="password"
            id="password_confirmation"
            name="password_confirmation"
            class="form-control"
            autocomplete="new-password"
            required
        >
    </div>

    <button type="submit" class="btn btn-sm btn-primary w-100">
        <i class="bi bi-check-lg me-1"></i> Сохранить новый пароль
    </button>
</form>

<div class="mt-3 text-center">
    <a href="{{ route('admin.login') }}" style="font-size:.8125rem;color:var(--ztr-text-muted)">
        <i class="bi bi-arrow-left me-1"></i>Вернуться к входу
    </a>
</div>
@endsection

