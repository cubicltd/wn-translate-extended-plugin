<?php

return [
    'strings'     => [
        'plugin_desc'               => 'Добавляет определение языка браузера и языковые префиксы в URL для плагина Rainlab\'s Translate.',
        'plugin_short_desc'               => 'Расширяет плагин Translate',
        'settings_label'            => 'Translate Extended',
        'settings_desc'             => 'Настройки Translate Extended.',
        'browser_language_detection'           => 'Определение языка браузера',
        'browser_language_detection_comment'   => 'Позволяет отображать сайт в языке, предпочитаемом браузером.',
        'query_param' => 'Параметр запроса',
        'query_param_comment' => 'Если задан, язык определяется по этому параметру запроса.',
        'header' => 'Заголовок',
        'header_comment' => 'Если задан, язык определяется по этому HTTP-заголовку.',
        'route_prefixing'            => 'Языковые префиксы в URL',
        'route_prefixing_comment'    => 'Добавляет префикс языка в адреса страниц.',
        'prefer_user_session'           => 'Отдавать предпочтение сесси пользователя автоопределению языка браузера',
        'prefer_user_session_comment'   => 'Если включено, язык, установленный в сессии пользователя, будет иметь приоритет над языком браузера. Если выключено, язык будет определяться при каждом входе посетителя на сайт.',
        'homepage_redirect'             => 'Редирект главной страницы',
        'homepage_redirect_comment'     => 'Если включено, префикс языка будет добавлен в адрес главной страницы.',
        'force_prefix'                  => 'Принудительный языковой префикс',
        'force_prefix_comment'          => 'Если включено, все GET-запросы к адресам без кода языка будут получать языковой префикс.',
        'localepicker_desc'             => 'Показывает список ссылок для смены языка сайта.'
    ],
    'permissions' => [
        'tab'      => 'Translate Extended',
        'settings' => 'Настройки Translate Extended',
    ],
];
