-- Tabela de usuários autorizados por relatório
CREATE TABLE IF NOT EXISTS glpi_plugin_powerbireports_reports_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plugin_powerbireports_reports_id INT NOT NULL,
    users_id INT NOT NULL,
    UNIQUE KEY unique_report_user (plugin_powerbireports_reports_id, users_id),
    KEY plugin_powerbireports_reports_id (plugin_powerbireports_reports_id),
    KEY users_id (users_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Tabela de grupos autorizados por relatório
CREATE TABLE IF NOT EXISTS glpi_plugin_powerbireports_reports_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plugin_powerbireports_reports_id INT NOT NULL,
    groups_id INT NOT NULL,
    UNIQUE KEY unique_report_group (plugin_powerbireports_reports_id, groups_id),
    KEY plugin_powerbireports_reports_id (plugin_powerbireports_reports_id),
    KEY groups_id (groups_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
