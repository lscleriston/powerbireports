<?php
namespace GlpiPlugin\Powerbireports;

class ConfigItem extends \CommonDBTM {
    public static $rightname = 'powerbireports';
    public $dohistory = false;

    public static function getTable($classname = null) {
        return 'glpi_plugin_powerbireports_configs';
    }
}
