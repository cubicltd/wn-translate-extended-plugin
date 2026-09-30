<?php
namespace Cubic\TranslateExtended\Models;

use Model;

class Settings extends Model
{

    public $implement = [
        'System.Behaviors.SettingsModel'
    ];

    public $settingsCode = 'cubic_translateextended_settings';

    public $settingsFields = 'fields.yaml';
}
