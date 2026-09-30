# Translate Extended

A Winter CMS plugin extending [Winter.Translate](https://github.com/wintercms/wn-translate-plugin) with browser language detection, locale route prefixes and an extended locale picker.

## Where our work begins

**Everything above the `2.0.0` commit is not ours.** The repository was imported from `git.anzui.dev/wintercms/wn-translate-extended`, a self-hosted Git, and that history is unchanged. `git log` credits everyone who wrote it; do not guess at roles from commit counts, and do not name individuals in prose — the history is the record.

**Conventional Commits start at `2.0.0` and apply to nothing above it.** Before that line the log reads the way the original project wrote.

`2.0.0` is also the release that renamed the composer package, the PHP namespace and the settings code. Nothing about behaviour changed in it; it is a major version because every identifier did.

**Do not describe the upstream as abandoned.** The project lives in a self-hosted Git at `git.anzui.dev/wintercms/wn-translate-extended` and is published on Packagist as `studiobosco/wn-translate-extended`. The GitHub repository of the same name does not resolve, which is not the same thing. Our import was taken from `f788229`, the commit the Packagist publication pointed at. The `replace` in `composer.json` is what redirects anything still requiring the old package.

## Identity is not free to choose

Winter derives a plugin's identity from the directory it sits in, not from `composer.json`. The install path must equal the declared namespace, **with no separators**, or the plugin is cloned, excluded from git, reported `OK` by `workspaces.sh check` and **never loaded** — with no warning anywhere.

The separator rule is the part that bites. `PluginManager::getPluginNamespaces()` builds `\Cubic\translate-extended` from the directory and passes it to `class_exists()`, but PHP class names cannot contain a hyphen, so the lookup fails and `loadPlugin()` returns `null` silently. The path is therefore `plugins/cubic/translateextended` — one lowercase word, matching `Cubic\TranslateExtended`, and matching the `installer-name` in `composer.json`. A multi-word plugin name is never hyphenated, camel-cased or snake-cased on disk.

The composer vendor and the GitHub organisation (`cubicltd`) are independent. Renaming one does not rename the other.

## Rules for this repository

- **Never put `winter/wn-translate-plugin` in `require`.** The core's merge-plugin folds plugin requires into the root package, so Composer would install a zipball over the working tree in `plugins/winter/translate`. The runtime dependency is declared by `Plugin::$require` and nothing else.
- **Do not hand-edit compiled or generated output.** There is none here; this is a plain plugin with no build step.
- **`updates/version.yaml` keys carry no `v`; the `updates/` directory does.** `2.0.0:` points at `v2.0.0/rename_settings_code.php`. Quoted keys matter where a key could parse as a float.
- **PHP 8.1 is the floor.** `rector.php` enforces it and `rector process --dry-run` is expected to come back empty.
- The whole suite must be green before and after any change. `php artisan winter:test -p Cubic.TranslateExtended`.

## Traps specific to this plugin

- **`BrowserMatching` reads `$_SERVER` directly**, not through Laravel's request, so a test cannot drive it with `$this->withServerVariables()`. Set and restore `$_SERVER` by hand.
- **`Settings::$settingsCode` is a database key**, the primary key of a `system_settings` row. Renaming it orphans stored settings unless a migration moves the row; `updates/v2.0.0/rename_settings_code.php` is the precedent.
- **`Translator::isconfigured()` is called in the wrong case.** PHP method names are case-insensitive so it works; do not write tests that assert the lowercase spelling, because the correct spelling is what will be there after modernisation.
- **`ExtendedLocalePicker` extends a frozen first-party class.** Do not mark it `final` and do not try to change the parent's `makeLocaleUrlFromPage()`.
- **`Plugin::registerRouting()` reads the global request and ignores the middleware-style argument**, and it only runs at all when `PluginManager::$noInit` is false. Both are handled in `tests/TranslateExtendedTestCase`; see the isolation note below.

## Test isolation

Three pieces of state outlive the application that a test tears down, and each one makes a test fail in a way that looks like the plugin's fault. The suite runs in random order, so without these the failures are intermittent. `TranslateExtendedTestCase::setUp()` resets all three:

- **`System\Behaviors\SettingsModel::$instances`** is a static array. Without clearing it, a setting written by one test is still readable by the next — and it presents as a routing bug, because that is where the settings are read.
- **`Winter\Translate\Classes\Translator`** is a singleton, so a locale set by one test is still active in the next.
- **`PluginManager::$noInit`** is set to `true` during the first bootstrap of the process, on the branch that fires when the migration table does not exist yet, and nothing ever sets it back. From then on `bootPlugin()` returns immediately for any plugin that is not elevated, so a test that depends on the plugin booting silently tests nothing at all.

A fourth, not a static: a test that needs a specific request has to put a **real** one in place, replacing both the container binding and the facade. `Request::swap()` alone is not enough for the `request()` helper, which reads the binding; and a URI cannot be changed after the request exists, because the base URL has already been resolved from what it was built with.

## Tests

`tests/unit` for the pure pieces, `tests/feature` for anything that boots the framework. `tests/TranslateExtendedTestCase` extends `System\Tests\Bootstrap\PluginTestCase`, which boots the application on in-memory SQLite, runs `winter:up` before each test, and derives the plugin under test from the test class's namespace — so tests must live under `Cubic\TranslateExtended\Tests`.

The suite runs with `php artisan winter:test -p Cubic.TranslateExtended`. CI runs it on PHP 8.1, 8.2 and 8.3, with no database service because the connection is in-memory SQLite.

Rector is checked but not allowed to rewrite: the workflow runs `--dry-run` and fails if anything is left, so a contributor finds out in CI rather than in review. `tools/vendor/bin/rector process` applies it.

`phpcs.xml` is the first-party plugin's ruleset, unchanged. Run `vendor/bin/phpcs -n --report=full --extensions=php <files>` from a Winter checkout before pushing.

The languages in `lang/` are held to the English key set by a test. Adding a key to English means adding it to every other file in the same commit, or the suite goes red.
