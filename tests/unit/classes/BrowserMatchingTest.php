<?php namespace Cubic\TranslateExtended\Tests\Unit\Classes;

use Cubic\TranslateExtended\Classes\BrowserMatching;
use Cubic\TranslateExtended\Tests\TranslateExtendedTestCase;

class BrowserMatchingTest extends TranslateExtendedTestCase
{
    /**
     * Sets the request header the class reads out of $_SERVER, and puts back
     * whatever was there before so one test cannot leak into the next.
     */
    protected function setAcceptLanguage($header)
    {
        $original = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;

        if (is_null($header)) {
            unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        } else {
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $header;
        }

        $this->beforeApplicationDestroyed(function () use ($original) {
            if (is_null($original)) {
                unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
            } else {
                $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $original;
            }
        });
    }

    /**
     * parseLanguageList keys the result by quality, not by language, because
     * that is the sort key the caller needs. It reads $_SERVER only when it is
     * handed null.
     */
    public function testParseLanguageListReturnsNothingWhenNoHeaderIsSent()
    {
        $this->setAcceptLanguage(null);

        $this->assertSame([], BrowserMatching::parseLanguageList(null));
    }

    public function testParseLanguageListReadsTheHeaderWhenGivenNull()
    {
        $this->setAcceptLanguage('de;q=0.4,en;q=0.6');

        $this->assertSame(
            ['0.6' => 'en', '0.4' => 'de'],
            BrowserMatching::parseLanguageList(null)
        );
    }

    public function testParseLanguageListSortsByQualityDescending()
    {
        $this->assertSame(
            ['0.9' => 'en', '0.5' => 'fr', '0.1' => 'de'],
            BrowserMatching::parseLanguageList('fr;q=0.5,en;q=0.9,de;q=0.1')
        );
    }

    public function testParseLanguageListDefaultsAnAbsentQualityToOne()
    {
        $this->assertSame(['1.0' => 'en'], BrowserMatching::parseLanguageList('en'));
    }

    /**
     * The duplicate guard is keyed on quality, not on language, so two tags
     * sharing a quality collapse to the first one. The same language offered
     * at two different qualities is kept twice.
     */
    public function testParseLanguageListDropsLaterTagsThatShareAQuality()
    {
        $this->assertSame(
            ['0.8' => 'en'],
            BrowserMatching::parseLanguageList('en;q=0.8,fr;q=0.8')
        );

        $this->assertSame(
            ['0.9' => 'en', '0.3' => 'en'],
            BrowserMatching::parseLanguageList('en;q=0.3,en;q=0.9')
        );
    }

    public function testParseLanguageListLowercasesTheLanguageTags()
    {
        $this->assertSame(['1.0' => 'en-gb'], BrowserMatching::parseLanguageList('EN-GB'));
    }

    public function testMatchLanguageIsExactOnlyWhenBothSidesAreIdentical()
    {
        $this->assertSame(2, BrowserMatching::matchLanguage('en', 'en'));
        $this->assertSame(1.0, BrowserMatching::matchLanguage('en', 'en-gb'));
        $this->assertSame(0.5, BrowserMatching::matchLanguage('en-us', 'en'));
        $this->assertSame(0.5, BrowserMatching::matchLanguage('en-us', 'en-gb'));
        $this->assertSame(0, BrowserMatching::matchLanguage('en', 'fr'));
    }

    public function testMatchLanguageOnlyLowercasesTheAvailableSide()
    {
        $this->assertSame(2, BrowserMatching::matchLanguage('en', 'EN'));
        $this->assertSame(0, BrowserMatching::matchLanguage('EN', 'en-us'));
    }

    public function testMatchLanguageReturnsNothingWhenEitherTagIsEmpty()
    {
        $this->assertSame(0, BrowserMatching::matchLanguage('', 'en'));
        $this->assertSame(0, BrowserMatching::matchLanguage('en', ''));
    }

    public function testFindMatchesScoresAvailableLocalesByQualityAndSpecificity()
    {
        $available = ['en' => 'English', 'de' => 'Deutsch', 'fr' => 'Français'];

        $this->assertSame(
            ['en' => 1.8, 'fr' => 1.0, 'de' => 0.2],
            BrowserMatching::findMatches(
                BrowserMatching::parseLanguageList('de;q=0.1,en;q=0.9,fr;q=0.5'),
                $available
            )
        );
    }

    /**
     * A broader match does not crowd out a more specific one: both are kept,
     * and the specific one wins on score.
     */
    public function testFindMatchesKeepsABroaderMatchAlongsideTheSpecificOne()
    {
        $available = ['en' => 'English', 'en-gb' => 'English (GB)'];

        $matches = BrowserMatching::findMatches(
            BrowserMatching::parseLanguageList('en-gb'),
            $available
        );

        $this->assertSame(['en-gb' => 2.0, 'en' => 0.5], $matches);
    }

    /**
     * A bare `q=0` means the client refuses the language, and it is honoured as
     * such.
     *
     * The quality pattern is `0(?:\.\d{0,3})?|1(?:\.0{0,3})?`, and the trailing
     * `?` on the inner group is what lets a zero written without a decimal
     * point parse. Without it the whole alternation fails, the surrounding
     * optional group is skipped, and the quality falls back to its `1.0`
     * default — which promoted a refusal to full preference.
     */
    public function testABareZeroQualityIsTreatedAsARefusal()
    {
        $parsed = BrowserMatching::parseLanguageList('de;q=0');

        // The quality is cast through a float and back to a string before it
        // becomes a key, so a zero arrives as the integer 0.
        $this->assertSame([0 => 'de'], $parsed);

        $this->assertSame(
            [],
            BrowserMatching::findMatches($parsed, ['de' => 'Deutsch'])
        );
    }

    /**
     * A zero written with a decimal point is refused the same way.
     */
    public function testADecimalZeroQualityIsHonoured()
    {
        $this->assertSame(
            [],
            BrowserMatching::findMatches(
                BrowserMatching::parseLanguageList('de;q=0.0'),
                ['de' => 'Deutsch']
            )
        );
    }

    /**
     * A bare `q=1` now matches the pattern instead of falling through to the
     * default, so the key is the cast form of 1 rather than the literal `1.0`.
     * Both mean the same thing; only the key differs.
     */
    public function testABareFullQualityStillReadsAsFullPreference()
    {
        $this->assertSame([1 => 'de'], BrowserMatching::parseLanguageList('de;q=1'));
    }

    public function testFindMatchesReturnsNothingWhenNothingMatches()
    {
        $this->assertSame(
            [],
            BrowserMatching::findMatches(
                BrowserMatching::parseLanguageList('ja'),
                ['en' => 'English', 'de' => 'Deutsch']
            )
        );
    }

    /**
     * A bare wildcard matches no tag, and a client sending `*` is saying it
     * accepts anything rather than expressing a preference. Nothing is chosen,
     * so the caller keeps whatever it already had.
     */
    public function testAWildcardAloneMatchesNothing()
    {
        $this->assertSame(
            [],
            BrowserMatching::findMatches(
                BrowserMatching::parseLanguageList('*'),
                ['en' => 'English', 'de' => 'Deutsch']
            )
        );
    }

    /**
     * A wildcard alongside a real tag must not hijack it.
     */
    public function testFindMatchesPrefersARealTagOverTheWildcard()
    {
        $available = ['en' => 'English', 'de' => 'Deutsch'];

        $matches = BrowserMatching::findMatches(
            BrowserMatching::parseLanguageList('de;q=0.8,*;q=0.1'),
            $available
        );

        $this->assertSame('de', array_keys($matches)[0]);
    }
}
