<?php namespace Cubic\TranslateExtended\Tests\Unit\Updates;

use Db;
use Winter\Storm\Database\Updater;
use Cubic\TranslateExtended\Tests\TranslateExtendedTestCase;

class RenameSettingsCodeTest extends TranslateExtendedTestCase
{
    const OLD_CODE = 'studiobosco_translateextended_settings';
    const NEW_CODE = 'cubic_translateextended_settings';

    /**
     * The same object VersionManager drives, so the test exercises the real
     * path by which Winter loads a file out of updates/ rather than reaching
     * for the class directly.
     */
    protected function updater()
    {
        return new Updater;
    }

    protected function scriptPath()
    {
        return dirname(__DIR__, 3) . '/updates/v2.0.0/rename_settings_code.php';
    }

    protected function seedSettingsRow($code, $value)
    {
        Db::table('system_settings')->insert(['item' => $code, 'value' => $value]);
    }

    protected function settingsValue($code)
    {
        return Db::table('system_settings')->where('item', $code)->value('value');
    }

    public function testUpMovesTheRowToTheNewSettingsCode()
    {
        $this->seedSettingsRow(self::OLD_CODE, 'stored settings');

        $this->updater()->setUp($this->scriptPath());

        $this->assertNull($this->settingsValue(self::OLD_CODE));
        $this->assertSame('stored settings', $this->settingsValue(self::NEW_CODE));
    }

    public function testDownMovesTheRowBack()
    {
        $this->seedSettingsRow(self::NEW_CODE, 'stored settings');

        $this->updater()->packDown($this->scriptPath());

        $this->assertNull($this->settingsValue(self::NEW_CODE));
        $this->assertSame('stored settings', $this->settingsValue(self::OLD_CODE));
    }

    /**
     * A fresh install has no row under the old code, and re-running the script
     * must not fail or invent one.
     */
    public function testUpIsANoOpWhenThereIsNoRowToMove()
    {
        $this->updater()->setUp($this->scriptPath());

        $this->assertSame(0, Db::table('system_settings')
            ->whereIn('item', [self::OLD_CODE, self::NEW_CODE])
            ->count());
    }

    public function testUpLeavesOtherSettingsAlone()
    {
        $this->seedSettingsRow('winter_translate_settings', 'someone else');
        $this->seedSettingsRow(self::OLD_CODE, 'stored settings');

        $this->updater()->setUp($this->scriptPath());

        $this->assertSame('someone else', $this->settingsValue('winter_translate_settings'));
    }

    /**
     * Running the script twice is what a retried update does.
     */
    public function testUpIsIdempotent()
    {
        $this->seedSettingsRow(self::OLD_CODE, 'stored settings');

        $this->updater()->setUp($this->scriptPath());
        $this->updater()->setUp($this->scriptPath());

        $this->assertSame(1, Db::table('system_settings')
            ->whereIn('item', [self::OLD_CODE, self::NEW_CODE])
            ->count());
    }

    /**
     * A row under the new code already exists when the plugin is deployed
     * before its update runs: Settings::$settingsCode is already the new value,
     * so the first request the site serves writes a row under it, and the old
     * row is still there when winter:up gets to the migration.
     *
     * `item` is indexed but not unique, so renaming unconditionally leaves two
     * rows under the same key instead of failing. The settings behaviour then
     * reads whichever the database returns first, and the stored configuration
     * is gone. The old row has to win.
     */
    public function testTheOldRowWinsWhenBothRowsExist()
    {
        $this->seedSettingsRow(self::NEW_CODE, 'written by a request before the update ran');
        $this->seedSettingsRow(self::OLD_CODE, 'the operator\'s configuration');

        $this->updater()->setUp($this->scriptPath());

        $this->assertSame(1, Db::table('system_settings')
            ->whereIn('item', [self::OLD_CODE, self::NEW_CODE])
            ->count());

        $this->assertSame(
            'the operator\'s configuration',
            $this->settingsValue(self::NEW_CODE)
        );
    }
}
