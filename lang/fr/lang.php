<?php

return [
    'strings'     => [
        'plugin_desc'               => 'Ajoute la détection de la langue du navigateur et les préfixes de langue dans les routes au plugin Translate de Winter.',
        'plugin_short_desc'         => 'Étend le plugin Translate',
        'settings_label'            => 'Paramètres de Translate Extended',
        'settings_desc'             => 'Gérer les paramètres de Translate Extended.',
        'browser_language_detection'           => 'Détection de la langue du navigateur',
        'browser_language_detection_comment'   => 'Permet d\'afficher le site dans la langue préférée par le navigateur.',
        'query_param' => 'Paramètre de requête',
        'query_param_comment' => 'Si renseigné, la langue sera détectée à partir de ce paramètre de requête.',
        'header' => 'En-tête',
        'header_comment' => 'Si renseigné, la langue sera détectée à partir de cet en-tête HTTP.',
        'route_prefixing'            => 'Préfixes dans les routes',
        'route_prefixing_comment'    => 'Permet d\'utiliser des préfixes de langue dans les routes URL.',
        'prefer_user_session'           => 'Préférer la session de l\'utilisateur à la langue détectée',
        'prefer_user_session_comment'   => 'Si activé, la langue enregistrée dans la session de l\'utilisateur est prioritaire sur la langue préférée par le navigateur. Si désactivé, la langue est détectée à chaque visite.',
        'homepage_redirect'             => 'Redirection de la page d\'accueil',
        'homepage_redirect_comment'     => 'Si activé, le code de langue est ajouté à l\'URL de la page d\'accueil.',
        'force_prefix'                  => 'Forcer le préfixe de langue',
        'force_prefix_comment'          => 'Si activé, toutes les requêtes GET vers des URL sans code de langue reçoivent le préfixe de la langue.',
        'localepicker_desc'             => 'Affiche une liste de liens pour changer la langue du site.'
    ],
    'permissions' => [
        'tab'      => 'Translate Extended',
        'settings' => 'Accéder aux paramètres',
    ],
];
