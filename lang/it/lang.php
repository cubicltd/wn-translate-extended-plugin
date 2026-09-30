<?php

return [
    'strings'     => [
        'plugin_desc'               => 'Aggiunge al plugin Translate di Winter il rilevamento della lingua del browser e i prefissi di lingua negli URL.',
        'plugin_short_desc'         => 'Estende il plugin Translate',
        'settings_label'            => 'Impostazioni di Translate Extended',
        'settings_desc'             => 'Gestisci le impostazioni di Translate Extended.',
        'browser_language_detection'           => 'Rilevamento della lingua del browser',
        'browser_language_detection_comment'   => 'Permette di mostrare il sito nella lingua preferita dal browser.',
        'query_param' => 'Parametro di query',
        'query_param_comment' => 'Se indicato, la lingua verrà rilevata da questo parametro di query.',
        'header' => 'Intestazione',
        'header_comment' => 'Se indicato, la lingua verrà rilevata da questa intestazione HTTP.',
        'route_prefixing'            => 'Prefissi negli URL',
        'route_prefixing_comment'    => 'Permette di usare prefissi di lingua negli URL.',
        'prefer_user_session'           => 'Preferisci la sessione dell\'utente alla lingua rilevata',
        'prefer_user_session_comment'   => 'Se attivo, la lingua salvata nella sessione dell\'utente ha la precedenza sulla lingua preferita dal browser. Se disattivo, la lingua viene rilevata di nuovo a ogni visita.',
        'homepage_redirect'             => 'Reindirizzamento della home page',
        'homepage_redirect_comment'     => 'Se attivo, il codice della lingua viene aggiunto all\'URL della home page.',
        'force_prefix'                  => 'Forza il prefisso della lingua',
        'force_prefix_comment'          => 'Se attivo, tutte le richieste GET verso URL senza codice della lingua ricevono il prefisso della lingua.',
        'localepicker_desc'             => 'Mostra un elenco di link per cambiare la lingua del sito.'
    ],
    'permissions' => [
        'tab'      => 'Translate Extended',
        'settings' => 'Accedi alle impostazioni',
    ],
];
