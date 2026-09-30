# Translate Extended

Extends [Winter's Translate plugin](https://github.com/wintercms/wn-translate-plugin) with the features a multilingual front-end usually needs and the core plugin deliberately leaves out:

 * detect the visitor's preferred language from the browser
 * show that language instead of the default one, and remember it in the session
 * force re-detection on every visit instead of only the first one
 * prefix every route with an SEO-friendly locale short code
 * override the locale from a query parameter or a request header

Requires **PHP 8.1+** and the **Winter.Translate** plugin.

## Installation

```bash
composer require cubic/wn-translate-extended-plugin
php artisan winter:up
```

The plugin installs to `plugins/cubic/translateextended` and registers itself under the `Cubic.TranslateExtended` identifier.

## Usage

Winter's Translate plugin serves translated content in two ways:

 * `http://website/lang/` displays the site in the language with the `lang` short code.
 * `http://website/` displays the site in the default language, unless the visitor picks one.

With Translate Extended installed, a visit to the home page:

 * reads the visitor's preferred languages from the browser and matches them against the translations enabled in Winter.Translate;
 * saves the match into the session and displays it straight away;
 * falls back to the default language when nothing matches;
 * prefixes the route with the locale short code, so the URL stays SEO-friendly.

Once a locale has been detected, changing the route keeps the prefix. Typing a locale into the address bar by hand switches immediately and saves the choice to the session.

**Note:** by default the preferred browser language is saved on the first visit, so later visits restore it from the session instead of detecting it again. "Force language prefix" and "Prefer user session over auto detected language" in the backend settings control that.

## Extended locale picker

The plugin ships an `extendedLocalePicker` component. If you prefix URLs with locale codes, the stock locale picker from Winter.Translate will not behave correctly, because it switches the locale over AJAX. The extended picker builds real `href` links instead, which keeps the prefix intact.

```twig
{% component 'extendedLocalePicker' %}
```

## Settings

Translate Extended registers its settings page under **Settings → Translate**, next to the Translate plugin's own. "Translate Extended Settings" covers browser language detection, session preference, the query parameter and header overrides, route prefixing, the homepage redirect and the forced prefix.

## Language codes

Translate Extended needs the codes configured in Winter.Translate to match the ISO 639 codes sent in the `HTTP_ACCEPT_LANGUAGE` header, otherwise browser detection silently matches nothing.

## Upgrading to 2.0.0

2.0.0 is the release in which Cubic took the plugin over. Nothing about how it behaves changed, but four identifiers did, and they are the reason the version is a major one:

| | Before | After |
|---|---|---|
| Composer package | `studiobosco/wn-translate-extended` | `cubic/wn-translate-extended-plugin` |
| PHP namespace | `StudioBosco\TranslateExtended` | `Cubic\TranslateExtended` |
| Plugin identifier | `StudioBosco.TranslateExtended` | `Cubic.TranslateExtended` |
| Settings code | `studiobosco_translateextended_settings` | `cubic_translateextended_settings` |

`php artisan winter:up` moves the settings row to the new code, so your stored settings survive. The old composer package is declared as `replace`d, so requiring it pulls this plugin in instead.

If you extended or referenced the plugin's classes directly, update them to the `Cubic\TranslateExtended` namespace.

## Provenance

The code originated in [git.anzui.dev/wintercms/wn-translate-extended](https://git.anzui.dev/wintercms/wn-translate-extended). Cubic maintains it here, starting from commit `f788229` of that repository. The history before our first commit is unchanged, and our own work begins at `2.0.0`.

## Licence

MIT. See [LICENSE.md](LICENSE.md).