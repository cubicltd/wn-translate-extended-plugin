<?php namespace Cubic\TranslateExtended\Tests\Feature\Classes;

use Request;
use Winter\Translate\Classes\Translator;
use Winter\Translate\Models\Locale;
use Cubic\TranslateExtended\Models\Settings;
use Cubic\TranslateExtended\Classes\ExtendedLocaleMiddleware;
use Cubic\TranslateExtended\Tests\TranslateExtendedTestCase;

class ExtendedLocaleMiddlewareTest extends TranslateExtendedTestCase
{
    protected $originalAcceptLanguage;

    public function setUp(): void
    {
        parent::setUp();

        $this->originalAcceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? null;

        Locale::unguard();
        foreach ([['code' => 'en', 'name' => 'English'], ['code' => 'de', 'name' => 'German']] as $locale) {
            Locale::create($locale + ['is_enabled' => true]);
        }
    }

    public function tearDown(): void
    {
        if (is_null($this->originalAcceptLanguage)) {
            unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
        } else {
            $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $this->originalAcceptLanguage;
        }

        parent::tearDown();
    }

    /**
     * Runs the middleware and returns the locale it settled on, which is the
     * only way to observe it: the chain's own return value is discarded by
     * handle().
     *
     * The request is swapped onto the container rather than simply passed in,
     * because the chain reads the global request — `post()` for the body and
     * `Request::segment()` for the URL — and ignores handle()'s own argument.
     * Two consequences follow, both found the hard way:
     *
     *  * the request has to be a real one. `post()` returns its default unless
     *    the method is POST, PUT, DELETE or PATCH, and the URL segment cannot
     *    be set afterwards — by the time a request exists, its base URL has
     *    already been resolved from the URI it was built with.
     *  * the facade has to be swapped too, not just the container binding, or
     *    `Request::` keeps serving the instance it resolved first.
     */
    protected function runMiddleware($method = 'GET', $path = '/some/page', $parameters = [])
    {
        $request = Request::create($path, $method, $parameters);
        $request->setLaravelSession(app('session.store'));
        Request::swap($request);

        (new ExtendedLocaleMiddleware)->handle($request, function () {
            return null;
        });

        return Translator::instance()->getLocale();
    }

    protected function defaultLocale()
    {
        return Translator::instance()->getDefaultLocale();
    }

    /**
     * The chain is post, then URL, then session, then browser, and a step is
     * reached only when the one before it yields nothing.
     */
    public function testPostWinsOverEverythingElse()
    {
        Settings::set('browser_language_detection', true);
        Settings::set('prefer_user_session', true);

        session()->put(Translator::SESSION_LOCALE, 'de');
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de';

        $this->assertSame(
            'en',
            $this->runMiddleware('POST', '/en/some/page', ['locale' => 'en'])
        );
    }

    public function testTheUrlWinsOverTheSessionAndTheBrowser()
    {
        Settings::set('browser_language_detection', true);
        Settings::set('prefer_user_session', true);

        session()->put(Translator::SESSION_LOCALE, 'de');
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de';

        $this->assertSame('en', $this->runMiddleware('GET', '/en/some/page'));
    }

    public function testTheSessionWinsOverTheBrowser()
    {
        Settings::set('browser_language_detection', true);
        Settings::set('prefer_user_session', true);

        session()->put(Translator::SESSION_LOCALE, 'de');
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';

        $this->assertSame('de', $this->runMiddleware());
    }

    public function testTheBrowserIsReachedWhenNothingElseMatches()
    {
        Settings::set('browser_language_detection', true);
        Settings::set('prefer_user_session', true);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de';

        $this->assertSame('de', $this->runMiddleware());
    }

    public function testAnAbsentHeaderLeavesTheDefaultLocaleAlone()
    {
        Settings::set('browser_language_detection', true);
        Settings::set('prefer_user_session', true);

        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);

        $this->assertSame($this->defaultLocale(), $this->runMiddleware());
    }

    public function testAHeaderMatchingNoEnabledLocaleLeavesTheDefaultLocaleAlone()
    {
        Settings::set('browser_language_detection', true);
        Settings::set('prefer_user_session', true);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'ja';

        $this->assertSame($this->defaultLocale(), $this->runMiddleware());
    }

    public function testBrowserDetectionCanBeSwitchedOff()
    {
        Settings::set('browser_language_detection', false);
        Settings::set('prefer_user_session', true);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de';

        $this->assertSame($this->defaultLocale(), $this->runMiddleware());
    }

    public function testTheSessionCanBePassedOver()
    {
        Settings::set('browser_language_detection', true);
        Settings::set('prefer_user_session', false);

        session()->put(Translator::SESSION_LOCALE, 'de');
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'en';

        $this->assertSame('en', $this->runMiddleware());
    }

    /**
     * `*` matches no tag, so findMatches falls back to the enabled locales and
     * the arsort that follows orders them by name. Whichever locale has the
     * greatest name wins, which is a function of how a language is written
     * down rather than of anything the visitor asked for.
     *
     * Pinned as it behaves. The fix is a later commit.
     */
    public function testAWildcardAloneSelectsTheLocaleWithTheGreatestName()
    {
        Settings::set('browser_language_detection', true);
        Settings::set('prefer_user_session', true);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = '*';

        $available = Locale::listEnabled();
        arsort($available);

        $this->assertSame(array_keys($available)[0], $this->runMiddleware());
    }

    /**
     * A wildcard arriving alongside a real tag must not override it.
     */
    public function testARealTagIsNotOverriddenByACompanionWildcard()
    {
        Settings::set('browser_language_detection', true);
        Settings::set('prefer_user_session', true);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de;q=0.8,*;q=0.1';

        $this->assertSame('de', $this->runMiddleware());
    }

    public function testTheRequestIsAlwaysPassedOn()
    {
        $request = Request::create('/some/page', 'GET');
        $request->setLaravelSession(app('session.store'));
        Request::swap($request);

        $response = (new ExtendedLocaleMiddleware)->handle($request, function ($passed) {
            return $passed;
        });

        $this->assertSame($request, $response);
    }

    public function testTheMiddlewareRemembersWhatItDetectedInTheSession()
    {
        Settings::set('browser_language_detection', true);
        Settings::set('prefer_user_session', true);

        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = 'de';

        $this->runMiddleware();

        $this->assertSame('de', session()->get(Translator::SESSION_LOCALE));
    }
}
