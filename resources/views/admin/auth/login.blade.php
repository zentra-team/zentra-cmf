@extends('admin.auth.layout')

@section('title', 'Вход')
@section('icon', 'bi-shield-lock')
@section('card-title', 'Вход в систему')

@section('card-body')
<form method="POST" action="{{ route('admin.login.post') }}" novalidate>
    @csrf

    <div class="mb-3">
        <label for="email" class="form-label">Email</label>
        <input
            type="email"
            id="email"
            name="email"
            class="form-control"
            value="{{ old('email') }}"
            autofocus
            autocomplete="email"
            required
        >
    </div>

    <div class="mb-3">
        <label for="password" class="form-label">Пароль</label>
        <div class="input-group">
            <input
                type="password"
                id="password"
                name="password"
                class="form-control"
                autocomplete="current-password"
                required
            >
            <button type="button" class="btn btn-sm btn-secondary" id="btnTogglePass" tabindex="-1" title="Показать/скрыть пароль">
                <i class="bi bi-eye" id="iconTogglePass"></i>
            </button>
        </div>
    </div>

    <div class="mb-4 d-flex justify-content-between align-items-center">
        <div class="form-check mb-0">
            <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1">
            <label class="form-check-label" for="remember">Запомнить меня</label>
        </div>
        <a href="{{ route('admin.password.request') }}" class="text-muted" style="font-size:.8125rem">Забыли пароль?</a>
    </div>

    <button type="submit" class="btn btn-sm btn-primary w-100">
        <i class="bi bi-box-arrow-in-right me-1"></i> Войти
    </button>
</form>
@endsection

@section('card-footer')
<span style="font-size:.75rem;color:var(--ztr-text-dim)">Zentra CMF &mdash; Панель управления</span>
@endsection

