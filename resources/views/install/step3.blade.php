@extends('install.layout')
@php $stepIcon = 'bi-database'; @endphp

@section('card-title', 'Подключение к базе данных')

@section('card-body')
<p class="text-muted mb-4" style="font-size:.875rem">
    Укажите адрес сайта и параметры подключения к <strong>PostgreSQL</strong>.
    Эти данные будут сохранены в файле <code>.env</code>.
</p>

<form method="POST" action="/install/step/3" id="dbForm">
    @csrf

    <div class="row g-3">

        <div class="col-12">
            <label class="form-label">
                Адрес сайта
                <span class="ztr-field-help" data-bs-toggle="tooltip"
                      title="Полный URL сайта с протоколом. Если у вас подключён SSL — укажите https://. Это влияет на все ссылки и редирект HTTP→HTTPS.">?</span>
            </label>
            <input type="url" name="app_url" class="form-control @error('app_url') is-invalid @enderror"
                   value="{{ old('app_url', $detectedUrl) }}" placeholder="https://example.com">
            @error('app_url')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="col-12"><hr class="my-1"></div>

        <div class="col-8">
            <label class="form-label">
                Хост
                <span class="ztr-field-help" data-bs-toggle="tooltip"
                      title="Для обычного подключения: 127.0.0.1. Для Docker - укажите имя сервиса из docker-compose.yml">?</span>
            </label>
            <input type="text" name="db_host" class="form-control @error('db_host') is-invalid @enderror"
                   value="{{ old('db_host', '127.0.0.1') }}" placeholder="127.0.0.1">
        </div>

        <div class="col-4">
            <label class="form-label">
                Порт
                <span class="ztr-field-help" data-bs-toggle="tooltip"
                      title="Стандартный порт PostgreSQL: 5432">?</span>
            </label>
            <input type="number" name="db_port" class="form-control @error('db_port') is-invalid @enderror"
                   value="{{ old('db_port', '5432') }}" placeholder="5432">
        </div>

        <div class="col-12">
            <label class="form-label">
                Имя базы данных
                <span class="ztr-field-help" data-bs-toggle="tooltip"
                      title="Название базы данных, которую будет использовать система">?</span>
            </label>
            <input type="text" name="db_name" class="form-control @error('db_name') is-invalid @enderror"
                   value="{{ old('db_name') }}" placeholder="zentra_db">
        </div>

        <div class="col-6">
            <label class="form-label">
                Имя пользователя
                <span class="ztr-field-help" data-bs-toggle="tooltip"
                      title="Пользователь PostgreSQL с правами на создание таблиц">?</span>
            </label>
            <input type="text" name="db_user" class="form-control @error('db_user') is-invalid @enderror"
                   value="{{ old('db_user') }}" placeholder="postgres" autocomplete="username">
        </div>

        <div class="col-6">
            <label class="form-label">Пароль</label>
            <input type="password" name="db_pass" class="form-control"
                   placeholder="(оставьте пустым, если не нужен)" autocomplete="current-password">
        </div>

        <div class="col-12 pt-1">
            <div class="form-check mb-2">
                <input class="form-check-input" type="checkbox" name="create_db" id="createDb" value="1"
                    {{ old('create_db') ? 'checked' : '' }}>
                <label class="form-check-label" for="createDb">
                    Создать базу данных, если не существует
                </label>
            </div>
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="clean_db" id="cleanDb" value="1"
                    {{ old('clean_db') ? 'checked' : '' }}>
                <label class="form-check-label" for="cleanDb">
                    <span class="text-warning">Очистить базу данных, если существует</span>
                    <small class="text-muted d-block">— удалит все таблицы и данные в указанной БД</small>
                </label>
            </div>
        </div>

    </div>
</form>
@endsection

@section('footer-left')
    <span class="text-muted" style="font-size:.8rem">Шаг 3 из {{ $totalSteps - 1 }}</span>
@endsection

@section('footer-right')
    <a href="/install/step/1" class="btn btn-sm btn-secondary">
        <i class="bi bi-x-lg me-1"></i> Отменить установку
    </a>
    <button type="submit" form="dbForm" class="btn btn-sm btn-primary">
        Продолжить <i class="bi bi-arrow-right ms-1"></i>
    </button>
@endsection

