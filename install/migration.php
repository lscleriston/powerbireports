<?php
// Arquivo: install/migration.php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

use Glpi\Migration\Migration;

function plugin_powerbireports_migration(Migration $migration) {
    $sqlfile = __DIR__ . '/mysql/plugin_powerbireports_reports.sql';
    if (file_exists($sqlfile)) {
        $migration->runFile($sqlfile);
    }

    // Garantir novos campos para modo de atualização customizado
    global $DB;
    if (!$DB->fieldExists('glpi_plugin_powerbireports_reports', 'update_mode')) {
        $migration->addPostQuery("ALTER TABLE `glpi_plugin_powerbireports_reports` ADD COLUMN `update_mode` ENUM('api','table_column') NOT NULL DEFAULT 'api' AFTER `icon_path`");
    }
    if (!$DB->fieldExists('glpi_plugin_powerbireports_reports', 'update_table')) {
        $migration->addPostQuery("ALTER TABLE `glpi_plugin_powerbireports_reports` ADD COLUMN `update_table` VARCHAR(255) DEFAULT NULL AFTER `update_mode`");
    }
    if (!$DB->fieldExists('glpi_plugin_powerbireports_reports', 'update_column')) {
        $migration->addPostQuery("ALTER TABLE `glpi_plugin_powerbireports_reports` ADD COLUMN `update_column` VARCHAR(255) DEFAULT NULL AFTER `update_table`");
    }
}
