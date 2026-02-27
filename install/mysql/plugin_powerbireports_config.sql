CREATE TABLE IF NOT EXISTS `glpi_plugin_powerbireports_configs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tenant_id` varchar(255) DEFAULT NULL,
  `client_id` varchar(255) DEFAULT NULL,
  `client_secret` varchar(255) DEFAULT NULL,
  `group_id` varchar(255) DEFAULT NULL,
  `report_id` varchar(255) DEFAULT NULL,
  `last_token` text DEFAULT NULL,
  `token_expiry` datetime DEFAULT NULL,
  `last_embed_token` text DEFAULT NULL,
  `embed_token_expiry` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;