@extends('install.layout')
@php $stepIcon = 'bi-cpu'; @endphp

@section('card-title', 'Проверка системных требований')

@section('card-body')
<p class="text-muted mb-3" style="font-size:.875rem">
    Проверка совместимости сервера с требованиями Zentra CMF.
</p>

<table class="table table-sm mb-0">
    <thead>
        <tr>
            <th>Компонент</th>
            <th>Требуется</th>
            <th>Обнаружено</th>
            <th style="width:80px">Статус</th>
        </tr>
    </thead>
    <tbody>
        @foreach($checks as $check)
        <tr class="ztr-req-row">
            <td>{{ $check['name'] }}</td>
            <td>{{ $check['required'] }}</td>
            <td>{{ $check['actual'] }}</td>
            <td>
                @if($check['pass'])
                    <span class="ztr-req-status-ok"><i class="bi bi-check-circle-fill"></i> OK</span>
                @elseif($check['optional'])
                    <span class="ztr-req-status-optional" title="Необязательно"><i class="bi bi-dash-circle"></i> —</span>
                @else
                    <span class="ztr-req-status-fail"><i class="bi bi-x-circle-fill"></i> Нет</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>

@if(!$allPassed)
<div class="alert alert-danger mt-3 mb-0">
    <i class="bi bi-exclamation-triangle-fill me-2"></i>
    Устраните выделенные проблемы и обновите страницу для повторной проверки.
</div>
@else
<div class="alert alert-success mt-3 mb-0">
    <i class="bi bi-check-circle-fill me-2"></i>
    Все обязательные требования выполнены. Можно продолжить.
</div>
@endif

@php
    $pgDumpCheck = collect($checks)->firstWhere('name', 'pg_dump (резервные копии)');
@endphp
@if($pgDumpCheck && !$pgDumpCheck['pass'])
<div class="alert alert-warning mt-3 mb-0">
    <div class="d-flex align-items-start gap-2">
        <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
        <div>
            <strong>pg_dump не установлен</strong><br>
            Утилита <code>pg_dump</code> (часть пакета <code>postgresql-client</code>) не найдена на сервере.
            Без неё создание резервных копий базы данных через интерфейс администратора будет недоступно.<br>
            <span class="text-muted" style="font-size:.8125rem">
                Установка: <code>apt-get install postgresql-client</code> (Debian/Ubuntu) или <code>yum install postgresql</code> (RHEL/CentOS).
                Это требование необязательно - установку можно продолжить.
            </span>
        </div>
    </div>
</div>
@endif
@endsection

@section('footer-left')
    <span class="text-muted" style="font-size:.8rem">Шаг 2 из {{ $totalSteps - 1 }}</span>
@endsection

@section('footer-right')
    <a href="/install/step/1" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i> Назад
    </a>

    @if($allPassed)
        <form method="POST" action="/install/step/2">
            @csrf
            <button type="submit" class="btn btn-sm btn-primary">
                Продолжить <i class="bi bi-arrow-right ms-1"></i>
            </button>
        </form>
    @else
        <a href="/install/step/2" class="btn btn-sm btn-secondary">
            <i class="bi bi-arrow-clockwise me-1"></i> Обновить
        </a>
    @endif
@endsection
