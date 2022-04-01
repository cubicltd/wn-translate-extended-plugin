<?php
/**
 * Created by PhpStorm.
 * User: r
 * Date: 13.04.16
 * Time: 16:12
 */

use StudioBosco\TranslateExtended\Models\Settings;
use StudioBosco\TranslateExtended\Classes\ExtendedLocaleMiddleware;
use Winter\Translate\Classes\Translator;

App::before(function($request) {

    if (App::runningInBackend()) {
        return;
    }

    $translator = Translator::instance();
    if (!$translator->isConfigured())
        return;

    $locale = $translator->loadLocaleFromRequest() ? $translator->getLocale() : null;

    if ($queryParam = trim(Settings::get('query_param', ''))) {
        $queryLocale = trim($request->get($queryParam));

        if ($queryLocale && $locale !== $queryLocale) {
            $translator->setLocale($queryLocale);
        }
    }

    if ($header = trim(Settings::get('header', ''))) {
        $headerLocale = trim($request->header($header));

        if ($headerLocale && $locale !== $headerLocale) {
            $translator->setLocale($headerLocale);
        }
    }

    if (Settings::get('route_prefixing', true)) {

        if ($locale){
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

        if (Settings::get('homepage_redirect', true)) {
            Route::get('/', function() use($translator, $request) {
                $redirect = $translator->getLocale();
                if ($request->query()) {
                    $redirect .= '?' . http_build_query($request->query());
                }
                return redirect($redirect);
            })->middleware(['web', ExtendedLocaleMiddleware::class]);
        }

        if (Settings::get('force_prefix', true)) {
            Route::get('/{any}', function () use ($translator, $request) {
                $redirect = $translator->getDefaultLocale() . '/' . $request->path();
                if ($request->query()) {
                    $redirect .= '?' . http_build_query($request->query());
                }
                return redirect($redirect);
            })->where('any', '.*');
        }
    }
});
