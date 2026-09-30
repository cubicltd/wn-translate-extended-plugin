<?php namespace Cubic\TranslateExtended\Tests\Unit\Models;

use Db;
use Symfony\Component\Yaml\Yaml;
use Cubic\TranslateExtended\Models\Settings;
use Cubic\TranslateExtended\Tests\TranslateExtendedTestCase;

class SettingsTest extends TranslateExtendedTestCase
{
    const SETTINGS_CODE = 'cubic_translateextended_settings';

    public function testTheSettingsRowIsStoredUnderTheRebrandedCode()
    {
        Settings::set('route_prefixing', false);

        $this->assertSame(
            1,
            Db::table('system_settings')->where('item', self::SETTINGS_CODE)->count()
        );
    }

    /**
     * The settings code is the key of the row, so nothing may write one
     * anywhere else. The pre-rebrand code would silently collect nothing.
     */
    public function testNothingIsWrittenUnderTheOldSettingsCode()
    {
        Settings::set('route_prefixing', false);

        $this->assertSame(
            0,
            Db::table('system_settings')
                ->where('item', 'studiobosco_translateextended_settings')
                ->count()
        );
    }

    public function testAValueSurvivesARoundTrip()
    {
        Settings::set('query_param', 'lang');

        $this->assertSame('lang', Settings::get('query_param'));
    }

    public function testTheSecondValueSurvivesTheFirst()
    {
        Settings::set('query_param', 'lang');
        Settings::set('header', 'X-Language');

        $this->assertSame('lang', Settings::get('query_param'));
        $this->assertSame('X-Language', Settings::get('header'));
    }

    /**
     * With nothing stored, a setting falls back to the default the caller asks
     * for, not to a value out of fields.yaml. The fields file declares no
     * defaults for the two text settings, so a caller that passes '' gets ''.
     */
    public function testUnsetSettingsFallBackToTheGivenDefault()
    {
        $this->assertTrue(Settings::get('route_prefixing', true));
        $this->assertSame('', Settings::get('query_param', ''));
    }

    /**
     * The switches are all on by default, which is what makes the plugin do
     * anything at all out of the box.
     */
    public function testEverySwitchDefaultsToOn()
    {
        foreach (['route_prefixing', 'homepage_redirect', 'force_prefix', 'prefer_user_session', 'browser_language_detection'] as $setting) {
            $this->assertTrue(Settings::get($setting, true), $setting . ' should default to on');
        }
    }

    public function testAValueCanBeTurnedOff()
    {
        Settings::set('route_prefixing', false);

        $this->assertFalse(Settings::get('route_prefixing', true));
    }

    /**
     * fields.yaml is the source of the settings form. A key the backend cannot
     * edit is a setting nobody can change without touching the database, and the
     * file is the only place that says which keys exist.
     */
    public function testTheSettingsFormExposesEverySettingTheModelReads()
    {
        $fields = Yaml::parseFile(dirname(__DIR__, 3) . '/models/settings/fields.yaml');

        $this->assertEqualsCanonicalizing(
            [
                'browser_language_detection',
                'prefer_user_session',
                'query_param',
                'header',
                'route_prefixing',
                'homepage_redirect',
                'force_prefix',
            ],
            array_keys($fields['fields'])
        );
    }

    /**
     * The switches carry a default of their own, so the backend form shows them
     * ticked before anything is stored.
     */
    public function testTheSwitchesInTheFormDefaultToOn()
    {
        $fields = Yaml::parseFile(dirname(__DIR__, 3) . '/models/settings/fields.yaml')['fields'];

        $switches = array_filter($fields, function ($field) {
            return ($field['type'] ?? null) === 'switch';
        });

        $this->assertNotEmpty($switches);

        foreach ($switches as $name => $field) {
            $this->assertTrue($field['default'], $name . ' should default to on in the form');
        }
    }
}
