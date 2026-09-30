<?php namespace Cubic\TranslateExtended\Tests\Unit;

use Lang;
use Cubic\TranslateExtended\Tests\TranslateExtendedTestCase;

class LangCoverageTest extends TranslateExtendedTestCase
{
    /**
     * The languages the plugin is expected to ship. Anything else that exists
     * is a bonus, not a requirement, but it still has to be complete.
     */
    const REQUIRED = ['en', 'es', 'fr', 'pt', 'it', 'de'];

    const LANG_NAMESPACE = 'cubic.translateextended';

    protected function flatten(array $lines, $prefix = '')
    {
        $keys = [];

        foreach ($lines as $key => $value) {
            $path = $prefix === '' ? $key : $prefix . '.' . $key;
            $keys = array_merge($keys, is_array($value) ? $this->flatten($value, $path) : [$path]);
        }

        return $keys;
    }

    protected function keysIn($lang)
    {
        $path = dirname(__DIR__, 2) . '/lang/' . $lang . '/lang.php';

        $this->assertFileExists($path, 'the ' . $lang . ' translation is missing');

        return $this->flatten(require $path);
    }

    protected function langs()
    {
        $dir = dirname(__DIR__, 2) . '/lang';

        $langs = array_map('basename', glob($dir . '/*', GLOB_ONLYDIR));
        sort($langs);

        return $langs;
    }

    public function testEveryRequiredLanguageIsShipped()
    {
        foreach (self::REQUIRED as $lang) {
            $this->assertContains($lang, $this->langs(), $lang . ' should be shipped');
        }
    }

    /**
     * A key added to English and nowhere else is a label that falls back to
     * English for everyone who has not translated it — quietly, because nothing
     * anywhere reports it as missing.
     */
    public function testEveryLanguageHasEveryEnglishKey()
    {
        $english = $this->keysIn('en');

        foreach ($this->langs() as $lang) {
            $this->assertEqualsCanonicalizing(
                $english,
                $this->keysIn($lang),
                'lang/' . $lang . '/lang.php does not cover the same keys as lang/en'
            );
        }
    }

    public function testNoLanguageCarriesAKeyEnglishDoesNot()
    {
        $english = $this->keysIn('en');

        foreach ($this->langs() as $lang) {
            $this->assertEmpty(
                array_diff($this->keysIn($lang), $english),
                'lang/' . $lang . '/lang.php has keys that lang/en does not'
            );
        }
    }

    public function testNoTranslationIsLeftEmpty()
    {
        foreach ($this->langs() as $lang) {
            foreach ($this->keysIn($lang) as $key) {
                $this->assertNotSame('', Lang::get(self::LANG_NAMESPACE . '::lang.' . $key, [], $lang));
            }
        }
    }

    /**
     * Winter registers a plugin's translations under the namespace derived from
     * its code, so a file sitting in lang/ is only reachable if that code is
     * right. An unregistered namespace makes every lookup return the key back.
     */
    public function testTheTranslationsResolveThroughThePluginNamespace()
    {
        $this->assertNotSame(
            self::LANG_NAMESPACE . '::lang.strings.plugin_desc',
            Lang::get(self::LANG_NAMESPACE . '::lang.strings.plugin_desc')
        );
    }
}
