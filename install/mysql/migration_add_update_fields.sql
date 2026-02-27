-- Adiciona campos para controle de atualização personalizada
ALTER TABLE `glpi_plugin_powerbireports_reports`
ADD COLUMN `update_mode` ENUM('api', 'table_column') NOT NULL DEFAULT 'api' AFTER `icon_path`,
ADD COLUMN `update_table` VARCHAR(255) DEFAULT NULL AFTER `update_mode`,
ADD COLUMN `update_column` VARCHAR(255) DEFAULT NULL AFTER `update_table`;
