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

        Schema::getConnection()
            ->table('system_settings')
            ->where('item', $from)
            ->update(['item' => $to]);
    }
}
