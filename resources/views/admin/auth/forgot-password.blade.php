@extends('admin.auth.layout')

@section('title', 'Восстановление пароля')
@section('icon', 'bi-envelope-open')
@section('card-title', 'Восстановление пароля')

@section('card-body')
<p style="font-size:.875rem;color:var(--ztr-text-muted);margin-bottom:1.25rem">
    Введите email вашей учётной записи. Если он зарегистрирован в системе - вы получите письмо со ссылкой для сброса пароля.
</p>

<form method="POST" action="{{ route('admin.password.email') }}" novalidate>
    @csrf

    <div class="mb-4">
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

    <button type="submit" class="btn btn-sm btn-primary w-100">
        <i class="bi bi-send me-1"></i> Отправить ссылку
    </button>
</form>

<div class="mt-3 text-center">
    <a href="{{ route('admin.login') }}" style="font-size:.8125rem;color:var(--ztr-text-muted)">
        <i class="bi bi-arrow-left me-1"></i>Вернуться к входу
    </a>
</div>

<div class="mt-4 p-3" style="background:var(--ztr-bg-input);border:1px solid var(--ztr-border);border-radius:var(--ztr-radius-sm);font-size:.8rem;color:var(--ztr-text-muted)">
    <i class="bi bi-terminal me-1"></i>
    Если отправка письма невозможна, используйте CLI-команду на сервере:<br>
    <code style="color:var(--ztr-purple-400)">php artisan admin:reset-password &lt;email&gt;</code>
</div>
@endsection
