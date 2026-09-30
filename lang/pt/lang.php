<?php

return [
    'strings'     => [
        'plugin_desc'               => 'Adiciona ao plugin Translate do Winter a deteção do idioma do navegador e os prefixos de idioma nos endereços.',
        'plugin_short_desc'         => 'Estende o plugin Translate',
        'settings_label'            => 'Definições do Translate Extended',
        'settings_desc'             => 'Faça a gestão das definições do Translate Extended.',
        'browser_language_detection'           => 'Deteção do idioma do navegador',
        'browser_language_detection_comment'   => 'Permite mostrar o site no idioma preferido pelo navegador.',
        'query_param' => 'Parâmetro de consulta',
        'query_param_comment' => 'Se indicado, o idioma será detetado a partir deste parâmetro de consulta.',
        'header' => 'Cabeçalho',
        'header_comment' => 'Se indicado, o idioma será detetado a partir deste cabeçalho HTTP.',
        'route_prefixing'            => 'Prefixos nos endereços',
        'route_prefixing_comment'    => 'Permite usar prefixos de idioma nos endereços.',
        'prefer_user_session'           => 'Preferir a sessão do utilizador ao idioma detetado',
        'prefer_user_session_comment'   => 'Se ativado, o idioma guardado na sessão do utilizador tem prioridade sobre o idioma preferido pelo navegador. Se desativado, o idioma é detetado novamente em cada visita.',
        'homepage_redirect'             => 'Redirecionamento da página inicial',
        'homepage_redirect_comment'     => 'Se ativado, o código de idioma será adicionado ao endereço da página inicial.',
        'force_prefix'                  => 'Forçar o prefixo de idioma',
        'force_prefix_comment'          => 'Se ativado, todos os pedidos GET a endereços sem código de idioma recebem o prefixo do idioma.',
        'localepicker_desc'             => 'Mostra uma lista de ligações para mudar o idioma do site.'
    ],
    'permissions' => [
        'tab'      => 'Translate Extended',
        'settings' => 'Aceder às definições',
    ],
];
