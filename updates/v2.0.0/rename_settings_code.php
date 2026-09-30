<?php namespace Cubic\TranslateExtended\Updates;

use Schema;
use Winter\Storm\Database\Updates\Migration;

/**
 * Moves the settings row to the rebranded settings code.
 *
 * The settings code is the primary key of a `system_settings` row, so renaming it
 * without moving the row would silently drop every stored setting.
 */
class RenameSettingsCode extends Migration
{
    const FROM_CODE = 'studiobosco_translateextended_settings';
    const TO_CODE = 'cubic_translateextended_settings';

    public function up()
    {
        $this->renameCode(self::FROM_CODE, self::TO_CODE);
    }

    public function down()
    {
        $this->renameCode(self::TO_CODE, self::FROM_CODE);
    }

    protected function renameCode($from, $to)
    {
        if (!Schema::hasTable('system_settings')) {
            return;
        }

        if (!$this->settingsRowExists($from)) {
            return;
        }

        /*
         * The target key can already be taken. It happens when the plugin is
         * deployed before its update is run: Settings::$settingsCode is already
         * the new value, so the first request the site serves writes a row under
         * it while the old row is still there.
         *
         * `item` is indexed but not unique, so renaming anyway would leave two
         * rows under one key instead of failing. The settings behaviour then
         * reads whichever the database hands back first, with no ordering to
         * choose by. The old row holds the operator's configuration, so it is
         * the one that has to survive and the duplicate is the one that goes.
         */
        if ($this->settingsRowExists($to)) {
            $this->settingsRow($to)->delete();
        }

        $this->settingsRow($from)->update(['item' => $to]);
    }

    /**
     * A fresh builder every time. A query builder keeps the constraints it has
     * already been given, so reusing one across two queries silently applies
     * both of them to the second.
     */
    protected function settingsRow($code)
    {
        return Schema::getConnection()->table('system_settings')->where('item', $code);
    }

    protected function settingsRowExists($code)
    {
        return $this->settingsRow($code)->exists();
    }
}
