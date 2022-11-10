<?php
namespace StudioBosco\TranslateExtended\Models;

use Model;
class Settings extends Model
{

    public $implement = [
        'System.Behaviors.SettingsModel'
    ];

    public $settingsCode = 'studiobosco_translateextended_settings';

    public $settingsFields = 'fields.yaml';
}
