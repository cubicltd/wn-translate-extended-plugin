<?php

return [
    'strings'     => [
        'plugin_desc'               => 'Añade la detección del idioma del navegador y los prefijos de idioma en las rutas al plugin Translate de Winter.',
        'plugin_short_desc'         => 'Extiende el plugin Translate',
        'settings_label'            => 'Ajustes de Translate Extended',
        'settings_desc'             => 'Gestiona los ajustes de Translate Extended.',
        'browser_language_detection'           => 'Detección del idioma del navegador',
        'browser_language_detection_comment'   => 'Permite mostrar el sitio en el idioma preferido por el navegador.',
        'query_param' => 'Parámetro de consulta',
        'query_param_comment' => 'Si se indica, el idioma se detectará a partir de este parámetro de consulta.',
        'header' => 'Cabecera',
        'header_comment' => 'Si se indica, el idioma se detectará a partir de esta cabecera HTTP.',
        'route_prefixing'            => 'Prefijos en las rutas',
        'route_prefixing_comment'    => 'Permite usar prefijos de idioma en las rutas URL.',
        'prefer_user_session'           => 'Preferir la sesión del usuario al idioma detectado',
        'prefer_user_session_comment'   => 'Si se activa, el idioma guardado en la sesión del usuario tendrá prioridad sobre el idioma preferido por el navegador. Si se desactiva, el idioma se detectará de nuevo en cada visita.',
        'homepage_redirect'             => 'Redirección de la página de inicio',
        'homepage_redirect_comment'     => 'Si se activa, el código de idioma se añadirá a la URL de la página de inicio.',
        'force_prefix'                  => 'Forzar el prefijo de idioma',
        'force_prefix_comment'          => 'Si se activa, todas las peticiones GET a URL sin código de idioma recibirán el prefijo del idioma.',
        'localepicker_desc'             => 'Muestra una lista de enlaces para cambiar el idioma del sitio.'
    ],
    'permissions' => [
        'tab'      => 'Translate Extended',
        'settings' => 'Acceder a los ajustes',
    ],
];
