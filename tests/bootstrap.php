<?php

/*
 * Bootstraps the plugin's test suite against a real Winter CMS installation.
 *
 * The plugin is developed inside a Winter checkout rather than a standalone
 * package, so the framework under test is the one that happens to be on disk.
 * Four levels up from tests/ is the project root, because Winter requires a
 * plugin to sit at plugins/<vendor>/<name>/ and will not discover it deeper or
 * shallower than that.
 */

define('WINTER_NO_EVENT_LOGGING', true);

$projectPath = dirname(__DIR__, 4);

require $projectPath . '/modules/system/tests/bootstrap/app.php';
