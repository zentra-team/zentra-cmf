<?php

return [
    'sections' => [
        'general' => [
            'label'       => 'Общее',
            'permissions' => [
                'all' => 'Разрешить все права',
            ],
        ],

        'documents' => [
            'label'       => 'Документы',
            'permissions' => [
                'documents.access' => 'Доступ к разделу',
                'documents.list'   => 'Просмотр списка документов',
                'documents.create' => 'Создание и копирование документов',
                'documents.edit'   => 'Редактирование документов и ревизий',
                'documents.delete' => 'Удаление документов',
            ],
        ],

        'rubrics' => [
            'label'       => 'Рубрики',
            'permissions' => [
                'rubrics.access'      => 'Доступ к разделу',
                'rubrics.list'        => 'Просмотр списка рубрик',
                'rubrics.create'      => 'Создание и копирование рубрик',
                'rubrics.edit'        => 'Редактирование рубрик, полей и шаблонов',
                'rubrics.delete'      => 'Удаление рубрик и полей',
                'rubrics.permissions' => 'Настройка прав доступа к документам рубрики',
            ],
        ],

        'requests' => [
            'label'       => 'Запросы',
            'permissions' => [
                'requests.access' => 'Доступ к разделу',
                'requests.list'   => 'Просмотр списка запросов',
                'requests.create' => 'Создание и копирование запросов',
                'requests.edit'   => 'Редактирование запросов',
                'requests.delete' => 'Удаление запросов',
            ],
        ],

        'blocks' => [
            'label'       => 'Блоки',
            'permissions' => [
                'blocks.access' => 'Доступ к разделу',
                'blocks.list'   => 'Просмотр списка блоков',
                'blocks.create' => 'Создание и копирование блоков',
                'blocks.edit'   => 'Редактирование блока',
                'blocks.delete' => 'Удаление блоков',
                'blocks.groups' => 'Управление группами (создание, переименование, удаление, порядок)',
            ],
        ],

        'layouts' => [
            'label'       => 'Макеты сайта',
            'permissions' => [
                'layouts.access' => 'Доступ к разделу',
                'layouts.list'   => 'Просмотр списка макетов и CSS/JS файлов',
                'layouts.edit'   => 'Редактирование HTML кода макета',
                'layouts.create' => 'Создание и копирование макетов',
                'layouts.delete' => 'Удаление макетов',
                'layouts.files'  => 'Управление CSS/JS файлами (создание, загрузка, редактирование, удаление)',
            ],
        ],

        'navigations' => [
            'label'       => 'Навигация',
            'permissions' => [
                'navigations.access' => 'Доступ к разделу',
                'navigations.list'   => 'Просмотр списка меню',
                'navigations.create' => 'Создание и копирование меню',
                'navigations.edit'   => 'Редактирование пунктов и шаблонов',
                'navigations.delete' => 'Удаление меню и пунктов',
            ],
        ],

        'redirects' => [
            'label'       => 'Редиректы',
            'permissions' => [
                'redirects.access'      => 'Доступ к разделу',
                'redirects.list'        => 'Просмотр списка редиректов',
                'redirects.create'      => 'Создание редиректов',
                'redirects.edit'        => 'Редактирование редиректов',
                'redirects.delete'      => 'Удаление редиректов',
                'redirects.misses_view' => 'Просмотр битых ссылок (404)',
            ],
        ],

        'api_tokens' => [
            'label'       => 'API-токены',
            'permissions' => [
                'api_tokens.access' => 'Доступ к разделу',
                'api_tokens.list'   => 'Просмотр списка токенов',
                'api_tokens.create' => 'Создание токенов',
                'api_tokens.edit'   => 'Редактирование токенов',
                'api_tokens.delete' => 'Удаление токенов',
            ],
        ],

        'users' => [
            'label'       => 'Пользователи',
            'permissions' => [
                'users.access' => 'Доступ к разделу',
                'users.list'   => 'Просмотр списка пользователей',
                'users.create' => 'Создание пользователей',
                'users.edit'   => 'Редактирование данных пользователя',
                'users.delete' => 'Удаление пользователей',
                'users.groups' => 'Смена группы пользователя',
            ],
        ],

        'groups' => [
            'label'       => 'Группы пользователей',
            'permissions' => [
                'groups.access' => 'Доступ к разделу',
                'groups.list'   => 'Просмотр списка групп',
                'groups.create' => 'Создание групп',
                'groups.edit'   => 'Редактирование группы (название и права)',
                'groups.delete' => 'Удаление групп',
            ],
        ],

        'modules' => [
            'label'       => 'Модули',
            'permissions' => [
                'modules.list'    => 'Просмотр',
                'modules.use'     => 'Использование (страницы модулей)',
                'modules.install' => 'Установка / Удаление',
            ],
        ],

        'database' => [
            'label'       => 'База данных',
            'permissions' => [
                'db.access'   => 'Доступ к разделу',
                'db.backup'   => 'Просмотр, создание и скачивание бэкапов',
                'db.restore'  => 'Восстановление из бэкапа',
                'db.optimize' => 'Обслуживание (VACUUM, ANALYZE, REINDEX)',
            ],
        ],

        'logs' => [
            'label'       => 'События',
            'permissions' => [
                'logs.access'        => 'Доступ к разделу',
                'logs.tab.admin'     => 'Таб «Действия пользователей»',
                'logs.tab.404'       => 'Таб «Ошибки 404»',
                'logs.tab.db'        => 'Таб «Ошибки PostgreSQL»',
                'logs.tab.framework' => 'Таб «Логи фреймворка»',
                'logs.export'        => 'Экспорт CSV / скачивание лог-файлов',
                'logs.clear'         => 'Очистка журналов',
            ],
        ],

        'settings' => [
            'label'       => 'Системные настройки',
            'permissions' => [
                'settings.access'       => 'Доступ к разделу',
                'settings.tab.general'  => 'Таб «Основные»',
                'settings.tab.env'      => 'Таб «Окружение»',
                'settings.tab.seo'      => 'Таб «SEO и коды»',
                'settings.tab.cache'    => 'Таб «Кэш и сессии»',
                'settings.tab.maps'     => 'Таб «Карты»',
                'settings.edit.general' => 'Сохранение в табе «Основные»',
                'settings.edit.env'     => 'Сохранение в табе «Окружение»',
                'settings.edit.seo'     => 'Сохранение в табе «SEO и коды»',
                'settings.edit.maps'    => 'Сохранение в табе «Карты»',
                'settings.manage'       => 'Обновление платформы',
            ],
        ],

        'cache' => [
            'label'       => 'Управление кешем',
            'permissions' => [
                'cache.clear' => 'Очистка кеша',
            ],
        ],
    ],
];
