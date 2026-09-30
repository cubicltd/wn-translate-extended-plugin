# Translate Extended

A Winter CMS plugin extending [Winter.Translate](https://github.com/wintercms/wn-translate-plugin) with browser language detection, locale route prefixes and an extended locale picker.

## Where our work begins

**Everything above the `2.0.0` commit is not ours.** The repository was imported from `git.anzui.dev/wintercms/wn-translate-extended`, a self-hosted Git, and that history is unchanged. `git log` credits everyone who wrote it; do not guess at roles from commit counts, and do not name individuals in prose — the history is the record.

**Conventional Commits start at `2.0.0` and apply to nothing above it.** Before that line the log reads the way the original project wrote.

`2.0.0` is also the release that renamed the composer package, the PHP namespace and the settings code. Those three are why it is a major version. It also changes behaviour in two places, both in `BrowserMatching`: a bare `*` no longer selects a locale, and a bare `q=0` is honoured as a refusal. `notes/translate-extended.md` in the environment repository has the detail.

**Do not describe the upstream as abandoned.** The project lives in a self-hosted Git at `git.anzui.dev/wintercms/wn-translate-extended` and is published on Packagist as `studiobosco/wn-translate-extended`. The GitHub repository of the same name does not resolve, which is not the same thing. Our import was taken from `f788229`, the commit the Packagist publication pointed at. The `replace` in `composer.json` is what redirects anything still requiring the old package.

## Identity is not free to choose

Winter does not read `composer.json` to learn what a plugin is. `PluginManager::getVendorAndPluginNames()` walks `plugins/*/*/`, and for each `Plugin.php` it finds builds a class name out of the two directory names and asks for it. A path that cannot produce the class the file declares gives a plugin that is cloned, excluded from git, reported `OK` by `workspaces.sh check` and **never loaded** — with no warning anywhere.

Two things about that rule are easy to get wrong.

**The directory names are used verbatim, so every segment must be a legal PHP identifier.** `Str::normalizeClassName()` only strips a leading backslash; it does not StudlyCase and it does not remove separators. So `plugins/cubic/translate-extended` is asked for as `\cubic\translate-extended\Plugin` — note the directory's own lower case — and no class can satisfy that. A hyphen, a dot, a space, a `+` or a leading digit all break it. Underscores and non-ASCII letters do not. The rule applies to the **vendor** directory too, not only to the plugin's own.

**Case does not matter.** Class names in PHP are case-insensitive, so `plugins/cubic/TranslateExtended` resolves exactly as well as the lowercase form. What matters is only which characters are legal.

The path is therefore `plugins/cubic/translateextended`, matching the namespace and the `installer-name` in `composer.json`. A multi-word plugin name is never hyphenated on disk.

The composer vendor and the GitHub organisation (`cubicltd`) are independent. Renaming one does not rename the other.

## Rules for this repository

- **Never put `winter/wn-translate-plugin` in `require`.** The core's merge-plugin folds a plugin's `require` into the root package, so Composer would resolve `winter/wn-translate-plugin` and install it — and the install path is `plugins/winter/translate`, which is a live clone here. `FileDownloader::install()` **empties** that directory first, `.git` included, and unpacks the dist zipball into it. The runtime dependency is declared by `Plugin::$require` and nothing else.
- **Do not hand-edit compiled or generated output.** There is none here; this is a plain plugin with no build step.
- **`updates/version.yaml` keys carry no `v`; the `updates/` directory does.** `2.0.0:` points at `v2.0.0/rename_settings_code.php`. Quoted keys matter where a key could parse as a float.
- **PHP 8.1 is the floor.** `rector.php` enforces it and `rector process --dry-run` is expected to come back empty.
- The whole suite must be green before and after any change. `php artisan winter:test -p Cubic.TranslateExtended`.

## Traps specific to this plugin

- **`BrowserMatching` reads `$_SERVER` directly**, not through Laravel's request, so a test cannot drive it with `$this->withServerVariables()`. Set and restore `$_SERVER` by hand.
- **`Settings::$settingsCode` is a database key**, the primary key of a `system_settings` row. Renaming it orphans stored settings unless a migration moves the row; `updates/v2.0.0/rename_settings_code.php` is the precedent.
- **`Translator::isconfigured()` is called in the wrong case.** PHP method names are case-insensitive so it works, and Rector leaves it alone — `RenameMethodRector` is not part of any standard set, it needs a `rector/rename` entry. Do not write tests that assert the lowercase spelling, and do not expect the modernisation to have fixed it.
- **`ExtendedLocalePicker` extends a frozen first-party class.** Do not mark it `final` and do not try to change the parent's `makeLocaleUrlFromPage()`.
- **`Plugin::registerRouting()` reads the global request and ignores the middleware-style argument**, and it only runs at all when `PluginManager::$noInit` is false. Both are handled in `tests/TranslateExtendedTestCase`; see the isolation note below.

## Test isolation

Three pieces of state outlive the application that a test tears down, and each one makes a test fail in a way that looks like the plugin's fault. The suite runs in random order, so without these the failures are intermittent. `TranslateExtendedTestCase::setUp()` resets all three:

- **`System\Behaviors\SettingsModel::$instances`** is a static array. Without clearing it, a setting written by one test is still readable by the next — and it presents as a routing bug, because that is where the settings are read.
- **`Winter\Translate\Classes\Translator`** is a singleton, so a locale set by one test is still active in the next.
- **`PluginManager::$noInit`** is a static kill-switch that `ServiceProvider::registerPrivilegedActions()` sets to `true` for a restricted bootstrap — the updates, install and migrate commands, or any console run where the migration table does not exist yet. Nothing ever sets it back, and `registerPrivilegedActions()` re-evaluates it on **every** application bootstrap, so the first one that satisfies a condition decides it for the rest of the process. It is a console-only path, and on a database that already has its migration table it never fires — so in the dev environment the reset below is defensive. It fires on a fresh database, which is what CI and a first install get. `plugins/cubic/backend/tests/BackendTestCase.php` resets it for the same reason.

A fourth, not a static: a test that needs a specific request has to put a **real** one in place, replacing both the container binding and the facade. `Request::swap()` alone is not enough for the `request()` helper, which reads the binding; and a URI cannot be changed after the request exists, because the base URL has already been resolved from what it was built with.

The `$noInit` reset happens *after* `PluginTestCase::setUp()` has already called `instantiatePlugin()`, so on a database where the flag fires, the plugin was never registered or booted and setting it false afterwards does not put that back. `PluginRoutingTest` does not rely on it: it discards the routes and calls `bootPlugin()` itself, which is a no-op unless the flag is false first. That is why the routing tests are the ones that prove the reset matters.

## Tests

`tests/unit` for the pure pieces, `tests/feature` for anything that boots the framework. `tests/TranslateExtendedTestCase` extends `System\Tests\Bootstrap\PluginTestCase`, which boots the application on in-memory SQLite, runs `winter:up` before each test, and derives the plugin under test from the test class's namespace — so tests must live under `Cubic\TranslateExtended\Tests`.

The suite runs with `php artisan winter:test -p Cubic.TranslateExtended`. CI runs it on PHP 8.1, 8.2 and 8.3, with no database service because the connection is in-memory SQLite.

Rector is checked but not allowed to rewrite: the workflow runs `--dry-run` and fails if anything is left, so a contributor finds out in CI rather than in review. `tools/vendor/bin/rector process` applies it.

`phpcs.xml` is the first-party plugin's ruleset, unchanged. Run `vendor/bin/phpcs -n --report=full --extensions=php <files>` from a Winter checkout before pushing.

The languages in `lang/` are held to the English key set by a test. Adding a key to English means adding it to every other file in the same commit, or the suite goes red.
