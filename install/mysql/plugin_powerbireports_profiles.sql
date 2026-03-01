-- Tabela para armazenar os perfis autorizados a visualizar cada relatório
CREATE TABLE IF NOT EXISTS `glpi_plugin_powerbireports_reports_profiles` (
   `id` int(11) NOT NULL AUTO_INCREMENT,
   `plugin_powerbireports_reports_id` int(11) NOT NULL,
   `profiles_id` int(11) NOT NULL,
   PRIMARY KEY (`id`),
   UNIQUE KEY `unique_report_profile` (`plugin_powerbireports_reports_id`,`profiles_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
