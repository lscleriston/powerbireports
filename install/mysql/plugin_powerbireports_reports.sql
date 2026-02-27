CREATE TABLE IF NOT EXISTS `glpi_plugin_powerbireports_reports` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `group_id` varchar(255) NOT NULL,
  `report_id` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `icon_path` varchar(255) DEFAULT NULL,
  `update_mode` enum('api','table_column') NOT NULL DEFAULT 'api',
  `update_table` varchar(255) DEFAULT NULL,
  `update_column` varchar(255) DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
