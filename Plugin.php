<?php namespace StudioBosco\TranslateExtended;

use App;
use Route;
use Event;
use System\Classes\PluginBase;
use Winter\Translate\Classes\Translator;
use StudioBosco\TranslateExtended\Classes\ExtendedLocaleMiddleware;
use StudioBosco\TranslateExtended\Models\Settings;

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
            'description' => 'studiobosco.translateextended::lang.strings.plugin_desc',
            'author'      => 'Excodus, StudioBosco',
            'icon'        => 'icon-language',
            'homepage'    => 'https://github.com/studiobosco/wn-translate-extended'
        ];
    }

    /**
     * @var array Plugin dependencies
     */
    public $require = ['Winter.Translate'];

    public function boot()
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

        $locale = $locale ? $locale : ($translator->loadLocaleFromRequest() ? $translator->getLocale() : null);

        // mount cms controller to prefixed routes
        if ($locale) {
            Route::group(['prefix' => $locale, 'middleware' => 'web'], function() {
                Route::any('{slug?}', 'Cms\Classes\CmsController@run')->where('slug', '(.*)?');
            });

            Route::any($locale, 'Cms\Classes\CmsController@run')->middleware('web');

            Event::listen('cms.route', function() use ($locale) {
                Route::group(['prefix' => $locale, 'middleware' => 'web'], function() {
                    Route::any('{slug?}', 'Cms\Classes\CmsController@run')->where('slug', '(.*)?');
                });
            });
        }

        Route::middleware(['web', ExtendedLocaleMiddleware::class])->group(function () use ($translator, $request) {
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
            'StudioBosco\TranslateExtended\Components\ExtendedLocalePicker' => 'extendedLocalePicker'
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
            'studiobosco.translateextended.access_settings' => [
                'tab'   => 'studiobosco.translateextended::lang.permissions.tab',
                'label' => 'studiobosco.translateextended::lang.permissions.settings'
            ],
        ];
    }

    public function registerSettings()
    {
        return [
            'translateextended' => [
                'label'       => 'studiobosco.translateextended::lang.strings.settings_label',
                'description' => 'studiobosco.translateextended::lang.strings.settings_desc',
                'icon'        => 'icon-language',
                'class'       => 'StudioBosco\TranslateExtended\Models\Settings',
                'order'       => 552,
                'category'    => 'rainlab.translate::lang.plugin.name',
                'permissions' => ['studiobosco.translateextended.access_settings']
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
