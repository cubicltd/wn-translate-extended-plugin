<?php namespace Cubic\TranslateExtended\Tests\Feature\Components;

use Lang;
use Winter\Translate\Models\Locale;
use Winter\Translate\Components\LocalePicker;
use Cubic\TranslateExtended\Components\ExtendedLocalePicker;
use Cubic\TranslateExtended\Tests\TranslateExtendedTestCase;

class ExtendedLocalePickerTest extends TranslateExtendedTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Locale::unguard();
        foreach ([['code' => 'en', 'name' => 'English'], ['code' => 'de', 'name' => 'German']] as $locale) {
            Locale::create($locale + ['is_enabled' => true]);
        }
    }

    protected function picker()
    {
        return new ExtendedLocalePicker;
    }

    public function testItIsNamedForWhatItAdds()
    {
        $details = $this->picker()->componentDetails();

        $this->assertSame('Extended Locale Picker', $details['name']);
        $this->assertSame(
            'cubic.translateextended::lang.strings.localepicker_desc',
            $details['description']
        );
    }

    /**
     * The description is a lang key, not a string, because the plugin ships
     * translations. A literal here would show untranslated to everyone.
     */
    public function testTheDescriptionResolvesInThePluginLangNamespace()
    {
        $details = $this->picker()->componentDetails();

        $this->assertStringStartsWith('cubic.translateextended::', $details['description']);
        $this->assertNotSame($details['description'], Lang::get($details['description']));
    }

    /**
     * The extended picker deliberately declares no properties, which drops the
     * `forceUrl` checkbox the parent offers. It builds links rather than
     * switching over a redirect, so the parent's redirect option has nothing to
     * act on here. Pinned so that adding a property to the parent does not look
     * like it silently started working.
     */
    public function testItDeclaresNoPropertiesOfItsOwn()
    {
        $this->assertSame([], $this->picker()->defineProperties());
    }

    public function testItDropsTheParentsOnlyProperty()
    {
        $parent = new LocalePicker;

        $this->assertArrayHasKey('forceUrl', $parent->defineProperties());
        $this->assertArrayNotHasKey('forceUrl', $this->picker()->defineProperties());
    }

    public function testItIsRegisteredUnderTheNameTheTemplatesUse()
    {
        $plugin = $this->getPluginObject();

        $this->assertArrayHasKey(ExtendedLocalePicker::class, $plugin->registerComponents());
        $this->assertSame(
            'extendedLocalePicker',
            $plugin->registerComponents()[ExtendedLocalePicker::class]
        );
    }

    /**
     * makeLinks is a straight map from every enabled locale to a URL, keyed by
     * the locale code, because that is the shape the template indexes with:
     * `localeLinks[code]`.
     *
     * The URL itself is built by the parent from a CMS page, which a unit test
     * has no way to supply, so the collaborator is replaced. What is under test
     * is the mapping and the keying, not the parent's URL construction.
     */
    public function testMakeLinksKeysEveryEnabledLocaleByItsCode()
    {
        $picker = $this->pickerWithStubbedUrls();

        $this->assertSame(
            ['en' => '/stub/en', 'de' => '/stub/de'],
            $picker->makeLinks(Locale::listEnabled())
        );
    }

    public function testMakeLinksReturnsNothingForNoLocales()
    {
        $this->assertSame([], $this->pickerWithStubbedUrls()->makeLinks([]));
    }

    /**
     * A locale with a truthy name and a falsy one must both be mapped, since
     * the loop keys off the locale code rather than the name.
     */
    public function testMakeLinksMapsLocalesWhateverTheirName()
    {
        $picker = $this->pickerWithStubbedUrls();

        $this->assertSame(
            ['en' => '/stub/en', 'de' => '/stub/de', 'xx' => '/stub/xx'],
            $picker->makeLinks(['en' => 'English', 'de' => '', 'xx' => 'Something'])
        );
    }

    /**
     * A stand-in for the parent method, which cannot run without a CMS page.
     */
    protected function pickerWithStubbedUrls()
    {
        return new class extends ExtendedLocalePicker {
            protected function makeLocaleUrlFromPage($locale)
            {
                return '/stub/' . $locale;
            }
        };
    }
}
