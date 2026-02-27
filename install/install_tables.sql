-- ============================================================
-- Power BI Reports Plugin - Script de Criação de Tabelas
-- Compatível com GLPI 11.x
-- ============================================================
-- Execute este script no banco de dados do GLPI antes de instalar o plugin.
-- Exemplo: mysql glpidb < install_tables.sql
-- ============================================================

-- Tabela de configuração (credenciais do Power BI)
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

-- Tabela de relatórios (múltiplos relatórios)
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

-- ============================================================
-- Fim do script de instalação
-- ============================================================
