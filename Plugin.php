<?php namespace Cubic\TranslateExtended;

use App;
use Route;
use Event;
use System\Classes\PluginBase;
use Winter\Translate\Classes\Translator;
use Cubic\TranslateExtended\Classes\ExtendedLocaleMiddleware;
use Cubic\TranslateExtended\Models\Settings;

/**
 * Translate Extended Plugin Information File
 */
class Plugin extends PluginBase
{
    /**
     * Returns information about this plugin.
     *
     * @return array
     */
    public function pluginDetails()
    {
        return [
            'name'        => 'Translate Extended',
            'description' => 'cubic.translateextended::lang.strings.plugin_desc',
            'author'      => 'Cubic',
            'icon'        => 'icon-language',
            'homepage'    => 'https://github.com/cubicltd/wn-translate-extended-plugin'
        ];
    }

    /**
     * @var array Plugin dependencies
     */
    public $require = ['Winter.Translate'];

    public function boot(): void
    {
        if (!App::runningInBackend()) {
            $this->registerRouting();
        }
    }

    protected function registerRouting()
    {
        $translator = Translator::instance();
        if (!$translator->isconfigured()) {
            return;
        }
        $request = request();
        $locale = null;

        if ($queryParam = trim(Settings::get('query_param', ''))) {
            $locale = trim($request->get($queryParam));

            if ($locale) {
                $translator->setLocale($locale);
            }
        }

        if (!$locale && $header = trim(Settings::get('header', ''))) {
            $locale = trim($request->header($header));

            if ($locale) {
                $translator->setLocale($locale);
            }
        }

        $locale = $locale ?: ($translator->loadLocaleFromRequest() ? $translator->getLocale() : null);

        // mount cms controller to prefixed routes
        if ($locale) {
            Route::group(['prefix' => $locale, 'middleware' => 'web'], function (): void {
                Route::any('{slug?}', 'Cms\Classes\CmsController@run')->where('slug', '(.*)?');
            });

            Route::any($locale, 'Cms\Classes\CmsController@run')->middleware('web');

            Event::listen('cms.route', function () use ($locale): void {
                Route::group(['prefix' => $locale, 'middleware' => 'web'], function (): void {
                    Route::any('{slug?}', 'Cms\Classes\CmsController@run')->where('slug', '(.*)?');
                });
            });
        }

        Route::middleware(['web', ExtendedLocaleMiddleware::class])->group(function () use ($translator, $request): void {
            if (Settings::get('route_prefixing', true)) {
                if (Settings::get('homepage_redirect', true)) {
                    Route::get('/', function () use ($translator, $request) {
                        $redirect = '/' . $translator->getLocale();
                        if ($request->query()) {
                            $redirect .= '?' . http_build_query($request->query());
                        }
                        return redirect($redirect);
                    });
                }
            }

            if (Settings::get('force_prefix', true) && !$translator->loadLocaleFromRequest() && $request->segment(1) !== 'resizer') {
                Route::get('/{any}', function () use ($translator, $request) {
                    $redirect = $translator->getDefaultLocale() . '/' . $request->path();
                    if ($request->query()) {
                        $redirect .= '?' . http_build_query($request->query());
                    }
                    return redirect($redirect);
                })->where('any', '.*');
            }
        });
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return array
     */
    public function registerComponents()
    {
        return [
            \Cubic\TranslateExtended\Components\ExtendedLocalePicker::class => 'extendedLocalePicker'
        ];
    }

    /**
     * Registers any back-end permissions used by this plugin.
     *
     * @return array
     */
    public function registerPermissions()
    {
        return [
            'cubic.translateextended.access_settings' => [
                'tab'   => 'cubic.translateextended::lang.permissions.tab',
                'label' => 'cubic.translateextended::lang.permissions.settings'
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'translateextended' => [
                'label'       => 'cubic.translateextended::lang.strings.settings_label',
                'description' => 'cubic.translateextended::lang.strings.settings_desc',
                'icon'        => 'icon-language',
                'class'       => \Cubic\TranslateExtended\Models\Settings::class,
                'order'       => 552,
                'category'    => 'winter.translate::lang.plugin.name',
                'permissions' => ['cubic.translateextended.access_settings']
            ]
        ];
    }

//    TODO: allow users to opt-in into extending |app twig filter
//    /**
//     * Lets extend the app filter.
//     * @return array
//     */
//    public function registerMarkupTags()
//    {
//        return [
//            'filters' => [
//                'app' => [$this, 'appFilter']
//            ]
//        ];
//    }
//
//    /**
//     * Extends the classic app filter
//     * @param  string $url
//     * @return string
//     */
//    public function appFilter($url)
//    {
//        return URL::to(Translator::instance()->getLocale() . '/' . $url);
//    }
}
