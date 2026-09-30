<?php namespace Cubic\TranslateExtended\Tests\Feature;

use Request;
use ReflectionProperty;
use Illuminate\Routing\RouteCollection;
use Winter\Translate\Classes\Translator;
use System\Classes\PluginManager;
use Winter\Translate\Models\Locale;
use Cubic\TranslateExtended\Models\Settings;
use Cubic\TranslateExtended\Classes\ExtendedLocaleMiddleware;
use Cubic\TranslateExtended\Tests\TranslateExtendedTestCase;

class PluginRoutingTest extends TranslateExtendedTestCase
{
    public function setUp(): void
    {
        parent::setUp();

        Locale::unguard();
        foreach ([['code' => 'en', 'name' => 'English'], ['code' => 'de', 'name' => 'German']] as $locale) {
            Locale::create($locale + ['is_enabled' => true]);
        }
    }

    /**
     * Throws away the routes PluginTestCase registered while booting the plugin
     * and registers them again, so each test sees the routes its own settings
     * produce rather than the ones that happened to be in place at boot.
     */
    protected function bootPlugin()
    {
        $this->app['router']->setRoutes(new RouteCollection);

        PluginManager::instance()->bootPlugin($this->getPluginObject());
    }

    /**
     * Boots the plugin and returns the URIs of the routes it mounted inside its
     * own middleware group, which is where the homepage redirect and the
     * forced-prefix catch-all live.
     *
     * Scoping to that group matters: the CMS module already claims `/`, so a
     * bare check for that URI would be satisfied by the framework whether or not
     * the plugin registered anything.
     */
    protected function registerRoutes()
    {
        $this->bootPlugin();

        return $this->pluginUris();
    }

    /**
     * Boots the plugin and returns the URIs of every registered route.
     *
     * The locale-prefixed routes are mounted in a group of their own carrying
     * only the `web` middleware, so they cannot be found by filtering on ours.
     * Nothing in the modules claims a bare locale code, so looking for `de`
     * across the whole collection is unambiguous.
     */
    protected function registerAllRoutes()
    {
        $this->bootPlugin();

        return $this->allUris();
    }

    protected function routes()
    {
        return collect($this->app['router']->getRoutes()->getRoutes());
    }

    /**
     * The routes the plugin mounted inside its own middleware group.
     */
    protected function pluginRoutes()
    {
        return $this->routes()->filter(function ($route) {
            return in_array(ExtendedLocaleMiddleware::class, $route->gatherMiddleware());
        });
    }

    /**
     * The URIs of the plugin routes inside its own middleware group.
     */
    protected function pluginUris()
    {
        return $this->routes()
            ->filter(function ($route) {
                return in_array(ExtendedLocaleMiddleware::class, $route->gatherMiddleware());
            })
            ->map(function ($route) {
                return $route->uri();
            })
            ->values()->all();
    }

    /**
     * The URIs of every registered route.
     *
     * The locale-prefixed routes are mounted in a group of their own carrying
     * only the `web` middleware, so they cannot be found by filtering on ours.
     * Nothing in the modules claims a bare locale code, so looking for `de`
     * across the whole collection is unambiguous.
     */
    protected function allUris()
    {
        return $this->routes()->map(function ($route) {
            return $route->uri();
        })->values()->all();
    }

    /**
     * Puts a real request in place of the one the test booted with.
     *
     * Both the container binding and the facade have to be replaced. The
     * `request()` helper reads the binding, while the middleware reads the
     * facade; swapping only one of them leaves the two disagreeing, and the
     * plugin sees the boot request with its URI of `/` and no headers.
     *
     * The request has to be a real one for the same reason: a URI cannot be
     * changed after the request exists, because the base URL has already been
     * resolved from what it was built with.
     */
    protected function visit($path, $headers = [])
    {
        $request = Request::create($path, 'GET', [], [], [], $headers);
        $request->setLaravelSession(app('session.store'));

        app()->instance('request', $request);
        Request::swap($request);
    }


    /**
     * Pretends the Translate plugin has not been set up, which is the guard at
     * the top of registerRouting(). The flag is cached, so it has to be reset
     * rather than merely supplied.
     */
    protected function makeTranslatorUnconfigured()
    {
        $property = new ReflectionProperty(Translator::class, 'isConfigured');
        $property->setAccessible(true);
        $property->setValue(Translator::instance(), false);
    }

    public function testNoRoutesAreRegisteredWhenTheTranslatorIsNotConfigured()
    {
        $this->makeTranslatorUnconfigured();
        $this->visit('/some/page');
        $this->registerRoutes();

        $this->assertCount(0, $this->routes());
    }

    public function testTheMiddlewareIsMountedWhenConfigured()
    {
        $this->visit('/some/page');
        $this->registerRoutes();

        $this->assertGreaterThan(0, $this->pluginRoutes()->count());
    }

    public function testTheHomepageRedirectIsRegisteredByDefault()
    {
        Settings::set('route_prefixing', true);
        Settings::set('homepage_redirect', true);
        $this->visit('/');

        $this->assertContains('/', $this->registerRoutes());
    }

    public function testTheHomepageRedirectNeedsPrefixingToo()
    {
        Settings::set('route_prefixing', false);
        Settings::set('homepage_redirect', true);
        $this->visit('/');

        $this->assertNotContains('/', $this->registerRoutes());
    }

    public function testTheHomepageRedirectCanBeSwitchedOffOnItsOwn()
    {
        Settings::set('route_prefixing', true);
        Settings::set('homepage_redirect', false);
        $this->visit('/');

        $this->assertNotContains('/', $this->registerRoutes());
    }

    /**
     * The catch-all is what turns an unprefixed URL into a redirect, so it is
     * the thing that has to disappear when the setting is off.
     */
    public function testTheForcedPrefixCatchAllFollowsItsSetting()
    {
        Settings::set('force_prefix', true);
        $this->visit('/some/page');

        $this->assertContains('{any}', $this->registerRoutes());

        Settings::set('force_prefix', false);

        $this->assertNotContains('{any}', $this->registerRoutes());
    }

    /**
     * The resizer serves images from a path the locale must not be forced onto,
     * or every thumbnail on the site would be rewritten.
     */
    public function testTheForcedPrefixLeavesTheResizerAlone()
    {
        Settings::set('force_prefix', true);
        $this->visit('/resizer/320/240');

        $this->assertNotContains('{any}', $this->registerRoutes());
    }

    public function testTheForcedPrefixStandsDownWhenTheUrlAlreadyCarriesALocale()
    {
        Settings::set('force_prefix', true);
        $this->visit('/de/some/page');

        $this->assertNotContains('{any}', $this->registerRoutes());
    }

    public function testTheQueryParameterOverrideSelectsTheLocale()
    {
        Settings::set('query_param', 'lang');
        $this->visit('/some/page?lang=de');

        $this->assertContains('de', $this->registerAllRoutes());
    }

    public function testTheHeaderOverrideSelectsTheLocale()
    {
        Settings::set('header', 'X-Language');
        $this->visit('/some/page', ['HTTP_X_LANGUAGE' => 'de']);

        $this->assertContains('de', $this->registerAllRoutes());
    }

    /**
     * The query parameter is read before the header, so it wins when both are
     * present.
     */
    public function testTheQueryParameterWinsOverTheHeader()
    {
        Settings::set('query_param', 'lang');
        Settings::set('header', 'X-Language');
        $this->visit('/some/page?lang=de', ['HTTP_X_LANGUAGE' => 'en']);

        $this->assertContains('de', $this->registerAllRoutes());
    }

    /**
     * The header is read before the URL is consulted, so a request carrying
     * both takes the header. That is the opposite order to the middleware's own
     * chain, where a locale in the URL is read ahead of anything the browser
     * sends.
     *
     * These assert on the routes that came out, not on the translator's locale.
     * The routes are what registerRouting() returns to the application, and
     * unlike the translator they are not state shared with anything else.
     */
    public function testTheHeaderWinsOverTheUrl()
    {
        Settings::set('header', 'X-Language');
        $this->visit('/de/some/page', ['HTTP_X_LANGUAGE' => 'en']);

        $uris = $this->registerAllRoutes();

        $this->assertContains('en', $uris);
        $this->assertNotContains('de', $uris);
    }

    public function testTheUrlSelectsTheLocaleWhenNoOverrideIsConfigured()
    {
        $this->visit('/de/some/page');

        $this->assertContains('de', $this->registerAllRoutes());
    }

    public function testNoLocaleIsMountedWhenTheUrlCarriesNone()
    {
        Settings::set('query_param', 'lang');
        Settings::set('header', 'X-Language');
        $this->visit('/some/page');

        $this->assertNotContains('de', $this->registerAllRoutes());
    }
}
