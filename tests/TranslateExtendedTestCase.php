<?php namespace Cubic\TranslateExtended\Tests;

use System\Behaviors\SettingsModel;
use System\Classes\PluginManager;
use Winter\Translate\Classes\Translator;

if (class_exists('\System\Tests\Bootstrap\PluginTestCase')) {
    class BaseTestCase extends \System\Tests\Bootstrap\PluginTestCase
    {
    }
} else {
    class BaseTestCase extends \PluginTestCase
    {
    }
}

/**
 * Base class for the plugin's tests.
 *
 * PluginTestCase boots the application on an in-memory SQLite database, runs
 * `winter:up` before every test, and works out which plugin is under test from
 * the namespace of the test class itself — which is why every test here must
 * live under `Cubic\TranslateExtended\Tests`.
 */
abstract class TranslateExtendedTestCase extends BaseTestCase
{
    protected $refreshPlugins = [
        'Cubic.TranslateExtended',
    ];

    public function setUp(): void
    {
        parent::setUp();

        /*
         * Three things outlive the application a test tears down, and each one
         * makes a test fail in a way that looks like the plugin's fault rather
         * than the test's — which, with the suite in random order, means it
         * only shows up sometimes.
         *
         * The settings behaviour keeps its instances in a static array, so a
         * setting written by one test is still readable by the next.
         *
         * The translator is a singleton, so a locale set by one test is still
         * active in the next.
         *
         * PluginManager::$noInit is set to true during the first bootstrap of
         * the process, on the branch that fires when the migration table does
         * not exist yet, and nothing ever sets it back. From then on bootPlugin
         * returns immediately for any plugin that is not elevated, so a test
         * that depends on the plugin booting silently tests nothing at all.
         */
        SettingsModel::clearInternalCache();
        Translator::forgetInstance();
        PluginManager::$noInit = false;
    }

    /**
     * Returns a URL the middleware is expected to redirect to, with the host
     * and scheme of the running site left off so assertions stay readable.
     */
    protected function redirectPath($response): string
    {
        $target = $response->headers->get('Location');

        return $target ? parse_url($target, PHP_URL_PATH) : '';
    }
}
