<?php

return [
    'strings'     => [
        'plugin_desc'               => 'Fügt Browser-Spracherkennung und Sprach-Prefixe für URLs zum Translate Plugin hinzu.',
        'plugin_short_desc'               => 'Erweitert das Translate Plugin',
        'settings_label'            => 'Erweiterte Spracheinstellungen',
        'settings_desc'             => 'Erweierte Spracheinstellungen verwalten.',
        'browser_language_detection'           => 'Browser-Spracherkennung',
        'browser_language_detection_comment'   => 'Aktiviert die Übersetzung der Webseite in der bevorzugten Browser-Sprache.',
        'query_param' => 'Query-Parameter',
        'query_param_comment' => 'Wird die Sprache aus dem hier eingetragenen Query-Paremeter erkennen. Leer lassen um diese Funktion zu deaktivieren.',
        'header' => 'Header',
        'header_comment' => 'Wird die Sprache aus dem hier eingetragenen HTTP Header erkennen. Leer lassen um diese Funktion zu deaktivieren.',
        'route_prefixing'            => 'URL Prefix',
        'route_prefixing_comment'    => 'Aktiviert die Verwendung von Sprach-Prefixen in URLs.',
        'prefer_user_session'           => 'Sprache aus Nutzer-Session statt erkannter Sprache preferieren.',
        'prefer_user_session_comment'   => 'Aktivieren, um die Sprache aus der Nutzer-Session zu verwenden, statt die erkannte Sprache. Wenn es deaktiviert ist, wird die Sprache jedes mal neu erkannt.',
        'homepage_redirect'             => 'Homepage redirect',
        'homepage_redirect_comment'     => 'Aktivieren, um das Sprachkürzel der Homepage URL hinzuzufügen.',
        'force_prefix'                  => 'Sprach-Prefix erzwingen',
        'force_prefix_comment'          => 'Aktivieren, um alle GET Anfragen mit URLs ohne Sprach-Prefix mit einem Sprach-Prefix zu versehen.',
        'localepicker_desc'             => 'Zeigt eine Liste mit links zum wechsel der Sprache.'
    ],
    'permissions' => [
        'tab'      => 'Erweiterte Spracheinstellungen',
        'settings' => 'Einstellungen verwalten',
    ],
];
