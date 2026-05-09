@extends('install.layout')
@php $stepIcon = 'bi-check-circle'; @endphp

@section('card-title', 'Установка завершена')

@section('card-body')
<div class="ztr-install-success">
    <div class="icon"><i class="bi bi-check-circle-fill"></i></div>
    <h3>Zentra CMF успешно установлена!</h3>
    <p>Система готова к работе. Войдите в панель управления, чтобы начать настройку сайта.</p>

    <div class="links">
        <a href="/" class="btn btn-sm btn-secondary">
            <i class="bi bi-house me-1"></i> Перейти на сайт
        </a>
        <a href="/admin" class="btn btn-sm btn-primary">
            <i class="bi bi-speedometer2 me-1"></i> Панель управления
        </a>
    </div>
</div>
@endsection

@section('footer-left')@endsection
@section('footer-right')@endsection
