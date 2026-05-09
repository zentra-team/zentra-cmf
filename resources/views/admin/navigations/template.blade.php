@extends('admin.layout')

@section('title', 'Шаблон меню - ' . $navigation->title)

@push('styles')
<link rel="stylesheet" href="{{ route('admin.asset', 'css/navigations-template.css') }}">

@endpush

@section('content')
<div class="ztr-page-title">
    <i class="bi bi-list-nested me-2"></i>    Шаблон меню - {{ $navigation->title }}
</div>

<div class="d-flex gap-2 mb-3">
    <a href="{{ route('admin.navigations.index') }}" class="btn btn-sm btn-secondary">
        <i class="bi bi-arrow-left me-1"></i>К списку
    </a>
    <div class="ms-auto d-flex align-items-center gap-2">
        <span id="saveStatus" class="ztr-tpl-save-status"></span>
        <a href="{{ route('admin.navigations.items', $navigation) }}" class="btn btn-sm btn-success">
            <i class="bi bi-list-nested me-1"></i>Пункты меню
        </a>
        @if($canEdit)
        <button type="button" class="btn btn-sm btn-primary" id="btnSave">
            <i class="bi bi-floppy me-1"></i>Сохранить
        </button>
        @endif
    </div>
</div>

@if(!$canEdit)
<div class="alert alert-danger py-2 mb-3 ztr-readonly-banner">
    <i class="bi bi-eye me-1"></i>Режим только просмотр - у вас нет прав на редактирование шаблонов
</div>
@endif

<form id="tplForm">
<div class="row g-3 mb-4">

    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h6 class="card-title mb-3">Основные настройки</h6>
                @php $showGroups = $groups->isNotEmpty(); @endphp
                <div class="row g-3">
                    <div class="col-md-{{ $showGroups ? 5 : 7 }}">
                        <label class="form-label">Название <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="{{ $navigation->title }}" required>
                    </div>
                    <div class="col-md-{{ $showGroups ? 4 : 5 }}">
                        <label class="form-label">Алиас <span class="text-danger">*</span></label>
                        <div class="d-flex align-items-center gap-2">
                            <input type="text" name="alias" class="form-control" value="{{ $navigation->alias }}" required>
                            <div class="d-flex align-items-center gap-1">
                                <code class="text-warning ztr-tpl-tag-code">{{ $navigation->tag() }}</code>
                                <button type="button" class="btn btn-link btn-sm p-0 text-secondary btn-copy-tag"
                                    data-tag="{{ $navigation->tag() }}" title="Скопировать тег">
                                    <i class="bi bi-copy ztr-tpl-copy-icon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    @if($showGroups)
                    <div class="col-md-3">
                        <label class="form-label d-flex align-items-center gap-1">
                            Группы пользователей
                            <i class="bi bi-info-circle text-muted ztr-tpl-tooltip-icon"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="left"
                                title="Группы, которым видно это меню.<br>Если ничего не выбрано - меню видно всем.<br><br>Администраторы видят меню всегда независимо от этого списка.<br><br>Чтобы выбрать несколько групп - зажмите <b>Ctrl</b> (Windows) или <b>⌘ Cmd</b> (Mac) и кликайте по нужным.<br><br>Или используйте кнопки <b>«Все»</b> / <b>«Никто»</b>."></i>
                        </label>
                        <div class="d-flex gap-1 mb-1">
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 ztr-tpl-group-btn" id="btnSelectAllGroups">Все</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 ztr-tpl-group-btn" id="btnDeselectAllGroups">Никто</button>
                        </div>
                        <select name="allowed_groups[]" id="groupsSelect" class="form-select ztr-tpl-groups-select" multiple>
                            @foreach($groups as $group)
                            <option value="{{ $group->id }}"
                                {{ in_array($group->id, $navigation->allowed_groups ?? []) ? 'selected' : '' }}>
                                {{ $group->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

@php
$levels = [
    1 => ['label' => 'Родительский уровень', 'badge' => 'уровень 1'],
    2 => ['label' => 'Первый уровень вложенности', 'badge' => 'уровень 2'],
    3 => ['label' => 'Второй уровень вложенности', 'badge' => 'уровень 3'],
];
$wrapperTags = [
    ['val' => '[content]', 'hint' => 'Основной тег шаблона уровня. Вставляет на своё место отрисованный список всех пунктов - каждый оформляется шаблоном ссылки справа.<br><br>Без этого тега пункты меню не отобразятся вообще.<br><br><b>Пример:</b><br><code>&lt;ul class="nav"&gt;[content]&lt;/ul&gt;</code>'],
];

$linkTags = [
    ['val' => '[link:text]',
     'hint' => 'Название пункта меню - содержимое поля «Название». Экранируется для безопасного вывода в HTML.<br><br><b>В поле «Название» введено:</b><br><code>Главная</code><br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt;</code><br><br><b>Результат в HTML:</b><br><code>&lt;a href="/home"&gt;Главная&lt;/a&gt;</code>'],

    ['val' => '[link:icon]',
     'hint' => 'Выводит содержимое поля «Иконка (HTML)» конкретного пункта как сырой HTML - без экранирования. Если поле не заполнено - выводит пустую строку.<br><br><b>В поле «Иконка (HTML)» введено:</b><br><code>&lt;i class="bi bi-house"&gt;&lt;/i&gt;</code><br><br><b>Шаблон:</b><br><code>[link:icon] &lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt;</code><br><br><b>Результат в HTML:</b><br><code>&lt;i class="bi bi-house"&gt;&lt;/i&gt; &lt;a href="/home"&gt;Главная&lt;/a&gt;</code><br><br>Подходит для Bootstrap Icons (<code>bi bi-*</code>), FontAwesome и любого другого HTML.'],

    ['val' => '[link:url]',
     'hint' => 'URL-адрес ссылки - содержимое поля «Ссылка». Экранируется. Если поле не заполнено - выводит <code>#</code>.<br><br><b>В поле «Ссылка» введено:</b><br><code>/about</code><br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt;</code><br><br><b>Результат в HTML:</b><br><code>&lt;a href="/about"&gt;О нас&lt;/a&gt;</code><br><br>Если поле пустое:<br><code>&lt;a href="#"&gt;О нас&lt;/a&gt;</code>'],

    ['val' => '[link:target]',
     'hint' => 'Атрибут открытия ссылки. Тег уже содержит пробел перед атрибутом. При «В новом окне» выводит <code> target="_blank"</code>, при любом другом значении - пустую строку.<br><br><b>В настройках пункта выбрано:</b> В новом окне<br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]" [link:target]&gt;[link:text]&lt;/a&gt;</code><br><br><b>Результат (новое окно):</b><br><code>&lt;a href="/link" target="_blank"&gt;Текст&lt;/a&gt;</code><br><br><b>Результат (обычная ссылка):</b><br><code>&lt;a href="/link"&gt;Текст&lt;/a&gt;</code>'],

    ['val' => '[link:title]',
     'hint' => 'Содержимое поля «Описание». Экранируется. Используется как HTML-атрибут <code>title</code> - браузер показывает его как всплывающую подсказку при наведении курсора. Если поле не заполнено - пусто.<br><br><b>В поле «Описание» введено:</b><br><code>Перейти на страницу О нас</code><br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]" title="[link:title]"&gt;[link:text]&lt;/a&gt;</code><br><br><b>Результат в HTML:</b><br><code>&lt;a href="/about" title="Перейти на страницу О нас"&gt;О нас&lt;/a&gt;</code>'],

    ['val' => '[link:img]',
     'hint' => 'Путь к изображению пункта - содержимое поля «Изображение». Выводит <b>только путь до файла</b>, не полный тег <code>&lt;img&gt;</code>. Если поле не заполнено - пусто.<br><br><b>⚠️ Важно:</b> оборачивайте <code>&lt;img&gt;</code> в условие <code>[if:img]...[/if:img]</code>, иначе у пунктов без изображения останется пустой тег <code>&lt;img src&gt;</code>, который ломает вёрстку.<br><br><b>В поле «Изображение» введено:</b><br><code>/uploads/nav/logo.png</code><br><br><b>Правильный шаблон:</b><br><code>[if:img]&lt;img src="[link:img]" alt="[link:text]"&gt;[/if:img]</code><br><br><b>Результат в HTML:</b><br><code>&lt;img src="/uploads/nav/logo.png" alt="Главная"&gt;</code>'],

    ['val' => '[link:style]',
     'hint' => 'Inline CSS-стили - поле «Inline стили». Тег уже содержит пробел и атрибут <code>style="..."</code> целиком. Если поле не заполнено - выводит пустую строку, атрибут не добавляется.<br><br><b>В поле «Inline стили» введено:</b><br><code>color:red; font-weight:bold</code><br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]" [link:style]&gt;[link:text]&lt;/a&gt;</code><br><br><b>Результат в HTML:</b><br><code>&lt;a href="/link" style="color:red; font-weight:bold"&gt;Текст&lt;/a&gt;</code><br><br>Если поле пустое:<br><code>&lt;a href="/link"&gt;Текст&lt;/a&gt;</code>'],

    ['val' => '[link:id]',
     'hint' => 'CSS-идентификатор - поле «CSS ID». Тег уже содержит пробел и атрибут <code>id="..."</code> целиком. Если поле не заполнено - выводит пустую строку, атрибут не добавляется.<br><br><b>В поле «CSS ID» введено:</b><br><code>main-nav-link</code><br><br><b>Шаблон:</b><br><code>&lt;li [link:id]&gt;&lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt;&lt;/li&gt;</code><br><br><b>Результат в HTML:</b><br><code>&lt;li id="main-nav-link"&gt;&lt;a href="/link"&gt;Текст&lt;/a&gt;&lt;/li&gt;</code><br><br>Если поле пустое:<br><code>&lt;li&gt;&lt;a href="/link"&gt;Текст&lt;/a&gt;&lt;/li&gt;</code>'],

    ['val' => '[link:class]',
     'hint' => 'CSS-классы - поле «CSS класс». Тег уже содержит пробел перед значением и вставляется прямо внутрь существующего атрибута <code>class</code>. Если поле не заполнено - пусто.<br><br><b>В поле «CSS класс» введено:</b><br><code>featured highlight</code><br><br><b>Шаблон:</b><br><code>&lt;a class="nav-link [link:class]" href="[link:url]"&gt;[link:text]&lt;/a&gt;</code><br><br><b>Результат в HTML:</b><br><code>&lt;a class="nav-link featured highlight" href="/link"&gt;Текст&lt;/a&gt;</code><br><br>Если поле пустое:<br><code>&lt;a class="nav-link" href="/link"&gt;Текст&lt;/a&gt;</code>'],

    ['val' => '[link:active:class]',
     'hint' => 'Класс активного пункта. Выводит слово <code>active</code>, если URL пункта совпадает с текущим URL страницы. Иначе - пустую строку. Вставляется внутрь атрибута <code>class</code>.<br><br><b>Пользователь находится на странице</b> <code>/about</code><br><br><b>Шаблон:</b><br><code>&lt;a class="nav-link [link:active:class]" href="[link:url]"&gt;[link:text]&lt;/a&gt;</code><br><br><b>Результат (пункт «О нас», URL=/about - активен):</b><br><code>&lt;a class="nav-link active" href="/about"&gt;О нас&lt;/a&gt;</code><br><br><b>Результат (пункт «Главная», URL=/home - не активен):</b><br><code>&lt;a class="nav-link" href="/home"&gt;Главная&lt;/a&gt;</code>'],

    ['val' => '[link:pos]',
     'hint' => 'Порядковый номер пункта в списке, начиная с 1. Считается отдельно для каждого уровня меню.<br><br>Пункт является <b>третьим</b> в своём списке:<br><br><b>Шаблон:</b><br><code>&lt;span&gt;[link:pos].&lt;/span&gt; &lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt;</code><br><br><b>Результат в HTML:</b><br><code>&lt;span&gt;3.&lt;/span&gt; &lt;a href="/services"&gt;Услуги&lt;/a&gt;</code>'],

    ['val' => '[link:html]',
     'hint' => 'Произвольный HTML - поле «Произвольный HTML» конкретного пункта. Выводится как сырой HTML, без экранирования. Если поле не заполнено - выводит пустую строку.<br><br>Позволяет добавить уникальный элемент (разделитель, бейдж, иконку) только для одного пункта без изменения общего шаблона.<br><br><b>В поле «Произвольный HTML» введено:</b><br><code>&lt;span class="badge bg-danger ms-1"&gt;NEW&lt;/span&gt;</code><br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]"&gt;[link:text] [link:html]&lt;/a&gt;</code><br><br><b>Результат в HTML:</b><br><code>&lt;a href="/news"&gt;Новости &lt;span class="badge bg-danger ms-1"&gt;NEW&lt;/span&gt;&lt;/a&gt;</code>'],
];

$condTags = [
    ['val' => '[if:active]...[/if:active]',
     'hint' => 'Выводит содержимое только если URL пункта совпадает с URL открытой страницы. Полезно для визуального выделения активного пункта.<br><br><b>Пользователь на странице</b> <code>/about</code><br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]"&gt;[link:text] [if:active]★[/if:active]&lt;/a&gt;</code><br><br><b>Результат (пункт «О нас», URL=/about - активен):</b><br><code>&lt;a href="/about"&gt;О нас ★&lt;/a&gt;</code><br><br><b>Результат (пункт «Главная», URL=/home - не активен):</b><br><code>&lt;a href="/home"&gt;Главная &lt;/a&gt;</code>'],

    ['val' => '[if:not:active]...[/if:not:active]',
     'hint' => 'Выводит содержимое только если пункт НЕ активен - URL не совпадает с текущей страницей. Обратное к <code>[if:active]</code>.<br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]"&gt;[link:text][if:not:active] →[/if:not:active]&lt;/a&gt;</code><br><br><b>Результат (пункт активен):</b><br><code>&lt;a href="/about"&gt;О нас&lt;/a&gt;</code><br><br><b>Результат (пункт не активен):</b><br><code>&lt;a href="/home"&gt;Главная →&lt;/a&gt;</code>'],

    ['val' => '[if:first]...[/if:first]',
     'hint' => 'Выводит содержимое только для первого пункта в списке своего уровня.<br><br><b>Меню:</b> Главная (1), О нас (2), Контакты (3)<br><br><b>Шаблон:</b><br><code>[if:first]&lt;li class="first"&gt;[/if:first][if:not:first]&lt;li&gt;[/if:not:first]&lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt;&lt;/li&gt;</code><br><br><b>Результат для «Главная» (1-й):</b><br><code>&lt;li class="first"&gt;&lt;a href="/home"&gt;Главная&lt;/a&gt;&lt;/li&gt;</code><br><br><b>Результат для «О нас» (2-й):</b><br><code>&lt;li&gt;&lt;a href="/about"&gt;О нас&lt;/a&gt;&lt;/li&gt;</code>'],

    ['val' => '[if:not:first]...[/if:not:first]',
     'hint' => 'Выводит содержимое для всех пунктов кроме первого. Обратное к <code>[if:first]</code>. Удобно для добавления разделителя между пунктами.<br><br><b>Меню:</b> Главная (1), О нас (2), Контакты (3)<br><br><b>Шаблон:</b><br><code>[if:not:first]&lt;hr&gt;[/if:not:first]&lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt;</code><br><br><b>Результат для «Главная» (1-й):</b><br><code>&lt;a href="/home"&gt;Главная&lt;/a&gt;</code><br><br><b>Результат для «О нас» (2-й):</b><br><code>&lt;hr&gt;&lt;a href="/about"&gt;О нас&lt;/a&gt;</code>'],

    ['val' => '[if:last]...[/if:last]',
     'hint' => 'Выводит содержимое только для последнего пункта в списке своего уровня.<br><br><b>Меню:</b> Главная (1), О нас (2), Контакты (3)<br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt;[if:last] &lt;hr&gt;[/if:last]</code><br><br><b>Результат для «О нас» (2-й из 3-х):</b><br><code>&lt;a href="/about"&gt;О нас&lt;/a&gt;</code><br><br><b>Результат для «Контакты» (последний):</b><br><code>&lt;a href="/contacts"&gt;Контакты&lt;/a&gt; &lt;hr&gt;</code>'],

    ['val' => '[if:not:last]...[/if:not:last]',
     'hint' => 'Выводит содержимое для всех пунктов кроме последнего. Обратное к <code>[if:last]</code>. Удобно для разделителя после каждого пункта, кроме последнего.<br><br><b>Меню:</b> Главная (1), О нас (2), Контакты (3)<br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt;[if:not:last] |[/if:not:last]</code><br><br><b>Результат для «О нас» (2-й из 3-х):</b><br><code>&lt;a href="/about"&gt;О нас&lt;/a&gt; |</code><br><br><b>Результат для «Контакты» (последний):</b><br><code>&lt;a href="/contacts"&gt;Контакты&lt;/a&gt;</code>'],

    ['val' => '[if:every:N]...[/if:every:N]',
     'hint' => 'Выводит содержимое для каждого N-го пункта: 2-го, 4-го, 6-го... при N=2; 3-го, 6-го, 9-го... при N=3. Замените N на нужное число.<br><br><b>Меню из 5 пунктов, N=2:</b><br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt;[if:every:2]&lt;hr&gt;[/if:every:2]</code><br><br><b>Результат:</b><br>Пункт 1: <code>&lt;a&gt;...&lt;/a&gt;</code><br>Пункт 2: <code>&lt;a&gt;...&lt;/a&gt;&lt;hr&gt;</code><br>Пункт 3: <code>&lt;a&gt;...&lt;/a&gt;</code><br>Пункт 4: <code>&lt;a&gt;...&lt;/a&gt;&lt;hr&gt;</code><br>Пункт 5: <code>&lt;a&gt;...&lt;/a&gt;</code>'],

    ['val' => '[if:pos:N]...[/if:pos:N]',
     'hint' => 'Выводит содержимое только для пункта с порядковым номером N - ровно один раз. Замените N на нужный номер.<br><br><b>Меню из 4 пунктов, N=2:</b><br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt;[if:pos:2]&lt;hr&gt;[/if:pos:2]</code><br><br><b>Результат:</b><br>Пункт 1: <code>&lt;a&gt;...&lt;/a&gt;</code><br>Пункт 2: <code>&lt;a&gt;...&lt;/a&gt;&lt;hr&gt;</code><br>Пункт 3: <code>&lt;a&gt;...&lt;/a&gt;</code><br>Пункт 4: <code>&lt;a&gt;...&lt;/a&gt;</code>'],

    ['val' => '[if:children]...[/if:children]',
     'hint' => 'Выводит содержимое только если у пункта есть дочерние пункты (подменю). Удобно для добавления стрелки или индикатора раскрытия.<br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]"&gt;[link:text][if:children] ▶[/if:children]&lt;/a&gt;[level:2]</code><br><br><b>Результат (у пункта «Услуги» есть дочерние):</b><br><code>&lt;a href="/services"&gt;Услуги ▶&lt;/a&gt;&lt;ul&gt;...&lt;/ul&gt;</code><br><br><b>Результат (у пункта «О нас» нет дочерних):</b><br><code>&lt;a href="/about"&gt;О нас&lt;/a&gt;</code>'],

    ['val' => '[if:no:children]...[/if:no:children]',
     'hint' => 'Выводит содержимое только если у пункта НЕТ дочерних пунктов. Обратное к <code>[if:children]</code>.<br><br><b>Шаблон:</b><br><code>&lt;a href="[link:url]"&gt;[link:text][if:no:children] →[/if:no:children]&lt;/a&gt;</code><br><br><b>Результат (у пункта «О нас» нет дочерних - простая ссылка):</b><br><code>&lt;a href="/about"&gt;О нас →&lt;/a&gt;</code><br><br><b>Результат (у пункта «Услуги» есть дочерние - это dropdown, стрелка не нужна):</b><br><code>&lt;a href="/services"&gt;Услуги&lt;/a&gt;</code>'],

    ['val' => '[if:img]...[/if:img]',
     'hint' => 'Выводит содержимое только если у пункта заполнено поле «Изображение». Используйте для оборачивания тега <code>&lt;img&gt;</code>, чтобы он не рендерился пустым, когда изображение не задано.<br><br><b>У пункта указано изображение</b> <code>/uploads/nav/logo.png</code><br><br><b>Шаблон:</b><br><code>[if:img]&lt;img src="[link:img]" alt="[link:text]"&gt;[/if:img]&lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt;</code><br><br><b>Результат (изображение есть):</b><br><code>&lt;img src="/uploads/nav/logo.png" alt="Главная"&gt;&lt;a href="/"&gt;Главная&lt;/a&gt;</code><br><br><b>Результат (изображения нет):</b><br><code>&lt;a href="/"&gt;Главная&lt;/a&gt;</code>'],

    ['val' => '[if:no:img]...[/if:no:img]',
     'hint' => 'Выводит содержимое только если у пункта НЕ заполнено поле «Изображение». Обратное к <code>[if:img]</code>. Удобно для показа заглушки или альтернативного оформления.<br><br><b>Шаблон:</b><br><code>[if:img]&lt;img src="[link:img]"&gt;[/if:img][if:no:img]&lt;i class="bi bi-image"&gt;&lt;/i&gt;[/if:no:img]</code><br><br><b>Результат (изображение есть):</b><br><code>&lt;img src="/uploads/nav/logo.png"&gt;</code><br><br><b>Результат (изображения нет):</b><br><code>&lt;i class="bi bi-image"&gt;&lt;/i&gt;</code>'],
];

$levelSubTags = [
    1 => ['val' => '[level:2]',
          'hint' => 'Вставляет HTML дочерних пунктов текущего пункта, оформленных шаблоном уровня 2. Если дочерних нет - выводит пустую строку.<br><br><b>Пункт «Услуги» имеет дочерние:</b> Дизайн, Разработка<br><br><b>Шаблон (ссылки уровня 1):</b><br><code>&lt;li&gt;&lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt; [level:2]&lt;/li&gt;</code><br><br><b>Результат в HTML:</b><br><code>&lt;li&gt;&lt;a href="/services"&gt;Услуги&lt;/a&gt; &lt;ul class="dropdown-menu"&gt;&lt;li&gt;...&lt;/li&gt;&lt;/ul&gt;&lt;/li&gt;</code><br><br>Если дочерних нет:<br><code>&lt;li&gt;&lt;a href="/about"&gt;О нас&lt;/a&gt; &lt;/li&gt;</code>'],
    2 => ['val' => '[level:3]',
          'hint' => 'Вставляет HTML дочерних пунктов, оформленных шаблоном уровня 3. Все более глубокие уровни тоже получают оформление уровня 3. Если дочерних нет - пусто.<br><br><b>Пункт «Дизайн» имеет дочерние:</b> Логотипы, Баннеры<br><br><b>Шаблон (ссылки уровня 2):</b><br><code>&lt;li&gt;&lt;a href="[link:url]"&gt;[link:text]&lt;/a&gt; [level:3]&lt;/li&gt;</code><br><br><b>Результат в HTML:</b><br><code>&lt;li&gt;&lt;a href="/design"&gt;Дизайн&lt;/a&gt; &lt;ul class="dropdown-submenu"&gt;&lt;li&gt;...&lt;/li&gt;&lt;/ul&gt;&lt;/li&gt;</code><br><br>Если дочерних нет:<br><code>&lt;li&gt;&lt;a href="/logo"&gt;Логотипы&lt;/a&gt; &lt;/li&gt;</code>'],
    3 => null,
];
@endphp

@foreach($levels as $n => $level)
<div class="nav-tpl-section">
    <div class="nav-tpl-section-header" data-bs-toggle="collapse" data-bs-target="#tplLevel{{ $n }}">
        <i class="bi bi-chevron-down ztr-tpl-level-chevron"></i>
        {{ $level['label'] }}
        <span class="badge bg-secondary ms-1 ztr-tpl-level-badge">{{ $level['badge'] }}</span>
    </div>
    <div class="collapse show nav-tpl-section-body" id="tplLevel{{ $n }}">
        <div class="row g-3 align-items-stretch">

            <div class="col-md-6 d-flex flex-column">
                <label class="form-label ztr-tpl-label">
                    Шаблон уровня
                    <span class="text-muted fw-normal">— обёртка вокруг всех пунктов</span>
                </label>

                <div class="mb-1">
                    <button type="button" class="btn-tags-toggle" data-bs-toggle="collapse"
                        data-bs-target="#tags-tpl{{ $n }}">
                        <i class="bi bi-tags me-1"></i>Теги
                        <i class="bi bi-chevron-down toggle-icon"></i>
                    </button>
                    <div class="collapse" id="tags-tpl{{ $n }}">
                        <div class="tpl-tags">
                            @foreach($wrapperTags as $t)
                            <span class="tpl-tag" data-target="tpl{{ $n }}" data-val="{{ $t['val'] }}"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="auto"
                                title="{{ $t['hint'] }}"
                            >{{ $t['val'] }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="ace-tpl-editor" id="ace-tpl{{ $n }}"></div>
            </div>

            <div class="col-md-6 d-flex flex-column">
                <label class="form-label ztr-tpl-label">
                    Оформление ссылки
                    <span class="text-muted fw-normal">— шаблон одного пункта меню</span>
                </label>

                <div class="mb-1">
                    <button type="button" class="btn-tags-toggle" data-bs-toggle="collapse"
                        data-bs-target="#tags-link{{ $n }}">
                        <i class="bi bi-tags me-1"></i>Теги
                        <i class="bi bi-chevron-down toggle-icon"></i>
                    </button>
                    <div class="collapse" id="tags-link{{ $n }}">
                        @php $allLinkTags = $levelSubTags[$n] ? array_merge($linkTags, [$levelSubTags[$n]]) : $linkTags; @endphp
                        <div class="tpl-tags">
                            @foreach($allLinkTags as $t)
                            <span class="tpl-tag" data-target="link{{ $n }}" data-val="{{ $t['val'] }}"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="auto"
                                title="{{ $t['hint'] }}"
                            >{{ $t['val'] }}</span>
                            @endforeach
                        </div>
                        <div class="tpl-tags-sep"><span>УСЛОВИЯ</span></div>
                        <div class="tpl-tags">
                            @foreach($condTags as $t)
                            <span class="tpl-tag" data-target="link{{ $n }}" data-val="{{ $t['val'] }}"
                                data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="auto"
                                title="{{ $t['hint'] }}"
                            >{{ $t['val'] }}</span>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="ace-tpl-editor" id="ace-link{{ $n }}"></div>
            </div>

        </div>
    </div>
</div>
@endforeach

@if($canEdit)
<div class="d-flex justify-content-end mt-3">
    <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('btnSave').click()">
        <i class="bi bi-floppy me-1"></i>Сохранить
    </button>
</div>
@endif
</form>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/ace-builds@1.32.8/src-min-noconflict/ace.js"></script>
<script>
    window.ZentraConfig = {
        csrf: document.querySelector('meta[name="csrf-token"]').content,
        saveUrl: '{{ route('admin.navigations.template.update', $navigation) }}',
        canEdit: {{ $canEdit ? 'true' : 'false' }},
        templates: {
            tpl1:  @json($navigation->template_l1  ?? ''),
            link1: @json($navigation->link_tpl_l1  ?? ''),
            tpl2:  @json($navigation->template_l2  ?? ''),
            link2: @json($navigation->link_tpl_l2  ?? ''),
            tpl3:  @json($navigation->template_l3  ?? ''),
            link3: @json($navigation->link_tpl_l3  ?? ''),
        }
    };
</script>
<script src="{{ route('admin.asset', 'js/navigations-template.js') }}"></script>
@endpush
