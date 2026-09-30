<?php declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnTypeFromStrictNewArrayRector;
use Rector\TypeDeclaration\Rector\ClassMethod\ReturnUnionTypeRector;
use Rector\TypeDeclaration\Rector\StmtsAwareInterface\SafeDeclareStrictTypesRector;

/**
 * Rector is installed as its own composer project under tools/, because
 * Winter's merge-plugin folds a plugin's `require` into the root package but
 * never its `require-dev` — so a dependency added here cannot leak into an
 * installation and cannot end up owned by Composer inside plugins/.
 *
 * Run it with:
 *   tools/vendor/bin/rector process --dry-run   # show what would change
 *   tools/vendor/bin/rector process             # apply
 *
 * The suite has to be green before and after. Rector cannot know which of these
 * rewrites are safe against a framework that reaches into the plugin at
 * runtime, which is what the tests are for.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Plugin.php',
        __DIR__ . '/classes',
        __DIR__ . '/components',
        __DIR__ . '/models',
    ])
    // The lowest version the plugin's composer.json declares.
    ->withPhpSets(php81: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
    )
    ->withSkip([
        // The suite is written against the plugin's current shape on purpose:
        // the test names are what the guardrail is read by, and rewriting them
        // would throw away the record of what the behaviour used to be.
        __DIR__ . '/tests',
        __DIR__ . '/tools',
        /*
         * Migration scripts are a historical record. Their job is to move the
         * schema a particular release left behind, and an install that skipped
         * that release still has to be able to run them unchanged.
         */
        __DIR__ . '/updates',
        /*
         * A file-level declare(strict_types=1) in one model and not in the rest
         * of the plugin is a change of behaviour nobody asked for, and it lands
         * in a single file only because Rector judged that one safe. Skipping it
         * keeps the plugin coherent.
         */
        SafeDeclareStrictTypesRector::class,
        /*
         * These two add a return type by also writing out a docblock of the
         * type they inferred. The inference is often unreadable — one of them
         * proposed array<int|(numeric-string & uppercase-string), lowercase-string>
         * for a list of language tags — and the docblock tells a reader nothing.
         * The return type is worth having; the comment is not.
         */
        ReturnTypeFromStrictNewArrayRector::class,
        ReturnUnionTypeRector::class,
    ]);
